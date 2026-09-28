<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\GradeScale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildCourseGradebook
{
    /**
     * Build the grade of every actively enrolled student in the course.
     *
     * Each quiz counts with its best submitted attempt, as a percentage of that
     * attempt's maximum score. The final grade is the weighted average of every
     * quiz with a weight above zero; a quiz the student has not taken counts as
     * zero. A student passes the course when the final grade reaches the
     * course's passing grade. Pass a student as `$only` to grade just them.
     *
     * @return array{
     *     quizzes: list<array{id: int, title: string, section_title: string, weight: int, passing_score: int|null, max_score: int}>,
     *     students: list<array{
     *         enrollment_id: int,
     *         student: array{id: int, name: string, email: string, avatar: string|null},
     *         grades: array<int, array{percent: int|null, score: int|null, max_score: int|null, attempts: int, passed: bool|null}>,
     *         quizzes_taken: int,
     *         final_percent: int|null,
     *         letter: string|null,
     *         passed: bool|null,
     *         certificate_code: string|null,
     *     }>,
     *     total_weight: int,
     * }
     */
    public function __invoke(Course $course, string $search = '', ?User $only = null): array
    {
        $quizzes = Quiz::query()
            ->whereHas('section', fn (Builder $section) => $section->where('course_id', $course->id))
            ->with(['section:id,title,position', 'questions.scores'])
            ->get()
            ->sortBy(fn (Quiz $quiz): array => [$quiz->section->position, $quiz->position])
            ->values();

        $totalWeight = (int) $quizzes->sum('weight');

        $enrollments = $course->enrollments()
            ->active()
            ->with('user:id,name,slug,email,avatar_path')
            ->when($only !== null, fn (Builder $query) => $query->where('user_id', $only?->id))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->get()
            ->sortBy(fn (Enrollment $enrollment): string => mb_strtolower($enrollment->user->name))
            ->values();

        $attempts = QuizAttempt::query()
            ->whereIn('user_id', $enrollments->pluck('user_id'))
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->whereNotNull('submitted_at')
            ->get(['user_id', 'quiz_id', 'score', 'max_score'])
            ->groupBy(['user_id', 'quiz_id']);

        $certificates = Certificate::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $enrollments->pluck('user_id'))
            ->pluck('code', 'user_id');

        $students = $enrollments
            ->map(function (Enrollment $enrollment) use ($quizzes, $attempts, $totalWeight, $course, $certificates): array {
                /** @var Collection<int, Collection<int, QuizAttempt>> $studentAttempts */
                $studentAttempts = $attempts->get($enrollment->user_id, collect());

                $grades = [];
                $weighted = 0;

                foreach ($quizzes as $quiz) {
                    $grade = $this->quizGrade($quiz, $studentAttempts->get($quiz->id, collect()));
                    $grades[$quiz->id] = $grade;
                    $weighted += ($grade['percent'] ?? 0) * $quiz->weight;
                }

                $finalPercent = $totalWeight === 0 ? null : (int) round($weighted / $totalWeight);

                return [
                    'enrollment_id' => $enrollment->id,
                    'student' => $enrollment->user->only(['id', 'name', 'slug', 'email', 'avatar']),
                    'grades' => $grades,
                    'quizzes_taken' => count(array_filter($grades, fn (array $grade): bool => $grade['attempts'] > 0)),
                    'final_percent' => $finalPercent,
                    'letter' => $finalPercent === null ? null : GradeScale::letter($finalPercent),
                    'passed' => $finalPercent === null ? null : $finalPercent >= $course->passing_grade,
                    'certificate_code' => $certificates[$enrollment->user_id] ?? null,
                ];
            })
            ->all();

        return [
            'quizzes' => array_values($quizzes
                ->map(fn (Quiz $quiz): array => [
                    'id' => $quiz->id,
                    'slug' => $quiz->slug,
                    'title' => $quiz->title,
                    'section_title' => $quiz->section->title,
                    'weight' => $quiz->weight,
                    'passing_score' => $quiz->passing_score,
                    'max_score' => (int) $quiz->questions->sum(fn (QuizQuestion $question): int => $question->maxPoints()),
                ])
                ->all()),
            'students' => array_values($students),
            'total_weight' => $totalWeight,
        ];
    }

    /**
     * Pick the best attempt a student made at one quiz.
     *
     * @param  Collection<int, QuizAttempt>  $attempts
     * @return array{percent: int|null, score: int|null, max_score: int|null, attempts: int, passed: bool|null}
     */
    private function quizGrade(Quiz $quiz, Collection $attempts): array
    {
        $best = $attempts->sortByDesc(fn (QuizAttempt $attempt): float => self::percentOf($attempt))->first();

        if ($best === null) {
            return ['percent' => null, 'score' => null, 'max_score' => null, 'attempts' => 0, 'passed' => null];
        }

        $percent = (int) round(self::percentOf($best));

        return [
            'percent' => $percent,
            'score' => (int) $best->score,
            'max_score' => $best->max_score,
            'attempts' => $attempts->count(),
            'passed' => $quiz->isPassingPercent($percent),
        ];
    }

    /**
     * Get an attempt's score as a percentage of its maximum.
     */
    public static function percentOf(QuizAttempt $attempt): float
    {
        return $attempt->max_score === 0 ? 0.0 : (int) $attempt->score / $attempt->max_score * 100;
    }
}
