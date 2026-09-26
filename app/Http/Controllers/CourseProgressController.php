<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseProgressController extends Controller
{
    /**
     * How many unanswered discussion questions the overview lists.
     */
    private const int UNANSWERED_LIMIT = 10;

    /**
     * Show how every enrolled student is progressing through the course.
     */
    public function index(Request $request, Course $course): Response
    {
        Gate::authorize('update', $course);

        $search = $request->string('search')->toString();
        $lessonIds = $this->lessonIds($course);
        $quizIds = $this->quizIds($course);
        $totalItems = $lessonIds->count() + $quizIds->count();

        $enrollments = $course->enrollments()
            ->active()
            ->with('user:id,name,email,avatar_path')
            ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->get();

        $studentIds = $enrollments->pluck('user_id');

        $completedLessons = DB::table('lesson_completions')
            ->whereIn('user_id', $studentIds)
            ->whereIn('lesson_id', $lessonIds)
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $bestAttempts = QuizAttempt::query()
            ->whereIn('user_id', $studentIds)
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('submitted_at')
            ->selectRaw('user_id, quiz_id, max(score) as best_score, max(max_score) as max_score')
            ->groupBy('user_id', 'quiz_id')
            ->get()
            ->groupBy('user_id');

        $students = $enrollments
            ->map(function (Enrollment $enrollment) use ($completedLessons, $bestAttempts, $lessonIds, $quizIds, $totalItems): array {
                $lessonsDone = (int) ($completedLessons[$enrollment->user_id] ?? 0);
                $attempts = $bestAttempts->get($enrollment->user_id, collect());
                $completed = $lessonsDone + $attempts->count();

                return [
                    'enrollment_id' => $enrollment->id,
                    'student' => $enrollment->user->only(['id', 'name', 'email', 'avatar']),
                    'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                    'last_accessed_at' => $enrollment->last_accessed_at?->toIso8601String(),
                    'lessons_completed' => $lessonsDone,
                    'lessons_total' => $lessonIds->count(),
                    'quizzes_attempted' => $attempts->count(),
                    'quizzes_total' => $quizIds->count(),
                    'quiz_score_percent' => $this->scorePercent($attempts),
                    'percent' => $totalItems === 0 ? 0 : (int) floor($completed / $totalItems * 100),
                ];
            })
            ->sortByDesc('percent')
            ->values();

        return Inertia::render('courses/Progress', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'summary' => [
                'students' => $students->count(),
                'average_percent' => $students->isEmpty() ? 0 : (int) round($students->avg('percent')),
                'completed' => $students->where('percent', 100)->count(),
                'inactive' => $students->filter(fn (array $row): bool => $row['last_accessed_at'] === null)->count(),
                'average_quiz_score' => $this->averageOf($students->pluck('quiz_score_percent')),
            ],
            'students' => $students->all(),
            'unansweredQuestions' => $this->unansweredQuestions($course, $lessonIds),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show one student's progress through every lesson and quiz of the course.
     */
    public function show(Course $course, User $student): Response
    {
        Gate::authorize('update', $course);

        $enrollment = $course->enrollments()->where('user_id', $student->id)->with('lastLesson:id,title')->first();

        abort_if($enrollment === null, 404);

        $course->load([
            'sections.lessons:id,section_id,title,content_type,duration_minutes,position',
            'sections.quizzes.questions.scores',
        ]);

        $completions = DB::table('lesson_completions')
            ->where('user_id', $student->id)
            ->pluck('created_at', 'lesson_id');

        $attempts = QuizAttempt::query()
            ->where('user_id', $student->id)
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy('quiz_id');

        $sections = $course->sections->map(fn ($section): array => [
            'id' => $section->id,
            'title' => $section->title,
            'lessons' => $section->lessons->map(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content_type' => $lesson->content_type,
                'completed_at' => isset($completions[$lesson->id]) ? (string) $completions[$lesson->id] : null,
            ])->all(),
            'quizzes' => $section->quizzes->map(function (Quiz $quiz) use ($attempts): array {
                $quizAttempts = $attempts->get($quiz->id, collect());

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'attempts' => $quizAttempts->count(),
                    'best_score' => $quizAttempts->max('score'),
                    'max_score' => $quiz->questions->sum(fn (QuizQuestion $question): int => $question->maxPoints()),
                    'last_submitted_at' => $quizAttempts->max('submitted_at')?->toIso8601String(),
                ];
            })->all(),
        ])->all();

        $questions = LessonQuestion::query()
            ->where('user_id', $student->id)
            ->whereIn('lesson_id', $this->lessonIds($course))
            ->with('lesson:id,title')
            ->withCount('replies')
            ->latest()
            ->get()
            ->map(fn (LessonQuestion $question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'created_at' => $question->created_at?->toIso8601String(),
                'replies_count' => (int) $question->replies_count,
                'lesson' => $question->lesson->only(['id', 'title']),
            ]);

        return Inertia::render('courses/ProgressStudent', [
            'course' => ['id' => $course->id, 'title' => $course->title],
            'student' => [
                ...$student->only(['id', 'name', 'email', 'avatar', 'headline']),
            ],
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'last_accessed_at' => $enrollment->last_accessed_at?->toIso8601String(),
                'last_lesson' => $enrollment->lastLesson?->only(['id', 'title']),
            ],
            'sections' => $sections,
            'questions' => $questions,
        ]);
    }

    /**
     * Get the ids of every lesson in the course.
     *
     * @return Collection<int, int>
     */
    private function lessonIds(Course $course): Collection
    {
        return Lesson::query()
            ->whereHas('section', fn (Builder $section) => $section->where('course_id', $course->id))
            ->pluck('id');
    }

    /**
     * Get the ids of every quiz in the course.
     *
     * @return Collection<int, int>
     */
    private function quizIds(Course $course): Collection
    {
        return Quiz::query()
            ->whereHas('section', fn (Builder $section) => $section->where('course_id', $course->id))
            ->pluck('id');
    }

    /**
     * Turn a student's best quiz attempts into one overall percentage.
     *
     * @param  Collection<int, QuizAttempt>  $attempts
     */
    private function scorePercent(Collection $attempts): ?int
    {
        $possible = (int) $attempts->sum('max_score');

        return $possible === 0 ? null : (int) round($attempts->sum('best_score') / $possible * 100);
    }

    /**
     * Average the values that are present, ignoring students without a score.
     *
     * @param  Collection<int, int|null>  $values
     */
    private function averageOf(Collection $values): ?int
    {
        $present = $values->filter(fn (?int $value): bool => $value !== null);

        return $present->isEmpty() ? null : (int) round($present->avg());
    }

    /**
     * List the course's newest discussion questions that nobody has replied to yet.
     *
     * @param  Collection<int, int>  $lessonIds
     * @return list<array<string, mixed>>
     */
    private function unansweredQuestions(Course $course, Collection $lessonIds): array
    {
        return LessonQuestion::query()
            ->whereIn('lesson_id', $lessonIds)
            ->whereDoesntHave('replies')
            ->with(['user:id,name,avatar_path', 'lesson:id,title'])
            ->latest()
            ->limit(self::UNANSWERED_LIMIT)
            ->get()
            ->map(fn (LessonQuestion $question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'created_at' => $question->created_at?->toIso8601String(),
                'author' => $question->user->only(['id', 'name', 'avatar']),
                'lesson' => $question->lesson->only(['id', 'title']),
                'course_id' => $course->id,
            ])
            ->all();
    }
}
