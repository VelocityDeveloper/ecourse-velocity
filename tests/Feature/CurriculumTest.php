<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

function ownedCourse(User $instructor): Course
{
    return Course::factory()->ownedBy($instructor)->create();
}

test('guests cannot touch the curriculum', function () {
    $section = Section::factory()->create();

    $this->post(route('sections.store', $section->course))->assertRedirect(route('login'));
    $this->delete(route('sections.destroy', $section))->assertRedirect(route('login'));
});

test('a student cannot add a section', function () {
    $student = User::factory()->student()->create();
    $course = Course::factory()->create();

    $this->actingAs($student)
        ->post(route('sections.store', $course), ['title' => 'Nope'])
        ->assertForbidden();
});

test('an instructor can add a section to their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);

    $this->actingAs($instructor)
        ->post(route('sections.store', $course), [
            'title' => 'Pengenalan',
            'description' => 'Garis besar materi.',
        ])
        ->assertRedirect();

    expect($course->sections()->count())->toBe(1)
        ->and(Section::sole()->position)->toBe(1);
});

test('an instructor cannot add a section to another instructors course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();

    $this->actingAs($instructor)
        ->post(route('sections.store', $course), ['title' => 'Hijack'])
        ->assertForbidden();

    expect(Section::query()->count())->toBe(0);
});

test('an admin can add a section to any course', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->post(route('sections.store', $course), ['title' => 'Admin Section'])
        ->assertRedirect();

    expect(Section::query()->count())->toBe(1);
});

test('new sections are appended to the end of the curriculum', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);

    foreach (['One', 'Two', 'Three'] as $title) {
        $this->actingAs($instructor)->post(route('sections.store', $course), ['title' => $title]);
    }

    expect($course->sections()->pluck('position')->all())->toBe([1, 2, 3]);
});

test('a section title is required', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);

    $this->actingAs($instructor)
        ->post(route('sections.store', $course), ['title' => ''])
        ->assertSessionHasErrors('title');
});

test('a section can be moved up and down', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);

    $first = Section::factory()->atPosition(1)->create(['course_id' => $course->id]);
    $second = Section::factory()->atPosition(2)->create(['course_id' => $course->id]);
    $third = Section::factory()->atPosition(3)->create(['course_id' => $course->id]);

    $this->actingAs($instructor)
        ->patch(route('sections.move', $third), ['direction' => 'up'])
        ->assertRedirect();

    expect($course->sections()->pluck('id')->all())
        ->toBe([$first->id, $third->id, $second->id]);

    $this->actingAs($instructor)->patch(route('sections.move', $third), ['direction' => 'down']);

    expect($course->sections()->pluck('id')->all())
        ->toBe([$first->id, $second->id, $third->id]);
});

test('moving the first section up is a no-op', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);
    $first = Section::factory()->atPosition(1)->create(['course_id' => $course->id]);
    Section::factory()->atPosition(2)->create(['course_id' => $course->id]);

    $this->actingAs($instructor)
        ->patch(route('sections.move', $first), ['direction' => 'up'])
        ->assertSessionHasNoErrors();

    expect($first->refresh()->position)->toBe(1);
});

test('an unknown move direction is rejected', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    $this->actingAs($instructor)
        ->patch(route('sections.move', $section), ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');
});

test('a section only ever swaps with a section of the same course', function () {
    $instructor = User::factory()->instructor()->create();
    $mine = Section::factory()->atPosition(1)->create(['course_id' => ownedCourse($instructor)->id]);
    $theirs = Section::factory()->atPosition(2)->create();

    $this->actingAs($instructor)->patch(route('sections.move', $mine), ['direction' => 'down']);

    expect($mine->refresh()->position)->toBe(1)
        ->and($theirs->refresh()->position)->toBe(2);
});

test('deleting a section closes the gap in the sequence', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);

    $first = Section::factory()->atPosition(1)->create(['course_id' => $course->id]);
    $second = Section::factory()->atPosition(2)->create(['course_id' => $course->id]);
    $third = Section::factory()->atPosition(3)->create(['course_id' => $course->id]);

    $this->actingAs($instructor)->delete(route('sections.destroy', $second))->assertRedirect();

    expect($first->refresh()->position)->toBe(1)
        ->and($third->refresh()->position)->toBe(2);
});

test('deleting a section deletes its lessons', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);
    Lesson::factory()->count(3)->create(['section_id' => $section->id]);

    $this->actingAs($instructor)->delete(route('sections.destroy', $section));

    expect(Lesson::query()->count())->toBe(0);
});

test('deleting a course deletes its whole curriculum', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->count(2)->create(['section_id' => $section->id]);

    $this->actingAs($admin)->delete(route('courses.destroy', $course));

    expect(Section::query()->count())->toBe(0)
        ->and(Lesson::query()->count())->toBe(0);
});

test('an instructor can add a lesson to a section of their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    $this->actingAs($instructor)
        ->post(route('lessons.store', $section), [
            'title' => 'Instalasi Laravel',
            'content_type' => Lesson::TYPE_VIDEO,
            'content_url' => 'https://example.com/video',
            'duration_minutes' => 12,
        ])
        ->assertRedirect();

    expect(Lesson::sole()->position)->toBe(1);
});

test('an instructor cannot add a lesson to another instructors course', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create();

    $this->actingAs($instructor)
        ->post(route('lessons.store', $section), [
            'title' => 'Hijack',
            'content_type' => Lesson::TYPE_VIDEO,
        ])
        ->assertForbidden();

    expect(Lesson::query()->count())->toBe(0);
});

test('an unknown lesson content type is rejected', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    $this->actingAs($instructor)
        ->post(route('lessons.store', $section), [
            'title' => 'Bad',
            'content_type' => 'hologram',
        ])
        ->assertSessionHasErrors('content_type');
});

test('a lesson content url must be a url', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    $this->actingAs($instructor)
        ->post(route('lessons.store', $section), [
            'title' => 'Bad link',
            'content_type' => Lesson::TYPE_ARTICLE,
            'content_url' => 'not a url',
        ])
        ->assertSessionHasErrors('content_url');
});

test('lessons are appended and can be reordered within their section', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    $first = Lesson::factory()->atPosition(1)->create(['section_id' => $section->id]);
    $second = Lesson::factory()->atPosition(2)->create(['section_id' => $section->id]);

    $this->actingAs($instructor)
        ->patch(route('lessons.move', $second), ['direction' => 'up'])
        ->assertRedirect();

    expect($section->lessons()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('an instructor can rename a lesson of their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);
    $lesson = Lesson::factory()->create(['section_id' => $section->id]);

    $this->actingAs($instructor)
        ->put(route('lessons.update', $lesson), [
            'title' => 'Judul Baru',
            'content_type' => Lesson::TYPE_ARTICLE,
        ])
        ->assertRedirect();

    expect($lesson->refresh()->title)->toBe('Judul Baru');
});

test('an instructor cannot edit or delete a lesson of another instructors course', function () {
    $instructor = User::factory()->instructor()->create();
    $lesson = Lesson::factory()->create();

    $this->actingAs($instructor)
        ->put(route('lessons.update', $lesson), ['title' => 'Nope', 'content_type' => Lesson::TYPE_VIDEO])
        ->assertForbidden();

    $this->actingAs($instructor)->delete(route('lessons.destroy', $lesson))->assertForbidden();

    expect(Lesson::query()->count())->toBe(1);
});

test('deleting a lesson closes the gap in the sequence', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create(['course_id' => ownedCourse($instructor)->id]);

    Lesson::factory()->atPosition(1)->create(['section_id' => $section->id]);
    $second = Lesson::factory()->atPosition(2)->create(['section_id' => $section->id]);
    $third = Lesson::factory()->atPosition(3)->create(['section_id' => $section->id]);

    $this->actingAs($instructor)->delete(route('lessons.destroy', $second));

    expect($third->refresh()->position)->toBe(2);
});

test('the course detail page carries the curriculum', function () {
    $instructor = User::factory()->instructor()->create();
    $course = ownedCourse($instructor);
    $section = Section::factory()->atPosition(1)->create(['course_id' => $course->id]);
    Lesson::factory()->atPosition(1)->create(['section_id' => $section->id, 'duration_minutes' => 10]);
    Lesson::factory()->atPosition(2)->create(['section_id' => $section->id, 'duration_minutes' => 5]);

    $this->actingAs($instructor)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Show')
            ->has('sections', 1)
            ->has('sections.0.lessons', 2)
            ->where('sections.0.duration_minutes', 15));
});
