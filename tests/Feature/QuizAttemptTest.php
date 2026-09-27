<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;

/**
 * Build a quiz with one question of every answer mode in an enrolled course.
 *
 * Scoring: true/false 5 pts, single 10 pts, multiple tiers [4, 10] for two correct options.
 *
 * @return array{course: Course, quiz: Quiz, student: User, trueFalse: QuizQuestion, single: QuizQuestion, multiple: QuizQuestion}
 */
function quizForLearner(?int $timeLimitMinutes = null): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $quiz = Quiz::factory()->create(['section_id' => $section->id, 'time_limit_minutes' => $timeLimitMinutes]);
    $student = User::factory()->student()->create();
    Enrollment::factory()->for($student)->for($course)->create();

    $makeQuestion = function (int $position, string $mode, int $points, array $options, array $scores = []) use ($quiz): QuizQuestion {
        $question = $quiz->questions()->create([
            'question' => "Pertanyaan {$position}",
            'answer_mode' => $mode,
            'points' => $points,
            'position' => $position,
        ]);

        foreach ($options as $index => [$text, $isCorrect]) {
            $question->options()->create(['text' => $text, 'is_correct' => $isCorrect, 'position' => $index + 1]);
        }

        foreach ($scores as $index => $tierPoints) {
            $question->scores()->create(['correct_count' => $index + 1, 'points' => $tierPoints]);
        }

        return $question->load('options');
    };

    return [
        'course' => $course,
        'quiz' => $quiz,
        'student' => $student,
        'trueFalse' => $makeQuestion(1, QuizQuestion::MODE_TRUE_FALSE, 5, [['True', true], ['False', false]]),
        'single' => $makeQuestion(2, QuizQuestion::MODE_SINGLE, 10, [['A', false], ['B', true], ['C', false]]),
        'multiple' => $makeQuestion(3, QuizQuestion::MODE_MULTIPLE, 0, [['X', true], ['Y', true], ['Z', false]], [4, 10]),
    ];
}

function optionId(QuizQuestion $question, string $text): int
{
    return $question->options->firstWhere('text', $text)->id;
}

test('starting a quiz creates a timed attempt and resumes it on the next start', function () {
    ['course' => $course, 'quiz' => $quiz, 'student' => $student] = quizForLearner(30);

    $this->actingAs($student)->post(route('learn.quizzes.start', [$course, $quiz]));

    $attempt = QuizAttempt::sole();

    expect($attempt->user_id)->toBe($student->id)
        ->and($attempt->isSubmitted())->toBeFalse()
        ->and((int) round($attempt->started_at->diffInMinutes($attempt->expires_at)))->toBe(30);

    $this->actingAs($student)
        ->post(route('learn.quizzes.start', [$course, $quiz]))
        ->assertRedirect(route('learn.attempts.show', $attempt));

    expect(QuizAttempt::query()->count())->toBe(1);
});

test('an open attempt shows the questions without the answer key', function () {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner(30);
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->get(route('learn.attempts.show', $attempt))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('learn/Attempt')
            ->has('questions', 3)
            ->missing('questions.0.options.0.is_correct')
            ->where('attempt.seconds_remaining', fn (int $seconds) => $seconds > 1790 && $seconds <= 1800)
        );
});

test('submitting grades every answer mode', function () {
    ['quiz' => $quiz, 'student' => $student, 'trueFalse' => $trueFalse, 'single' => $single, 'multiple' => $multiple] = quizForLearner();
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->post(route('learn.attempts.submit', $attempt), [
            'answers' => [
                $trueFalse->id => [optionId($trueFalse, 'False')],
                $single->id => [optionId($single, 'B')],
                $multiple->id => [optionId($multiple, 'X'), optionId($multiple, 'Y')],
            ],
        ])
        ->assertRedirect(route('learn.attempts.show', $attempt));

    $attempt->refresh();

    expect($attempt->isSubmitted())->toBeTrue()
        ->and($attempt->score)->toBe(20)
        ->and($attempt->max_score)->toBe(25)
        ->and($attempt->is_late)->toBeFalse();

    $this->actingAs($student)
        ->get(route('learn.attempts.show', $attempt))
        ->assertInertia(fn ($page) => $page
            ->component('learn/AttemptResult')
            ->where('questions.0.is_correct', false)
            ->where('questions.1.points', 10)
            ->where('questions.2.points', 10)
            ->where('questions.2.options.2.is_correct', false)
        );
});

test('wrong picks on a multiple answer question cancel out right ones', function () {
    ['quiz' => $quiz, 'student' => $student, 'multiple' => $multiple] = quizForLearner();
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)->post(route('learn.attempts.submit', $attempt), [
        'answers' => [
            $multiple->id => [optionId($multiple, 'X'), optionId($multiple, 'Y'), optionId($multiple, 'Z')],
        ],
    ]);

    expect($attempt->refresh()->score)->toBe(4);
});

test('options from other questions are ignored', function () {
    ['quiz' => $quiz, 'student' => $student, 'single' => $single, 'multiple' => $multiple] = quizForLearner();
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)->post(route('learn.attempts.submit', $attempt), [
        'answers' => [$single->id => [optionId($multiple, 'X')]],
    ]);

    expect($attempt->refresh()->score)->toBe(0)
        ->and($attempt->answers[(string) $single->id])->toBe([]);
});

test('answers sent after the time limit are not counted', function () {
    ['quiz' => $quiz, 'student' => $student, 'single' => $single] = quizForLearner(10);
    $attempt = QuizAttempt::factory()->for($student)->for($quiz)->expiredMinutesAgo(5)->create();

    $this->actingAs($student)->post(route('learn.attempts.submit', $attempt), [
        'answers' => [$single->id => [optionId($single, 'B')]],
    ]);

    $attempt->refresh();

    expect($attempt->isSubmitted())->toBeTrue()
        ->and($attempt->is_late)->toBeTrue()
        ->and($attempt->score)->toBe(0);
});

test('an attempt whose time ran out is closed when the student comes back', function () {
    ['course' => $course, 'quiz' => $quiz, 'student' => $student] = quizForLearner(10);
    $attempt = QuizAttempt::factory()->for($student)->for($quiz)->expiredMinutesAgo(5)->create();

    $this->actingAs($student)
        ->get(route('learn.quizzes.show', [$course, $quiz]))
        ->assertInertia(fn ($page) => $page
            ->where('openAttemptId', null)
            ->has('attempts', 1)
            ->where('attempts.0.is_late', true)
        );

    expect($attempt->refresh()->isSubmitted())->toBeTrue();
});

test('a submitted attempt cannot be submitted again', function () {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner();
    $attempt = QuizAttempt::factory()->for($student)->for($quiz)->submitted(5, 25)->create();

    $this->actingAs($student)
        ->post(route('learn.attempts.submit', $attempt), ['answers' => []])
        ->assertForbidden();

    expect($attempt->refresh()->score)->toBe(5);
});

test('students cannot see or submit someone else\'s attempt', function () {
    ['course' => $course, 'quiz' => $quiz, 'student' => $student] = quizForLearner();
    $attempt = QuizAttempt::start($student, $quiz);
    $classmate = User::factory()->student()->create();
    Enrollment::factory()->for($classmate)->for($course)->create();

    $this->actingAs($classmate)->get(route('learn.attempts.show', $attempt))->assertForbidden();
    $this->actingAs($classmate)->post(route('learn.attempts.submit', $attempt))->assertForbidden();
});

test('students without an enrollment cannot start a quiz', function () {
    ['course' => $course, 'quiz' => $quiz] = quizForLearner();

    $this->actingAs(User::factory()->student()->create())
        ->post(route('learn.quizzes.start', [$course, $quiz]))
        ->assertForbidden();

    expect(QuizAttempt::query()->count())->toBe(0);
});

test('a submitted quiz counts towards course progress', function () {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner();
    QuizAttempt::factory()->for($student)->for($quiz)->submitted(10, 25)->create();

    $this->actingAs($student)
        ->get(route('my-courses.index'))
        ->assertInertia(fn ($page) => $page
            ->where('enrollments.0.progress.completed', 1)
            ->where('enrollments.0.progress.total', 1)
            ->where('enrollments.0.progress.percent', 100)
        );
});

/**
 * Add a short answer question worth 6 points to the quiz.
 */
function shortAnswerQuestion(Quiz $quiz, array $accepted = ['Jakarta', 'DKI Jakarta']): QuizQuestion
{
    $question = $quiz->questions()->create([
        'question' => 'Ibu kota Indonesia?',
        'answer_mode' => QuizQuestion::MODE_SHORT_ANSWER,
        'points' => 6,
        'position' => $quiz->questions()->count() + 1,
    ]);

    foreach ($accepted as $index => $text) {
        $question->options()->create(['text' => $text, 'is_correct' => true, 'position' => $index + 1]);
    }

    return $question->load('options');
}

test('an open attempt hides the accepted answers of a short answer question', function () {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner();
    shortAnswerQuestion($quiz);
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->get(route('learn.attempts.show', $attempt))
        ->assertInertia(fn ($page) => $page
            ->component('learn/Attempt')
            ->where('questions.3.answer_mode', QuizQuestion::MODE_SHORT_ANSWER)
            ->where('questions.3.options', [])
        );
});

test('a short answer matches an accepted answer regardless of case, spacing and end punctuation', function (string $typed, bool $isCorrect) {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner();
    $question = shortAnswerQuestion($quiz);
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->post(route('learn.attempts.submit', $attempt), ['answers' => [$question->id => $typed]])
        ->assertSessionHasNoErrors();

    $attempt->refresh();

    expect($attempt->score)->toBe($isCorrect ? 6 : 0)
        ->and($attempt->max_score)->toBe(31)
        ->and($attempt->answers[(string) $question->id])->toBe(trim($typed));

    $this->actingAs($student)
        ->get(route('learn.attempts.show', $attempt))
        ->assertInertia(fn ($page) => $page
            ->component('learn/AttemptResult')
            ->where('questions.3.is_correct', $isCorrect)
            ->where('questions.3.text_answer', trim($typed))
            ->where('questions.3.options.1.text', 'DKI Jakarta')
        );
})->with([
    'exact' => ['Jakarta', true],
    'case and spacing' => ['  dki   JAKARTA ', true],
    'end punctuation' => ['Jakarta.', true],
    'different answer' => ['Bandung', false],
    'partial answer' => ['Jak', false],
    'blank' => ['   ', false],
]);

test('a short answer longer than the limit is rejected', function () {
    ['quiz' => $quiz, 'student' => $student] = quizForLearner();
    $question = shortAnswerQuestion($quiz);
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->post(route('learn.attempts.submit', $attempt), [
            'answers' => [$question->id => str_repeat('a', QuizQuestion::MAX_SHORT_ANSWER_LENGTH + 1)],
        ])
        ->assertSessionHasErrors("answers.{$question->id}");

    expect($attempt->refresh()->isSubmitted())->toBeFalse();
});

test('option ids sent for a short answer question and text sent for a choice question score nothing', function () {
    ['quiz' => $quiz, 'student' => $student, 'single' => $single] = quizForLearner();
    $question = shortAnswerQuestion($quiz);
    $attempt = QuizAttempt::start($student, $quiz);

    $this->actingAs($student)
        ->post(route('learn.attempts.submit', $attempt), [
            'answers' => [
                $question->id => [$question->options->first()->id],
                $single->id => 'B',
            ],
        ]);

    expect($attempt->refresh()->score)->toBe(0);
});
