<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class CalculateCourseProgress
{
    /**
     * Work out how far the student is through the course.
     *
     * Every lesson counts once it is marked complete and every quiz counts once
     * an attempt has been handed in.
     *
     * @return array{completed: int, total: int, percent: int, completed_lesson_ids: list<int>, attempted_quiz_ids: list<int>}
     */
    public function __invoke(User $student, Course $course): array
    {
        $lessonIds = Lesson::query()
            ->whereHas('section', fn ($section) => $section->where('course_id', $course->id))
            ->pluck('id');

        $quizIds = Quiz::query()
            ->whereHas('section', fn ($section) => $section->where('course_id', $course->id))
            ->pluck('id');

        $completedLessonIds = $student->completedLessons()
            ->whereIn('lessons.id', $lessonIds)
            ->pluck('lessons.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $attemptedQuizIds = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('submitted_at')
            ->distinct()
            ->pluck('quiz_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $total = $lessonIds->count() + $quizIds->count();
        $completed = count($completedLessonIds) + count($attemptedQuizIds);

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) floor($completed / $total * 100),
            'completed_lesson_ids' => $completedLessonIds,
            'attempted_quiz_ids' => $attemptedQuizIds,
        ];
    }
}
