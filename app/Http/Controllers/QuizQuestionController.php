<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRequest;
use App\Http\Requests\QuizQuestionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuizQuestionController extends Controller
{
    /**
     * Append a question, with its options and answer key, to the quiz.
     */
    public function store(QuizQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz->section->course);

        DB::transaction(function () use ($request, $quiz): void {
            $question = $quiz->questions()->create([
                'question' => $request->string('question')->toString(),
                'answer_mode' => $request->string('answer_mode')->toString(),
                'points' => $this->flatPoints($request),
                'position' => ((int) $quiz->questions()->max('position')) + 1,
            ]);

            $this->syncAnswerKey($request, $question);
        });

        return back();
    }

    /**
     * Replace the question, its options and its answer key.
     */
    public function update(QuizQuestionRequest $request, QuizQuestion $question): RedirectResponse
    {
        Gate::authorize('update', $question->quiz->section->course);

        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'question' => $request->string('question')->toString(),
                'answer_mode' => $request->string('answer_mode')->toString(),
                'points' => $this->flatPoints($request),
            ]);

            $question->options()->delete();
            $question->scores()->delete();

            $this->syncAnswerKey($request, $question);
        });

        return back();
    }

    /**
     * Move the question one step up or down within its quiz.
     */
    public function move(MoveRequest $request, QuizQuestion $question): RedirectResponse
    {
        Gate::authorize('update', $question->quiz->section->course);

        $question->move($request->string('direction')->toString());

        return back();
    }

    /**
     * Delete the question along with its options and answer key.
     */
    public function destroy(QuizQuestion $question): RedirectResponse
    {
        Gate::authorize('update', $question->quiz->section->course);

        $question->delete();
        $question->resequenceSiblings();

        return back();
    }

    /**
     * Write the options and the scoring tiers for the question.
     */
    private function syncAnswerKey(QuizQuestionRequest $request, QuizQuestion $question): void
    {
        foreach (array_values($request->array('options')) as $index => $option) {
            $question->options()->create([
                'text' => (string) ($option['text'] ?? ''),
                'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'position' => $index + 1,
            ]);
        }

        if ($request->string('answer_mode')->toString() !== QuizQuestion::MODE_MULTIPLE) {
            return;
        }

        foreach (array_values($request->array('scores')) as $index => $points) {
            $question->scores()->create([
                'correct_count' => $index + 1,
                'points' => max(0, (int) $points),
            ]);
        }
    }

    /**
     * Get the flat score, which only a single answer question carries.
     */
    private function flatPoints(QuizQuestionRequest $request): int
    {
        if ($request->string('answer_mode')->toString() === QuizQuestion::MODE_MULTIPLE) {
            return 0;
        }

        return max(0, $request->integer('points'));
    }
}
