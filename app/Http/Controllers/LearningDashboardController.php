<?php

namespace App\Http\Controllers;

use App\Actions\CalculateCourseProgress;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LearningDashboardController extends Controller
{
    /**
     * How many recent activity entries the dashboard lists.
     */
    private const int ACTIVITY_LIMIT = 8;

    /**
     * Show the student's learning overview.
     */
    public function __invoke(Request $request, CalculateCourseProgress $calculateProgress): Response
    {
        $actor = $request->user();

        assert($actor instanceof User);

        $enrollments = $actor->enrollments()
            ->active()
            ->with(['course:id,title,slug,thumbnail_path,level,instructor_id', 'course.instructor:id,name', 'lastLesson:id,title,slug'])
            ->orderByRaw('last_accessed_at is null')
            ->latest('last_accessed_at')
            ->latest('enrolled_at')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'enrollment' => $enrollment,
                'progress' => $calculateProgress($actor, $enrollment->course),
            ]);

        $resume = $enrollments->first(fn (array $row): bool => $row['enrollment']->last_accessed_at !== null
            && $row['progress']['percent'] < 100) ?? $enrollments->first(fn (array $row): bool => $row['progress']['percent'] < 100);

        $submittedAttempts = $actor->quizAttempts()->whereNotNull('submitted_at');
        $scoreTotals = (clone $submittedAttempts)->selectRaw('sum(score) as scored, sum(max_score) as possible')->first();
        $possible = (int) ($scoreTotals->possible ?? 0);

        return Inertia::render('learning/Dashboard', [
            'resume' => $resume === null ? null : $this->courseCard($resume['enrollment'], $resume['progress']),
            'stats' => [
                'active_courses' => $enrollments->count(),
                'completed_courses' => $enrollments->filter(fn (array $row): bool => $row['progress']['total'] > 0 && $row['progress']['percent'] === 100)->count(),
                'lessons_completed' => $actor->completedLessons()->count(),
                'average_quiz_score' => $possible === 0 ? null : (int) round(((int) $scoreTotals->scored) / $possible * 100),
                'notes' => $actor->lessonNotes()->count(),
                'bookmarks' => $actor->bookmarkedLessons()->count(),
            ],
            'courses' => $enrollments
                ->map(fn (array $row): array => $this->courseCard($row['enrollment'], $row['progress']))
                ->values()
                ->all(),
            'activity' => $this->recentActivity($actor),
        ]);
    }

    /**
     * Shape an enrollment for the dashboard's course cards.
     *
     * @param  array{completed: int, total: int, percent: int}  $progress
     * @return array<string, mixed>
     */
    private function courseCard(Enrollment $enrollment, array $progress): array
    {
        return [
            'course' => [
                'id' => $enrollment->course->id,
                'slug' => $enrollment->course->slug,
                'title' => $enrollment->course->title,
                'thumbnail_url' => $enrollment->course->thumbnail_url,
                'level' => $enrollment->course->level,
                'instructor' => $enrollment->course->instructor?->name,
            ],
            'last_lesson' => $enrollment->lastLesson?->only(['id', 'slug', 'title']),
            'last_accessed_at' => $enrollment->last_accessed_at?->toIso8601String(),
            'progress' => [
                'completed' => $progress['completed'],
                'total' => $progress['total'],
                'percent' => $progress['percent'],
            ],
        ];
    }

    /**
     * Merge the latest finished lessons and handed-in quizzes into one timeline.
     *
     * @return list<array<string, mixed>>
     */
    private function recentActivity(User $student): array
    {
        /** @var Collection<int, array<string, mixed>> $lessons */
        $lessons = $student->completedLessons()
            ->with('section.course:id,title,slug')
            ->orderByPivot('created_at', 'desc')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Lesson $lesson): array => [
                'type' => 'lesson',
                'title' => $lesson->title,
                'course' => $lesson->section->course->only(['id', 'slug', 'title']),
                'target_id' => $lesson->id,
                'target_slug' => $lesson->slug,
                'at' => $lesson->pivot?->created_at?->toIso8601String(),
            ]);

        $quizzes = $student->quizAttempts()
            ->whereNotNull('submitted_at')
            ->with('quiz:id,section_id,title,slug', 'quiz.section.course:id,title,slug')
            ->latest('submitted_at')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (QuizAttempt $attempt): array => [
                'type' => 'quiz',
                'title' => $attempt->quiz->title,
                'course' => $attempt->quiz->section->course->only(['id', 'slug', 'title']),
                'target_id' => $attempt->id,
                'target_slug' => $attempt->quiz->slug,
                'score' => $attempt->score,
                'max_score' => $attempt->max_score,
                'at' => $attempt->submitted_at?->toIso8601String(),
            ]);

        return $lessons->concat($quizzes)
            ->sortByDesc('at')
            ->take(self::ACTIVITY_LIMIT)
            ->values()
            ->all();
    }
}
