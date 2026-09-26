<?php

namespace App\Http\Controllers;

use App\Actions\GradeQuizAttempt;
use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    /**
     * Show the attempt: the questions while it is open, the result once it is handed in.
     */
    public function show(QuizAttempt $attempt, GradeQuizAttempt $grade): Response
    {
        Gate::authorize('view', $attempt);

        if (! $attempt->isSubmitted() && $attempt->isPastDeadline()) {
            $attempt->handIn([], $grade);
        }

        $quiz = $attempt->quiz->load(['section.course:id,title', 'questions.options', 'questions.scores']);

        $context = [
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at->toIso8601String(),
                'expires_at' => $attempt->expires_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'seconds_remaining' => $attempt->expires_at === null
                    ? null
                    : max(0, (int) now()->diffInSeconds($attempt->expires_at, false)),
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'is_late' => $attempt->is_late,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'time_limit_minutes' => $quiz->time_limit_minutes,
            ],
            'course' => [
                'id' => $quiz->section->course->id,
                'title' => $quiz->section->course->title,
            ],
        ];

        if (! $attempt->isSubmitted()) {
            return Inertia::render('learn/Attempt', [
                ...$context,
                'questions' => $quiz->questions
                    ->map(fn (QuizQuestion $question): array => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'answer_mode' => $question->answer_mode,
                        'max_points' => $question->maxPoints(),
                        'options' => $question->options
                            ->map(fn (QuizOption $option): array => ['id' => $option->id, 'text' => $option->text])
                            ->all(),
                    ])
                    ->all(),
            ]);
        }

        $answers = $attempt->answers ?? [];
        $breakdown = $grade($quiz, $answers)['questions'];

        return Inertia::render('learn/AttemptResult', [
            ...$context,
            'questions' => $quiz->questions
                ->map(function (QuizQuestion $question) use ($answers, $breakdown): array {
                    $selected = $answers[(string) $question->id] ?? [];

                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'answer_mode' => $question->answer_mode,
                        'points' => $breakdown[$question->id]['points'] ?? 0,
                        'max_points' => $question->maxPoints(),
                        'is_correct' => $breakdown[$question->id]['is_correct'] ?? false,
                        'options' => $question->options
                            ->map(fn (QuizOption $option): array => [
                                'id' => $option->id,
                                'text' => $option->text,
                                'is_correct' => (bool) $option->is_correct,
                                'was_selected' => in_array($option->id, $selected, true),
                            ])
                            ->all(),
                    ];
                })
                ->all(),
        ]);
    }

    /**
     * Hand the attempt in and grade it.
     */
    public function submit(SubmitQuizAttemptRequest $request, QuizAttempt $attempt, GradeQuizAttempt $grade): RedirectResponse
    {
        $attempt->handIn($request->array('answers'), $grade);

        Inertia::flash('toast', $attempt->is_late
            ? ['type' => 'warning', 'message' => __('Time was up, so your answers could not be counted.')]
            : ['type' => 'success', 'message' => __('Quiz submitted. You scored :score of :max.', ['score' => $attempt->score, 'max' => $attempt->max_score])]);

        return to_route('learn.attempts.show', $attempt);
    }
}
