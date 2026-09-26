<?php

namespace App\Actions;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;

class GradeQuizAttempt
{
    /**
     * Score a set of answers against the quiz's answer key.
     *
     * Single answer and true/false questions award their flat points only when
     * exactly the correct option is chosen. Multiple answer questions count the
     * correct options picked, minus one for every wrong pick, and award the
     * score tier for that count.
     *
     * The quiz must be loaded with `questions.options` and `questions.scores`.
     *
     * @param  array<int|string, mixed>  $answers  Selected option ids keyed by question id.
     * @return array{score: int, max_score: int, answers: array<string, list<int>>, questions: array<int, array{points: int, max_points: int, is_correct: bool}>}
     */
    public function __invoke(Quiz $quiz, array $answers): array
    {
        $score = 0;
        $maxScore = 0;
        $cleanAnswers = [];
        $questions = [];

        foreach ($quiz->questions as $question) {
            $selected = $this->selectedOptionIds($question, $answers[$question->id] ?? []);
            $cleanAnswers[(string) $question->id] = $selected;

            $result = $question->isMultipleAnswer()
                ? $this->gradeMultiple($question, $selected)
                : $this->gradeSingle($question, $selected);

            $questions[$question->id] = $result;
            $score += $result['points'];
            $maxScore += $result['max_points'];
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'answers' => $cleanAnswers,
            'questions' => $questions,
        ];
    }

    /**
     * Keep only the submitted ids that are real options of the question.
     *
     * @return list<int>
     */
    private function selectedOptionIds(QuizQuestion $question, mixed $submitted): array
    {
        $submitted = is_array($submitted) ? $submitted : [$submitted];
        $validIds = $question->options->pluck('id')->all();

        $selected = array_values(array_unique(array_filter(
            array_map(fn (mixed $id): int => (int) $id, $submitted),
            fn (int $id): bool => in_array($id, $validIds, true),
        )));

        sort($selected);

        return $selected;
    }

    /**
     * Grade a question with exactly one correct option.
     *
     * @param  list<int>  $selected
     * @return array{points: int, max_points: int, is_correct: bool}
     */
    private function gradeSingle(QuizQuestion $question, array $selected): array
    {
        $correctIds = $this->correctOptionIds($question);
        $isCorrect = $selected !== [] && $selected === $correctIds;

        return [
            'points' => $isCorrect ? $question->points : 0,
            'max_points' => $question->maxPoints(),
            'is_correct' => $isCorrect,
        ];
    }

    /**
     * Grade a question with several correct options using its score tiers.
     *
     * @param  list<int>  $selected
     * @return array{points: int, max_points: int, is_correct: bool}
     */
    private function gradeMultiple(QuizQuestion $question, array $selected): array
    {
        $correctIds = $this->correctOptionIds($question);
        $rightPicks = count(array_intersect($selected, $correctIds));
        $wrongPicks = count($selected) - $rightPicks;
        $creditedCount = max(0, $rightPicks - $wrongPicks);

        $points = $creditedCount === 0
            ? 0
            : (int) ($question->scores->firstWhere('correct_count', $creditedCount)->points ?? 0);

        return [
            'points' => $points,
            'max_points' => $question->maxPoints(),
            'is_correct' => $wrongPicks === 0 && $rightPicks === count($correctIds),
        ];
    }

    /**
     * Get the ids of the options marked as correct, sorted.
     *
     * @return list<int>
     */
    private function correctOptionIds(QuizQuestion $question): array
    {
        $ids = $question->options
            ->filter(fn (QuizOption $option): bool => (bool) $option->is_correct)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        sort($ids);

        return $ids;
    }
}
