<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\User;

class CheckCertificateEligibility
{
    public function __construct(
        private CalculateCourseProgress $calculateProgress,
        private BuildCourseGradebook $buildGradebook,
    ) {}

    /**
     * Decide whether the student has earned the course certificate.
     *
     * The student needs an active enrollment, every lesson completed and every
     * quiz handed in (100% progress), and a final grade that reaches the
     * course's passing grade. A course whose quizzes all weigh zero, or that
     * has no quizzes, has no final grade, so progress alone decides.
     *
     * @return array{eligible: bool, progress_percent: int, final_percent: int|null, letter: string|null, passing_grade: int, missing: list<string>}
     */
    public function __invoke(User $student, Course $course): array
    {
        $enrolled = $course->enrollments()->active()->where('user_id', $student->id)->exists();
        $progress = ($this->calculateProgress)($student, $course);
        $row = ($this->buildGradebook)($course, only: $student)['students'][0] ?? null;

        $finalPercent = $row['final_percent'] ?? null;
        $missing = [];

        if (! $enrolled) {
            $missing[] = __('An active enrollment in the course');
        }

        if ($progress['total'] === 0 || $progress['percent'] < 100) {
            $missing[] = __('Finish every lesson and quiz (now :percent%)', ['percent' => $progress['percent']]);
        }

        if ($finalPercent !== null && $finalPercent < $course->passing_grade) {
            $missing[] = __('A final grade of at least :min% (now :percent%)', ['min' => $course->passing_grade, 'percent' => $finalPercent]);
        }

        return [
            'eligible' => $missing === [],
            'progress_percent' => $progress['percent'],
            'final_percent' => $finalPercent,
            'letter' => $row['letter'] ?? null,
            'passing_grade' => $course->passing_grade,
            'missing' => $missing,
        ];
    }
}
