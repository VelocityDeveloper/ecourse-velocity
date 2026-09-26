<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;

/**
 * A published course with two lessons, and a student enrolled in it.
 *
 * @return array{course: Course, first: Lesson, second: Lesson, student: User, enrollment: Enrollment}
 */
function studyScenario(): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $student = User::factory()->student()->create();

    return [
        'course' => $course,
        'first' => Lesson::factory()->atPosition(1)->create(['section_id' => $section->id]),
        'second' => Lesson::factory()->atPosition(2)->create(['section_id' => $section->id]),
        'student' => $student,
        'enrollment' => Enrollment::factory()->for($student)->for($course)->create(),
    ];
}

test('opening a lesson remembers it and resuming returns there', function () {
    ['course' => $course, 'second' => $second, 'student' => $student, 'enrollment' => $enrollment] = studyScenario();

    $this->actingAs($student)->get(route('learn.lessons.show', [$course, $second]))->assertOk();

    $enrollment->refresh();

    expect($enrollment->last_lesson_id)->toBe($second->id)
        ->and($enrollment->last_accessed_at)->not->toBeNull();

    $this->actingAs($student)
        ->get(route('learn.show', $course))
        ->assertRedirect(route('learn.lessons.show', [$course, $second]));
});

test('resuming skips the last lesson once it is finished', function () {
    ['course' => $course, 'first' => $first, 'second' => $second, 'student' => $student, 'enrollment' => $enrollment] = studyScenario();
    $enrollment->update(['last_lesson_id' => $first->id]);
    $student->completedLessons()->attach($first->id);

    $this->actingAs($student)
        ->get(route('learn.show', $course))
        ->assertRedirect(route('learn.lessons.show', [$course, $second]));
});

test('a student can save, update and clear a lesson note', function () {
    ['course' => $course, 'first' => $first, 'student' => $student] = studyScenario();

    $this->actingAs($student)
        ->put(route('learn.lessons.note.update', [$course, $first]), ['body' => 'Ingat: Eloquent itu ORM'])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->put(route('learn.lessons.note.update', [$course, $first]), ['body' => 'Catatan baru']);

    expect(LessonNote::query()->sole()->body)->toBe('Catatan baru');

    $this->actingAs($student)
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertInertia(fn ($page) => $page->where('lesson.note', 'Catatan baru'));

    $this->actingAs($student)
        ->put(route('learn.lessons.note.update', [$course, $first]), ['body' => '  ']);

    expect(LessonNote::query()->count())->toBe(0);
});

test('notes stay private to their author', function () {
    ['course' => $course, 'first' => $first, 'student' => $student] = studyScenario();
    LessonNote::factory()->for($student)->for($first)->create(['body' => 'Rahasia']);
    $classmate = User::factory()->student()->create();
    Enrollment::factory()->for($classmate)->for($course)->create();

    $this->actingAs($classmate)
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertInertia(fn ($page) => $page->where('lesson.note', null));

    $this->actingAs($classmate)
        ->get(route('learning.notes'))
        ->assertInertia(fn ($page) => $page->has('notes', 0));
});

test('the notes page lists and searches the student\'s notes', function () {
    ['first' => $first, 'second' => $second, 'student' => $student] = studyScenario();
    LessonNote::factory()->for($student)->for($first)->create(['body' => 'Relasi hasMany']);
    LessonNote::factory()->for($student)->for($second)->create(['body' => 'Middleware auth']);

    $this->actingAs($student)
        ->get(route('learning.notes'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('learning/Notes')->has('notes', 2));

    $this->actingAs($student)
        ->get(route('learning.notes', ['search' => 'hasMany']))
        ->assertInertia(fn ($page) => $page
            ->has('notes', 1)
            ->where('notes.0.lesson.id', $first->id)
        );
});

test('students cannot write notes on lessons of courses they are not enrolled in', function () {
    ['course' => $course, 'first' => $first] = studyScenario();

    $this->actingAs(User::factory()->student()->create())
        ->put(route('learn.lessons.note.update', [$course, $first]), ['body' => 'Hai'])
        ->assertForbidden();
});

test('a student can bookmark and unbookmark lessons', function () {
    ['course' => $course, 'first' => $first, 'student' => $student] = studyScenario();

    $this->actingAs($student)->post(route('learn.lessons.bookmark.store', [$course, $first]))->assertRedirect();
    $this->actingAs($student)->post(route('learn.lessons.bookmark.store', [$course, $first]));

    expect($student->bookmarkedLessons()->count())->toBe(1);

    $this->actingAs($student)
        ->get(route('learn.lessons.show', [$course, $first]))
        ->assertInertia(fn ($page) => $page
            ->where('lesson.is_bookmarked', true)
            ->where('outline.sections.0.items.0.is_bookmarked', true)
        );

    $this->actingAs($student)
        ->get(route('learning.bookmarks'))
        ->assertInertia(fn ($page) => $page->component('learning/Bookmarks')->has('bookmarks', 1));

    $this->actingAs($student)->delete(route('learn.lessons.bookmark.destroy', [$course, $first]));

    expect($student->bookmarkedLessons()->count())->toBe(0);
});

test('bookmarks from courses the student left are hidden', function () {
    ['course' => $course, 'first' => $first, 'student' => $student, 'enrollment' => $enrollment] = studyScenario();
    $student->bookmarkedLessons()->attach($first->id);
    $enrollment->cancel($student);

    $this->actingAs($student)
        ->get(route('learning.bookmarks'))
        ->assertInertia(fn ($page) => $page->has('bookmarks', 0));
});

test('the learning dashboard summarises the student\'s progress', function () {
    ['course' => $course, 'first' => $first, 'student' => $student] = studyScenario();
    $quiz = Quiz::factory()->create(['section_id' => $first->section_id]);
    QuizAttempt::factory()->for($student)->for($quiz)->submitted(8, 10)->create();

    $this->actingAs($student)->get(route('learn.lessons.show', [$course, $first]));
    $this->actingAs($student)->post(route('learn.lessons.complete', [$course, $first]));

    $this->actingAs($student)
        ->get(route('learning.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('learning/Dashboard')
            ->where('resume.course.id', $course->id)
            ->where('resume.last_lesson.id', $first->id)
            ->where('resume.progress.completed', 2)
            ->where('resume.progress.total', 3)
            ->where('stats.active_courses', 1)
            ->where('stats.lessons_completed', 1)
            ->where('stats.average_quiz_score', 80)
            ->has('activity', 2)
        );
});

test('guests cannot open the learning dashboard', function () {
    $this->get(route('learning.dashboard'))->assertRedirect(route('login'));
});
