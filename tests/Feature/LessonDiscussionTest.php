<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\Section;
use App\Models\User;

/**
 * A lesson in a published course with its instructor and one enrolled student.
 *
 * @return array{course: Course, lesson: Lesson, instructor: User, student: User}
 */
function discussionScenario(): array
{
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();
    $lesson = Lesson::factory()->create(['section_id' => Section::factory()->create(['course_id' => $course->id])->id]);
    $student = User::factory()->student()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    return compact('course', 'lesson', 'instructor', 'student');
}

test('an enrolled student can ask a question in a lesson', function () {
    ['course' => $course, 'lesson' => $lesson, 'student' => $student] = discussionScenario();

    $this->actingAs($student)
        ->post(route('learn.questions.store', [$course, $lesson]), ['body' => 'Kenapa pakai eager loading?'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $question = LessonQuestion::query()->sole();

    expect($question->user_id)->toBe($student->id)
        ->and($question->lesson_id)->toBe($lesson->id);
});

test('questions must have some content', function () {
    ['course' => $course, 'lesson' => $lesson, 'student' => $student] = discussionScenario();

    $this->actingAs($student)
        ->post(route('learn.questions.store', [$course, $lesson]), ['body' => ''])
        ->assertSessionHasErrors('body');

    expect(LessonQuestion::query()->count())->toBe(0);
});

test('students without access cannot join the discussion', function () {
    ['course' => $course, 'lesson' => $lesson] = discussionScenario();
    $question = LessonQuestion::factory()->for($lesson)->create();
    $outsider = User::factory()->student()->create();

    $this->actingAs($outsider)
        ->post(route('learn.questions.store', [$course, $lesson]), ['body' => 'Halo semua'])
        ->assertForbidden();

    $this->actingAs($outsider)
        ->post(route('learn.replies.store', $question), ['body' => 'Saya juga'])
        ->assertForbidden();
});

test('the instructor reply is flagged as staff in the lesson discussion', function () {
    ['course' => $course, 'lesson' => $lesson, 'instructor' => $instructor, 'student' => $student] = discussionScenario();
    $question = LessonQuestion::factory()->for($lesson)->for($student)->create();

    $this->actingAs($instructor)
        ->post(route('learn.replies.store', $question), ['body' => 'Supaya tidak N+1 query.'])
        ->assertRedirect();

    $this->actingAs($student)
        ->get(route('learn.lessons.show', [$course, $lesson]))
        ->assertInertia(fn ($page) => $page
            ->has('discussion', 1)
            ->where('discussion.0.author.is_staff', false)
            ->where('discussion.0.can_delete', true)
            ->has('discussion.0.replies', 1)
            ->where('discussion.0.replies.0.author.is_staff', true)
            ->where('discussion.0.replies.0.can_delete', false)
        );
});

test('authors and the course instructor can delete posts but classmates cannot', function () {
    ['course' => $course, 'lesson' => $lesson, 'instructor' => $instructor, 'student' => $student] = discussionScenario();
    $question = LessonQuestion::factory()->for($lesson)->for($student)->create();
    $reply = LessonReply::factory()->for($question, 'question')->for($student)->create();
    $classmate = User::factory()->student()->create();
    Enrollment::factory()->for($classmate)->for($course)->create();

    $this->actingAs($classmate)->delete(route('learn.questions.destroy', $question))->assertForbidden();
    $this->actingAs($classmate)->delete(route('learn.replies.destroy', $reply))->assertForbidden();

    $this->actingAs($student)->delete(route('learn.replies.destroy', $reply))->assertRedirect();
    expect(LessonReply::query()->count())->toBe(0);

    $this->actingAs($instructor)->delete(route('learn.questions.destroy', $question))->assertRedirect();
    expect(LessonQuestion::query()->count())->toBe(0);
});

test('deleting a question removes its replies', function () {
    ['lesson' => $lesson, 'student' => $student] = discussionScenario();
    $question = LessonQuestion::factory()->for($lesson)->for($student)->create();
    LessonReply::factory()->count(2)->for($question, 'question')->create();

    $this->actingAs($student)->delete(route('learn.questions.destroy', $question));

    expect(LessonReply::query()->count())->toBe(0);
});

test('the discussion reads like a chat, oldest question first and newest last', function () {
    ['course' => $course, 'lesson' => $lesson, 'student' => $student] = discussionScenario();
    LessonQuestion::factory()->for($lesson)->create(['body' => 'Pertanyaan lama', 'created_at' => now()->subDay()]);
    LessonQuestion::factory()->for($lesson)->create(['body' => 'Pertanyaan baru', 'created_at' => now()]);

    $this->actingAs($student)
        ->get(route('learn.lessons.show', [$course, $lesson]))
        ->assertInertia(fn ($page) => $page
            ->where('discussion.0.body', 'Pertanyaan lama')
            ->where('discussion.1.body', 'Pertanyaan baru'));
});
