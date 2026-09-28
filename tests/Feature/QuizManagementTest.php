<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionScore;
use App\Models\Section;
use App\Models\User;

function sectionFor(User $instructor): Section
{
    return Section::factory()->create([
        'course_id' => Course::factory()->ownedBy($instructor)->create()->id,
    ]);
}

function quizFor(User $instructor): Quiz
{
    return Quiz::factory()->create(['section_id' => sectionFor($instructor)->id]);
}

function singleQuestionPayload(array $overrides = []): array
{
    return array_merge([
        'question' => 'Apa itu MVC?',
        'answer_mode' => QuizQuestion::MODE_SINGLE,
        'points' => 10,
        'options' => [
            ['text' => 'Model View Controller', 'is_correct' => true],
            ['text' => 'My Very Own Code', 'is_correct' => false],
            ['text' => 'Multi Version Control', 'is_correct' => false],
        ],
    ], $overrides);
}

function multipleQuestionPayload(array $overrides = []): array
{
    return array_merge([
        'question' => 'Mana saja perintah Artisan?',
        'answer_mode' => QuizQuestion::MODE_MULTIPLE,
        'options' => [
            ['text' => 'php artisan migrate', 'is_correct' => true],
            ['text' => 'php artisan serve', 'is_correct' => true],
            ['text' => 'php artisan fly', 'is_correct' => false],
        ],
        'scores' => [4, 10],
    ], $overrides);
}

test('quiz is no longer a lesson content type', function () {
    expect(Lesson::CONTENT_TYPES)->toBe(['video', 'article']);
});

test('a lesson can no longer be created as a quiz', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);

    $this->actingAs($instructor)
        ->post(route('lessons.store', $section), [
            'title' => 'Kuis',
            'content_type' => 'quiz',
        ])
        ->assertSessionHasErrors('content_type');
});

test('guests are redirected from the quiz list', function () {
    $this->get(route('quizzes.index'))->assertRedirect(route('login'));
});

test('a student cannot reach the quiz list', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('quizzes.index'))->assertRedirect(route('home'));
});

test('an instructor only sees quizzes from their own courses', function () {
    $instructor = User::factory()->instructor()->create();
    quizFor($instructor);
    Quiz::factory()->create();

    $this->actingAs($instructor)
        ->get(route('quizzes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('quizzes/Index')
            ->has('quizzes.data', 1));
});

test('an instructor can add a quiz to a section of their own course', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quizzes.store', $section), [
            'title' => 'Kuis Bab 1',
            'description' => 'Penilaian bab pertama.',
        ])
        ->assertRedirect();

    expect(Quiz::sole()->position)->toBe(1);
});

test('an instructor cannot add a quiz to another instructors course', function () {
    $instructor = User::factory()->instructor()->create();
    $section = Section::factory()->create();

    $this->actingAs($instructor)
        ->post(route('quizzes.store', $section), ['title' => 'Hijack'])
        ->assertForbidden();

    expect(Quiz::query()->count())->toBe(0);
});

test('quizzes can be reordered inside their section', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);

    $first = Quiz::factory()->atPosition(1)->create(['section_id' => $section->id]);
    $second = Quiz::factory()->atPosition(2)->create(['section_id' => $section->id]);

    $this->actingAs($instructor)
        ->patch(route('quizzes.move', $second), ['direction' => 'up'])
        ->assertRedirect();

    expect($section->quizzes()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('a single answer question stores its option and flat score', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), singleQuestionPayload())
        ->assertRedirect();

    $question = QuizQuestion::sole();

    expect($question->answer_mode)->toBe(QuizQuestion::MODE_SINGLE)
        ->and($question->points)->toBe(10)
        ->and($question->options()->count())->toBe(3)
        ->and($question->options()->where('is_correct', true)->count())->toBe(1)
        ->and($question->scores()->count())->toBe(0)
        ->and($question->maxPoints())->toBe(10);
});

test('a single answer question needs exactly one correct option', function (array $flags) {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $options = [];
    foreach ($flags as $index => $isCorrect) {
        $options[] = ['text' => "Opsi {$index}", 'is_correct' => $isCorrect];
    }

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), singleQuestionPayload(['options' => $options]))
        ->assertSessionHasErrors('options');

    expect(QuizQuestion::query()->count())->toBe(0);
})->with([
    'none correct' => [[false, false, false]],
    'two correct' => [[true, true, false]],
]);

test('a multiple answer question stores a score for each correct answer count', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), multipleQuestionPayload())
        ->assertRedirect();

    $question = QuizQuestion::sole();

    expect($question->answer_mode)->toBe(QuizQuestion::MODE_MULTIPLE)
        ->and($question->points)->toBe(0)
        ->and($question->scores()->pluck('points', 'correct_count')->all())
        ->toBe([1 => 4, 2 => 10])
        ->and($question->maxPoints())->toBe(10);
});

test('a multiple answer question needs at least two correct options', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), multipleQuestionPayload([
            'options' => [
                ['text' => 'Satu-satunya benar', 'is_correct' => true],
                ['text' => 'Salah', 'is_correct' => false],
            ],
            'scores' => [5],
        ]))
        ->assertSessionHasErrors('options');

    expect(QuizQuestion::query()->count())->toBe(0);
});

test('the number of scores must match the number of correct options', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), multipleQuestionPayload(['scores' => [5]]))
        ->assertSessionHasErrors('scores');

    expect(QuizQuestion::query()->count())->toBe(0);
});

test('a question needs at least two options', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), singleQuestionPayload([
            'options' => [['text' => 'Hanya satu', 'is_correct' => true]],
        ]))
        ->assertSessionHasErrors('options');
});

test('updating a question replaces its options and answer key', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)->post(route('quiz-questions.store', $quiz), singleQuestionPayload());
    $question = QuizQuestion::sole();

    $this->actingAs($instructor)
        ->put(route('quiz-questions.update', $question), multipleQuestionPayload())
        ->assertRedirect();

    expect($question->refresh()->answer_mode)->toBe(QuizQuestion::MODE_MULTIPLE)
        ->and($question->points)->toBe(0)
        ->and(QuizOption::query()->count())->toBe(3)
        ->and($question->scores()->pluck('points')->all())->toBe([4, 10]);
});

test('switching a question back to single answer clears the scoring tiers', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)->post(route('quiz-questions.store', $quiz), multipleQuestionPayload());
    $question = QuizQuestion::sole();

    $this->actingAs($instructor)->put(route('quiz-questions.update', $question), singleQuestionPayload());

    expect(QuizQuestionScore::query()->count())->toBe(0)
        ->and($question->refresh()->points)->toBe(10);
});

test('questions can be reordered inside their quiz', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $first = QuizQuestion::factory()->atPosition(1)->create(['quiz_id' => $quiz->id]);
    $second = QuizQuestion::factory()->atPosition(2)->create(['quiz_id' => $quiz->id]);

    $this->actingAs($instructor)
        ->patch(route('quiz-questions.move', $second), ['direction' => 'up'])
        ->assertRedirect();

    expect($quiz->questions()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('an instructor cannot touch a question from another instructors quiz', function () {
    $instructor = User::factory()->instructor()->create();
    $question = QuizQuestion::factory()->create();

    $this->actingAs($instructor)
        ->put(route('quiz-questions.update', $question), singleQuestionPayload())
        ->assertForbidden();

    $this->actingAs($instructor)
        ->delete(route('quiz-questions.destroy', $question))
        ->assertForbidden();
});

test('an admin can build a quiz on any instructors course', function () {
    $admin = User::factory()->admin()->create();
    $quiz = Quiz::factory()->create();

    $this->actingAs($admin)
        ->post(route('quiz-questions.store', $quiz), singleQuestionPayload())
        ->assertRedirect();

    expect(QuizQuestion::query()->count())->toBe(1);
});

test('deleting a question closes the gap in the sequence', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    QuizQuestion::factory()->atPosition(1)->create(['quiz_id' => $quiz->id]);
    $second = QuizQuestion::factory()->atPosition(2)->create(['quiz_id' => $quiz->id]);
    $third = QuizQuestion::factory()->atPosition(3)->create(['quiz_id' => $quiz->id]);

    $this->actingAs($instructor)->delete(route('quiz-questions.destroy', $second));

    expect($third->refresh()->position)->toBe(2);
});

test('deleting a quiz removes its questions options and scores', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)->post(route('quiz-questions.store', $quiz), multipleQuestionPayload());

    $this->actingAs($instructor)->delete(route('quizzes.destroy', $quiz))->assertRedirect();

    expect(Quiz::query()->count())->toBe(0)
        ->and(QuizQuestion::query()->count())->toBe(0)
        ->and(QuizOption::query()->count())->toBe(0)
        ->and(QuizQuestionScore::query()->count())->toBe(0);
});

test('deleting a section removes its quizzes', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);
    Quiz::factory()->create(['section_id' => $section->id]);

    $this->actingAs($instructor)->delete(route('sections.destroy', $section));

    expect(Quiz::query()->count())->toBe(0);
});

test('the quiz list reports total points per quiz', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)->post(route('quiz-questions.store', $quiz), singleQuestionPayload());
    $this->actingAs($instructor)->post(route('quiz-questions.store', $quiz), multipleQuestionPayload());

    $this->actingAs($instructor)
        ->get(route('quizzes.index'))
        ->assertInertia(fn ($page) => $page
            ->where('quizzes.data.0.questions_count', 2)
            ->where('quizzes.data.0.total_points', 20));
});

test('a true or false question stores the fixed options and a flat score', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), [
            'question' => 'Laravel adalah framework PHP.',
            'answer_mode' => QuizQuestion::MODE_TRUE_FALSE,
            'points' => 5,
            'options' => [
                ['text' => 'Benar sekali', 'is_correct' => false],
                ['text' => 'Salah', 'is_correct' => true],
                ['text' => 'Mungkin', 'is_correct' => false],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $question = QuizQuestion::sole();

    expect($question->answer_mode)->toBe(QuizQuestion::MODE_TRUE_FALSE)
        ->and($question->points)->toBe(5)
        ->and($question->maxPoints())->toBe(5)
        ->and($question->scores()->count())->toBe(0)
        ->and($question->options()->pluck('is_correct', 'text')->map(fn ($isCorrect) => (bool) $isCorrect)->all())
        ->toBe(['True' => false, 'False' => true]);
});

test('a true or false question needs a correct answer', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), [
            'question' => 'Laravel adalah framework PHP.',
            'answer_mode' => QuizQuestion::MODE_TRUE_FALSE,
            'points' => 5,
            'options' => [],
        ])
        ->assertSessionHasErrors('options');

    expect(QuizQuestion::query()->count())->toBe(0);
});

test('a quiz can be given a time limit and have it removed', function () {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quizzes.store', $section), [
            'title' => 'Kuis Bab 1',
            'time_limit_minutes' => 30,
        ])
        ->assertSessionHasNoErrors();

    $quiz = Quiz::sole();

    expect($quiz->time_limit_minutes)->toBe(30);

    $this->actingAs($instructor)
        ->put(route('quizzes.update', $quiz), [
            'title' => 'Kuis Bab 1',
            'time_limit_minutes' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($quiz->refresh()->time_limit_minutes)->toBeNull();
});

test('a quiz time limit must be within range', function (int $minutes) {
    $instructor = User::factory()->instructor()->create();
    $section = sectionFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quizzes.store', $section), [
            'title' => 'Kuis Bab 1',
            'time_limit_minutes' => $minutes,
        ])
        ->assertSessionHasErrors('time_limit_minutes');

    expect(Quiz::query()->count())->toBe(0);
})->with([
    'zero' => [0],
    'too long' => [Quiz::MAX_TIME_LIMIT_MINUTES + 1],
]);

test('a short answer question stores every accepted answer as correct', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), [
            'question' => 'Perintah Artisan untuk menjalankan migrasi?',
            'answer_mode' => QuizQuestion::MODE_SHORT_ANSWER,
            'points' => 8,
            'options' => [
                ['text' => 'php artisan migrate', 'is_correct' => false],
                ['text' => 'artisan migrate'],
            ],
            'scores' => [3, 4],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $question = QuizQuestion::sole();

    expect($question->answer_mode)->toBe(QuizQuestion::MODE_SHORT_ANSWER)
        ->and($question->isShortAnswer())->toBeTrue()
        ->and($question->points)->toBe(8)
        ->and($question->maxPoints())->toBe(8)
        ->and($question->scores()->count())->toBe(0)
        ->and($question->options()->where('is_correct', true)->pluck('text')->all())
        ->toBe(['php artisan migrate', 'artisan migrate']);
});

test('a short answer question needs one accepted answer with a letter or number', function (array $options, string $errorKey) {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->post(route('quiz-questions.store', $quiz), [
            'question' => 'Ibu kota Indonesia?',
            'answer_mode' => QuizQuestion::MODE_SHORT_ANSWER,
            'points' => 5,
            'options' => $options,
        ])
        ->assertSessionHasErrors($errorKey);

    expect(QuizQuestion::query()->count())->toBe(0);
})->with([
    'no answers' => [[], 'options'],
    'only punctuation' => [[['text' => ' ?! ']], 'options.0.text'],
]);

test('the quiz editor offers the short answer mode', function () {
    $instructor = User::factory()->instructor()->create();
    $quiz = quizFor($instructor);

    $this->actingAs($instructor)
        ->get(route('quizzes.edit', ['course' => $quiz->section->course, 'quiz' => $quiz]))
        ->assertInertia(fn ($page) => $page
            ->where('answerModes', fn ($modes) => collect($modes)->contains(QuizQuestion::MODE_SHORT_ANSWER))
        );
});
