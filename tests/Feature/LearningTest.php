<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Build a published course with two lessons and a quiz in one section.
 *
 * @return array{course: Course, first: Lesson, second: Lesson, quiz: Quiz}
 */
function learnableCourse(?User $instructor = null): array
{
    $course = Course::factory()
        ->ownedBy($instructor ?? User::factory()->instructor()->create())
        ->published()
        ->create();
    $section = Section::factory()->create(['course_id' => $course->id]);

    return [
        'course' => $course,
        'first' => Lesson::factory()->atPosition(1)->create([
            'section_id' => $section->id,
            'content_type' => Lesson::TYPE_VIDEO,
            'content_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]),
        'second' => Lesson::factory()->atPosition(2)->create([
            'section_id' => $section->id,
            'content_type' => Lesson::TYPE_ARTICLE,
            'content' => '<p>Isi artikel</p>',
            'content_url' => null,
        ]),
        'quiz' => Quiz::factory()->create(['section_id' => $section->id]),
    ];
}

function enrolledStudentIn(Course $course): User
{
    $student = User::factory()->student()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    return $student;
}

test('an enrolled student can open a lesson with the course outline', function () {
    ['course' => $course, 'first' => $first, 'second' => $second] = learnableCourse();
    $student = enrolledStudentIn($course);

    $this->actingAs($student)
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('learn/Lesson')
            ->where('lesson.id', $first->id)
            ->where('lesson.embed_url', 'https://www.youtube-nocookie.com/embed/abcdefghijk')
            ->where('lesson.is_completed', false)
            ->has('outline.sections.0.items', 3)
            ->where('outline.progress.total', 3)
            ->where('neighbours.previous', null)
            ->where('neighbours.next.id', $second->id)
        );
});

test('people without an active enrollment are sent back to the course page', function (Closure $makeUser) {
    ['course' => $course, 'first' => $first] = learnableCourse();

    $this->actingAs($makeUser($course))
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertRedirect(route('catalog.show', $course));
})->with([
    'not enrolled' => [fn () => User::factory()->student()->create()],
    'cancelled enrollment' => [function (Course $course) {
        $student = User::factory()->student()->create();
        Enrollment::factory()->for($student)->for($course)->cancelled()->create();

        return $student;
    }],
    'another instructor' => [fn () => User::factory()->instructor()->create()],
]);

test('guests must log in to learn', function () {
    ['course' => $course, 'first' => $first] = learnableCourse();

    $this->get(route('learn.lessons.show', [$course, $first]))->assertRedirect(route('login'));
});

test('the course instructor can preview lessons without enrolling', function () {
    $instructor = User::factory()->instructor()->create();
    ['course' => $course, 'first' => $first] = learnableCourse($instructor);

    $this->actingAs($instructor)
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertOk();
});

test('a lesson from another course cannot be opened through this course', function () {
    ['course' => $course] = learnableCourse();
    ['first' => $foreignLesson] = learnableCourse();

    $this->actingAs(enrolledStudentIn($course))
        ->get(route('learn.lessons.show', [$course, $foreignLesson]))
        ->assertNotFound();
});

test('opening a course resumes at the first unfinished item', function () {
    ['course' => $course, 'first' => $first, 'second' => $second, 'quiz' => $quiz] = learnableCourse();
    $student = enrolledStudentIn($course);

    $this->actingAs($student)
        ->get(route('learn.show', $course))
        ->assertRedirect(route('learn.lessons.show', [$course, $first]));

    $student->completedLessons()->attach([$first->id, $second->id]);

    $this->actingAs($student)
        ->get(route('learn.show', $course))
        ->assertRedirect(route('learn.quizzes.show', [$course, $quiz]));
});

test('a student can mark lessons complete and see their progress', function () {
    ['course' => $course, 'first' => $first] = learnableCourse();
    $student = enrolledStudentIn($course);

    $this->actingAs($student)
        ->post(route('learn.lessons.complete', [$course, $first]))
        ->assertRedirect();

    expect($student->completedLessons()->pluck('lessons.id')->all())->toBe([$first->id]);

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.progress.completed', 1)
            ->where('enrollments.0.progress.total', 3)
            ->where('enrollments.0.progress.percent', 33)
        );

    $this->actingAs($student)
        ->delete(route('learn.lessons.uncomplete', [$course, $first]))
        ->assertRedirect();

    expect($student->completedLessons()->count())->toBe(0);
});

test('students cannot mark lessons of courses they are not enrolled in', function () {
    ['course' => $course, 'first' => $first] = learnableCourse();

    $this->actingAs(User::factory()->student()->create())
        ->post(route('learn.lessons.complete', [$course, $first]))
        ->assertForbidden();
});

test('lesson attachments can only be downloaded by learners', function () {
    Storage::fake(LessonAttachment::DISK);
    ['course' => $course, 'first' => $first] = learnableCourse();
    $path = UploadedFile::fake()->create('slides.pdf', 20)->store(LessonAttachment::DIRECTORY, LessonAttachment::DISK);
    $attachment = $first->attachments()->create([
        'name' => 'slides.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 20480,
    ]);

    $this->actingAs(enrolledStudentIn($course))
        ->get(route('learn.attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('slides.pdf');

    $this->actingAs(User::factory()->student()->create())
        ->get(route('learn.attachments.download', $attachment))
        ->assertForbidden();
});

test('the catalog tells enrolled students they can start learning', function () {
    ['course' => $course] = learnableCourse();

    $this->actingAs(enrolledStudentIn($course))
        ->get(route('catalog.show', $course))
        ->assertInertia(fn ($page) => $page->where('can.learn', true));

    $this->actingAs(User::factory()->student()->create())
        ->get(route('catalog.show', $course))
        ->assertInertia(fn ($page) => $page->where('can.learn', false));
});
