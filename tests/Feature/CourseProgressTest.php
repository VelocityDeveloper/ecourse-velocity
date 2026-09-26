<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;

/**
 * A course with two lessons and a quiz, owned by an instructor.
 *
 * @return array{instructor: User, course: Course, lessons: list<Lesson>, quiz: Quiz}
 */
function monitoredCourse(): array
{
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->ownedBy($instructor)->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);

    return [
        'instructor' => $instructor,
        'course' => $course,
        'lessons' => [
            Lesson::factory()->atPosition(1)->create(['section_id' => $section->id]),
            Lesson::factory()->atPosition(2)->create(['section_id' => $section->id]),
        ],
        'quiz' => Quiz::factory()->create(['section_id' => $section->id]),
    ];
}

test('the instructor sees how far every enrolled student has got', function () {
    ['instructor' => $instructor, 'course' => $course, 'lessons' => $lessons, 'quiz' => $quiz] = monitoredCourse();

    $busy = User::factory()->student()->create(['name' => 'Rajin']);
    Enrollment::factory()->for($busy)->for($course)->create(['last_accessed_at' => now()]);
    $busy->completedLessons()->attach([$lessons[0]->id, $lessons[1]->id]);
    QuizAttempt::factory()->for($busy)->for($quiz)->submitted(6, 10)->create();
    QuizAttempt::factory()->for($busy)->for($quiz)->submitted(9, 10)->create();

    $idle = User::factory()->student()->create(['name' => 'Santai']);
    Enrollment::factory()->for($idle)->for($course)->create();

    Enrollment::factory()->for($course)->cancelled()->create();

    $this->actingAs($instructor)
        ->get(route('courses.progress.index', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Progress')
            ->where('summary.students', 2)
            ->where('summary.completed', 1)
            ->where('summary.inactive', 1)
            ->where('summary.average_percent', 50)
            ->where('students.0.student.id', $busy->id)
            ->where('students.0.percent', 100)
            ->where('students.0.lessons_completed', 2)
            ->where('students.0.quizzes_attempted', 1)
            ->where('students.0.quiz_score_percent', 90)
            ->where('students.1.student.id', $idle->id)
            ->where('students.1.percent', 0)
        );
});

test('unanswered questions are listed for the instructor', function () {
    ['instructor' => $instructor, 'course' => $course, 'lessons' => $lessons] = monitoredCourse();
    $open = LessonQuestion::factory()->for($lessons[0])->create();
    $answered = LessonQuestion::factory()->for($lessons[0])->create();
    LessonReply::factory()->for($answered, 'question')->create();

    $this->actingAs($instructor)
        ->get(route('courses.progress.index', $course))
        ->assertInertia(fn ($page) => $page
            ->has('unansweredQuestions', 1)
            ->where('unansweredQuestions.0.id', $open->id)
        );
});

test('the instructor can drill into one student\'s progress', function () {
    ['instructor' => $instructor, 'course' => $course, 'lessons' => $lessons, 'quiz' => $quiz] = monitoredCourse();
    $student = User::factory()->student()->create();
    Enrollment::factory()->for($student)->for($course)->create();
    $student->completedLessons()->attach($lessons[0]->id);
    QuizAttempt::factory()->for($student)->for($quiz)->submitted(4, 10)->create();

    $this->actingAs($instructor)
        ->get(route('courses.progress.show', [$course, $student]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/ProgressStudent')
            ->where('student.id', $student->id)
            ->whereNot('sections.0.lessons.0.completed_at', null)
            ->where('sections.0.lessons.1.completed_at', null)
            ->where('sections.0.quizzes.0.attempts', 1)
            ->where('sections.0.quizzes.0.best_score', 4)
        );
});

test('progress of someone who never enrolled cannot be opened', function () {
    ['instructor' => $instructor, 'course' => $course] = monitoredCourse();

    $this->actingAs($instructor)
        ->get(route('courses.progress.show', [$course, User::factory()->student()->create()]))
        ->assertNotFound();
});

test('other instructors and students cannot monitor the course', function () {
    ['course' => $course] = monitoredCourse();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('courses.progress.index', $course))
        ->assertForbidden();

    $this->actingAs(User::factory()->student()->create())
        ->get(route('courses.progress.index', $course))
        ->assertRedirect(route('home'));
});

test('admins can monitor any course', function () {
    ['course' => $course] = monitoredCourse();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.progress.index', $course))
        ->assertOk();
});
