<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function lessonFor(User $instructor): Lesson
{
    $course = Course::factory()->ownedBy($instructor)->create();
    $section = Section::factory()->create(['course_id' => $course->id]);

    return Lesson::factory()->create(['section_id' => $section->id]);
}

test('guests are redirected from the lesson list', function () {
    $this->get(route('lessons.index'))->assertRedirect(route('login'));
});

test('a student cannot reach the lesson list', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('lessons.index'))->assertRedirect(route('home'));
});

test('an instructor only sees lessons from their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    lessonFor($instructor);
    Lesson::factory()->create();

    $this->actingAs($instructor)
        ->get(route('lessons.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('lessons/Index')
            ->has('lessons.data', 1));
});

test('an admin sees lessons from every course', function () {
    $admin = User::factory()->admin()->create();
    Lesson::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('lessons.index'))
        ->assertInertia(fn ($page) => $page->has('lessons.data', 3));
});

test('the lesson list can be filtered by course and content type', function () {
    $admin = User::factory()->admin()->create();
    $lesson = Lesson::factory()->create(['content_type' => Lesson::TYPE_ARTICLE]);
    Lesson::factory()->create(['content_type' => Lesson::TYPE_VIDEO]);

    $this->actingAs($admin)
        ->get(route('lessons.index', ['content_type' => Lesson::TYPE_ARTICLE]))
        ->assertInertia(fn ($page) => $page->has('lessons.data', 1));

    $this->actingAs($admin)
        ->get(route('lessons.index', ['course_id' => $lesson->section->course_id]))
        ->assertInertia(fn ($page) => $page->has('lessons.data', 1));
});

test('an instructor can open the editor for their own lesson', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)
        ->get(route('lessons.edit', ['course' => $lesson->section->course, 'lesson' => $lesson]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('lessons/Edit'));
});

test('an instructor cannot open the editor for another instructors lesson', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($instructor)->get(route('lessons.edit', ['course' => $lesson->section->course, 'lesson' => $lesson]))->assertForbidden();
});

test('lesson material is saved and dangerous html is stripped', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)
        ->put(route('lessons.update', $lesson), [
            'title' => $lesson->title,
            'content_type' => Lesson::TYPE_ARTICLE,
            'content' => '<p>Materi <strong>penting</strong></p><script>alert(1)</script>',
        ])
        ->assertRedirect();

    expect($lesson->refresh()->content)
        ->toBe('<p>Materi <strong>penting</strong></p>');
});

test('a javascript link is stripped from lesson material', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->put(route('lessons.update', $lesson), [
        'title' => $lesson->title,
        'content_type' => Lesson::TYPE_ARTICLE,
        'content' => '<p><a href="javascript:alert(1)">klik</a></p>',
    ]);

    expect($lesson->refresh()->content)->not->toContain('javascript:');
});

test('an external link in lesson material keeps a safe rel and target', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->put(route('lessons.update', $lesson), [
        'title' => $lesson->title,
        'content_type' => Lesson::TYPE_ARTICLE,
        'content' => '<p><a href="https://laravel.com">docs</a></p>',
    ]);

    expect($lesson->refresh()->content)
        ->toContain('href="https://laravel.com"')
        ->toContain('rel="noopener noreferrer"');
});

test('material that is only empty markup is stored as null', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->put(route('lessons.update', $lesson), [
        'title' => $lesson->title,
        'content_type' => Lesson::TYPE_ARTICLE,
        'content' => '<p></p>',
    ]);

    expect($lesson->refresh()->content)->toBeNull();
});

test('material is also sanitized when a lesson is created', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create([
        'course_id' => Course::factory()->ownedBy($instructor)->create()->id,
    ]);

    $this->actingAs($instructor)->post(route('lessons.store', $section), [
        'title' => 'Materi Baru',
        'content_type' => Lesson::TYPE_ARTICLE,
        'content' => '<p>Aman</p><img src=x onerror=alert(1)>',
    ]);

    expect(Lesson::sole()->content)->toBe('<p>Aman</p>');
});

test('an instructor can attach files to their own lesson', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)
        ->post(route('lesson-attachments.store', $lesson), [
            'files' => [
                UploadedFile::fake()->create('materi.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('diagram.png'),
            ],
        ])
        ->assertRedirect();

    expect($lesson->attachments()->count())->toBe(2)
        ->and($lesson->attachments()->pluck('name')->all())
        ->toBe(['materi.pdf', 'diagram.png']);

    foreach ($lesson->attachments as $attachment) {
        Storage::disk(LessonAttachment::DISK)->assertExists($attachment->path);
    }
});

test('an instructor cannot attach files to another instructors lesson', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($instructor)
        ->post(route('lesson-attachments.store', $lesson), [
            'files' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')],
        ])
        ->assertForbidden();

    expect(LessonAttachment::query()->count())->toBe(0);
});

test('an executable file cannot be attached', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)
        ->post(route('lesson-attachments.store', $lesson), [
            'files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
        ])
        ->assertSessionHasErrors('files.0');

    expect(LessonAttachment::query()->count())->toBe(0);
});

test('an oversized attachment is rejected', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)
        ->post(route('lesson-attachments.store', $lesson), [
            'files' => [
                UploadedFile::fake()->create(
                    'besar.pdf',
                    LessonAttachment::MAX_KILOBYTES + 1,
                    'application/pdf',
                ),
            ],
        ])
        ->assertSessionHasErrors('files.0');

    expect(LessonAttachment::query()->count())->toBe(0);
});

test('deleting an attachment removes its stored file', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->post(route('lesson-attachments.store', $lesson), [
        'files' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')],
    ]);

    $attachment = LessonAttachment::sole();

    $this->actingAs($instructor)
        ->delete(route('lesson-attachments.destroy', $attachment))
        ->assertRedirect();

    Storage::disk(LessonAttachment::DISK)->assertMissing($attachment->path);
    expect(LessonAttachment::query()->count())->toBe(0);
});

test('deleting a lesson removes its attachment files', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->post(route('lesson-attachments.store', $lesson), [
        'files' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')],
    ]);
    $path = LessonAttachment::sole()->path;

    $this->actingAs($instructor)->delete(route('lessons.destroy', $lesson));

    Storage::disk(LessonAttachment::DISK)->assertMissing($path);
    expect(LessonAttachment::query()->count())->toBe(0);
});

test('deleting a section removes the attachment files of its lessons', function () {
    Storage::fake(LessonAttachment::DISK);

    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->post(route('lesson-attachments.store', $lesson), [
        'files' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')],
    ]);
    $path = LessonAttachment::sole()->path;

    $this->actingAs($instructor)->delete(route('sections.destroy', $lesson->section));

    Storage::disk(LessonAttachment::DISK)->assertMissing($path);
    expect(Lesson::query()->count())->toBe(0)
        ->and(LessonAttachment::query()->count())->toBe(0);
});

test('deleting a course removes the attachment files of its whole curriculum', function () {
    Storage::fake(LessonAttachment::DISK);

    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $lesson = lessonFor($instructor);

    $this->actingAs($instructor)->post(route('lesson-attachments.store', $lesson), [
        'files' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')],
    ]);
    $path = LessonAttachment::sole()->path;

    $this->actingAs($admin)->delete(route('courses.destroy', $lesson->section->course));

    Storage::disk(LessonAttachment::DISK)->assertMissing($path);
    expect(Section::query()->count())->toBe(0)
        ->and(Lesson::query()->count())->toBe(0)
        ->and(LessonAttachment::query()->count())->toBe(0);
});
