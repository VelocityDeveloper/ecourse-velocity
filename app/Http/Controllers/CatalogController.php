<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Support\CatalogCourseCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    /**
     * The catalogue orderings, the first being the default.
     *
     * @var list<string>
     */
    /**
     * The number of other courses suggested at the bottom of a course page.
     */
    private const int RELATED_LIMIT = 4;

    private const array SORTS = ['terbaru', 'populer', 'rating', 'termurah'];

    /**
     * How many written reviews the course page shows.
     */
    private const int REVIEW_LIMIT = 20;

    /**
     * List the published courses students can enroll in.
     */
    public function index(Request $request): Response
    {
        $viewerId = $request->user()?->id;
        $search = $request->string('search')->toString();
        $level = $request->string('level')->toString();

        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, self::SORTS, true) ? $sort : self::SORTS[0];

        $courses = Course::query()
            ->published()
            ->forCatalogCard($viewerId)
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when(in_array($level, Course::LEVELS, true), fn (Builder $query) => $query->where('level', $level))
            ->when($sort === 'populer', fn (Builder $query) => $query->orderByDesc('students_count'))
            ->when($sort === 'rating', fn (Builder $query) => $query->orderByRaw('reviews_avg_rating IS NULL')->orderByDesc('reviews_avg_rating'))
            ->when($sort === 'termurah', fn (Builder $query) => $query->orderBy('price'))
            ->latest()
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Course $course): array => CatalogCourseCard::from($course));

        return Inertia::render('catalog/Index', [
            'courses' => $courses,
            'filters' => [...$request->only(['search', 'category_id', 'level']), 'sort' => $sort],
            'categories' => Category::query()
                ->withCount(['courses' => fn (Builder $query) => $query->published()])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'courses_count' => (int) $category->courses_count,
                ]),
            'levels' => Course::LEVELS,
            'sorts' => self::SORTS,
            'total' => Course::query()->published()->count(),
        ]);
    }

    /**
     * Show a course as students see it, with its outline and enroll action.
     */
    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('viewInCatalog', $course);

        $viewer = $request->user();

        if ($viewer === null) {
            // Bring a guest back to this course after they log in to enroll.
            redirect()->setIntendedUrl(route('catalog.show', $course));
        }

        $course->load([
            'category:id,name',
            'instructor:id,name,avatar_path,headline,bio',
            'sections.lessons:id,section_id,title,content_type,duration_minutes,position',
            'sections.quizzes' => fn ($query) => $query->withCount('questions'),
        ]);

        $enrollment = $viewer === null
            ? null
            : $course->enrollments()->where('user_id', $viewer->id)->first();

        return Inertia::render('catalog/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'level' => $course->level,
                'price' => $course->price,
                'status' => $course->status,
                'thumbnail_url' => $course->thumbnail_url,
                'category' => $course->category?->only(['id', 'name']),
                'instructor' => $course->instructor === null ? null : [
                    ...$course->instructor->only(['id', 'name', 'avatar', 'headline', 'bio']),
                    'courses_count' => $course->instructor->courses()->published()->count(),
                ],
                'students_count' => $course->enrollments()->active()->count(),
                'total_minutes' => (int) $course->sections->flatMap->lessons->sum('duration_minutes'),
                'updated_at' => $course->updated_at?->toIso8601String(),
            ],
            'related' => Course::query()
                ->published()
                ->whereKeyNot($course->id)
                ->when($course->category_id !== null, fn (Builder $query) => $query->where('category_id', $course->category_id))
                ->forCatalogCard($viewer?->id)
                ->orderByDesc('students_count')
                ->limit(self::RELATED_LIMIT)
                ->get()
                ->map(fn (Course $related): array => CatalogCourseCard::from($related))
                ->all(),
            'sections' => $course->sections
                ->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'lessons' => $section->lessons
                        ->map(fn (Lesson $lesson): array => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'content_type' => $lesson->content_type,
                            'duration_minutes' => $lesson->duration_minutes,
                        ])
                        ->all(),
                    'quizzes' => $section->quizzes
                        ->map(fn (Quiz $quiz): array => [
                            'id' => $quiz->id,
                            'title' => $quiz->title,
                            'time_limit_minutes' => $quiz->time_limit_minutes,
                            'questions_count' => (int) $quiz->questions_count,
                        ])
                        ->all(),
                ])
                ->all(),
            'enrollment' => $enrollment === null ? null : [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'cancelled_at' => $enrollment->cancelled_at?->toIso8601String(),
            ],
            'rating' => $this->ratingSummary($course),
            'reviews' => $course->reviews()
                ->with('user:id,name,avatar_path')
                ->whereNotNull('comment')
                ->latest()
                ->limit(self::REVIEW_LIMIT)
                ->get()
                ->map(fn (CourseReview $review): array => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->toIso8601String(),
                    'author' => $review->user->only(['id', 'name', 'avatar']),
                    'can_delete' => Gate::allows('delete', $review),
                ])
                ->all(),
            'myReview' => $viewer === null
                ? null
                : $course->reviews()->where('user_id', $viewer->id)->first()?->only(['id', 'rating', 'comment']),
            'can' => [
                'enroll' => Gate::allows('enroll', $course) && ! $enrollment?->isActive(),
                'cancel' => $enrollment !== null && Gate::allows('cancel', $enrollment),
                'manage' => Gate::allows('update', $course),
                'learn' => $viewer !== null && Gate::allows('learn', $course),
                'review' => Gate::allows('review', $course),
            ],
        ]);
    }

    /**
     * Summarise the course's star ratings: the average, the count and how many of each star.
     *
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    private function ratingSummary(Course $course): array
    {
        $counts = $course->reviews()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = (int) $counts->sum();
        $distribution = [];

        foreach (range(CourseReview::MAX_RATING, CourseReview::MIN_RATING) as $stars) {
            $distribution[$stars] = (int) ($counts[$stars] ?? 0);
        }

        return [
            'average' => $count === 0
                ? null
                : round($counts->map(fn (mixed $total, mixed $stars): int => (int) $stars * (int) $total)->sum() / $count, 1),
            'count' => $count,
            'distribution' => $distribution,
        ];
    }

    /**
     * Enroll the current student in the course.
     */
    public function enroll(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('enroll', $course);

        $actor = $this->actor($request);

        if ($course->enrollments()->active()->where('user_id', $actor->id)->exists()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('You are already enrolled in this course.')]);

            return to_route('catalog.show', $course);
        }

        Enrollment::enroll($actor, $course);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You are now enrolled in :course.', ['course' => $course->title])]);

        return to_route('catalog.show', $course);
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
