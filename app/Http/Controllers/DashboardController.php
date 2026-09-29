<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\LessonQuestion;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The staff dashboard: admins see the whole platform, instructors only their own courses.
 */
class DashboardController extends Controller
{
    /**
     * How many days the activity chart covers.
     */
    private const int CHART_DAYS = 30;

    /**
     * How many rows each list on the dashboard shows.
     */
    private const int LIST_LIMIT = 5;

    /**
     * How many items each "needs attention" group shows; the rest are one click away.
     */
    private const int ACTION_LIMIT = 3;

    /**
     * How many latest reviews are shown: two full rows of three.
     */
    private const int REVIEW_LIMIT = 6;

    /**
     * Show the dashboard of the signed-in admin or instructor.
     */
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();
        assert($actor instanceof User);

        $monthStart = Date::now()->startOfMonth();
        $previousMonthStart = $monthStart->subMonth();

        $courses = fn (): Builder => Course::query()->manageableBy($actor);
        $enrollments = fn (): Builder => Enrollment::query()->manageableBy($actor);
        $transactions = fn (): Builder => Transaction::query()->manageableBy($actor);
        // Instructors see their share after the platform commission.
        $revenue = Transaction::revenueColumnFor($actor);
        $reviews = fn (): Builder => CourseReview::query()->whereHas('course', fn (Builder $course) => $course->manageableBy($actor));

        return Inertia::render('Dashboard', [
            'isAdmin' => $actor->isAdmin(),
            'stats' => [
                'revenue' => [
                    'month' => (int) $transactions()->where('paid_at', '>=', $monthStart)->sum($revenue),
                    'previous' => (int) $transactions()->whereBetween('paid_at', [$previousMonthStart, $monthStart])->sum($revenue),
                    'total' => (int) $transactions()->sum($revenue),
                ],
                'enrollments' => [
                    'month' => $enrollments()->where('enrolled_at', '>=', $monthStart)->count(),
                    'previous' => $enrollments()->whereBetween('enrolled_at', [$previousMonthStart, $monthStart])->count(),
                ],
                'students' => $enrollments()->active()->distinct()->count('user_id'),
                'courses' => [
                    'published' => $courses()->published()->count(),
                    'draft' => $courses()->where('status', Course::STATUS_DRAFT)->count(),
                    'pending' => $courses()->where('status', Course::STATUS_PENDING)->count(),
                ],
                'rating' => CourseReview::summarize($reviews()),
                'users' => $actor->isAdmin() ? [
                    'students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
                    'instructors' => User::query()->where('role', User::ROLE_INSTRUCTOR)->count(),
                    'new_this_month' => User::query()->where('created_at', '>=', $monthStart)->count(),
                ] : null,
            ],
            'chart' => $this->chart($enrollments(), $transactions(), $revenue),
            'awaitingOrders' => Order::query()
                ->manageableBy($actor)
                ->where('status', Order::STATUS_AWAITING_CONFIRMATION)
                ->with('user:id,name,avatar_path')
                ->oldest('proof_uploaded_at')
                ->limit(self::ACTION_LIMIT)
                ->get()
                ->map(fn (Order $order): array => [
                    'number' => $order->number,
                    'course_title' => $order->course_title,
                    'total' => $order->total,
                    'proof_uploaded_at' => $order->proof_uploaded_at?->toIso8601String(),
                    'student' => $order->user->only(['id', 'name', 'avatar']),
                ])
                ->all(),
            'awaitingOrdersCount' => Order::query()->manageableBy($actor)->where('status', Order::STATUS_AWAITING_CONFIRMATION)->count(),
            'unansweredQuestions' => LessonQuestion::query()
                ->whereHas('lesson.section.course', fn (Builder $course) => $course->manageableBy($actor))
                ->whereDoesntHave('replies')
                ->with(['user:id,name,avatar_path', 'lesson:id,section_id,title,slug', 'lesson.section:id,course_id', 'lesson.section.course:id,slug,title'])
                ->latest()
                ->limit(self::ACTION_LIMIT)
                ->get()
                ->map(fn (LessonQuestion $question): array => [
                    'id' => $question->id,
                    'body' => $question->body,
                    'created_at' => $question->created_at?->toIso8601String(),
                    'author' => $question->user->only(['id', 'name', 'avatar']),
                    'lesson' => $question->lesson->only(['slug', 'title']),
                    'course' => $question->lesson->section->course->only(['slug', 'title']),
                ])
                ->all(),
            'pendingCourses' => $courses()
                ->where('status', Course::STATUS_PENDING)
                ->with('instructor:id,name')
                ->latest('updated_at')
                ->limit(self::ACTION_LIMIT)
                ->get(['id', 'slug', 'title', 'instructor_id', 'updated_at'])
                ->map(fn (Course $course): array => [
                    'slug' => $course->slug,
                    'title' => $course->title,
                    'instructor' => $course->instructor?->name,
                    'updated_at' => $course->updated_at?->toIso8601String(),
                ])
                ->all(),
            'topCourses' => $courses()
                ->withCount(['enrollments' => fn (Builder $query) => $query->where('status', Enrollment::STATUS_ACTIVE)])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('enrollments_count')
                ->orderBy('title')
                ->limit(self::LIST_LIMIT)
                ->get()
                ->map(fn (Course $course): array => [
                    'slug' => $course->slug,
                    'title' => $course->title,
                    'status' => $course->status,
                    'thumbnail_url' => $course->thumbnail_url,
                    'students' => (int) $course->enrollments_count,
                    'rating' => $course->reviews_avg_rating === null ? null : round((float) $course->reviews_avg_rating, 1),
                    'reviews' => (int) $course->reviews_count,
                ])
                ->all(),
            'recentEnrollments' => $enrollments()
                ->with(['user:id,name,avatar_path', 'course:id,slug,title'])
                ->latest('enrolled_at')
                ->latest('id')
                ->limit(self::LIST_LIMIT)
                ->get()
                ->map(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                    'student' => $enrollment->user->only(['id', 'name', 'avatar']),
                    'course' => $enrollment->course->only(['slug', 'title']),
                ])
                ->all(),
            'recentReviews' => $reviews()
                ->with(['user:id,name,avatar_path', 'course:id,slug,title'])
                ->latest()
                ->limit(self::REVIEW_LIMIT)
                ->get()
                ->map(fn (CourseReview $review): array => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->toIso8601String(),
                    'author' => $review->user->only(['id', 'name', 'avatar']),
                    'course' => $review->course->only(['slug', 'title']),
                ])
                ->all(),
        ]);
    }

    /**
     * New enrollments and revenue per day over the last CHART_DAYS days, oldest first.
     *
     * @param  Builder<Enrollment>  $enrollments
     * @param  Builder<Transaction>  $transactions
     * @param  string  $revenue  The transaction column that counts as revenue for the viewer.
     * @return list<array{date: string, enrollments: int, revenue: int}>
     */
    private function chart(Builder $enrollments, Builder $transactions, string $revenue): array
    {
        $start = Date::today()->subDays(self::CHART_DAYS - 1);

        $enrollmentsPerDay = $enrollments
            ->where('enrolled_at', '>=', $start)
            ->pluck('enrolled_at')
            ->countBy(fn (CarbonImmutable $at): string => $at->toDateString());

        $revenuePerDay = $transactions
            ->where('paid_at', '>=', $start)
            ->get([$revenue, 'paid_at'])
            ->groupBy(fn (Transaction $transaction): string => $transaction->paid_at->toDateString())
            ->map(fn ($day): int => (int) $day->sum($revenue));

        $days = [];

        for ($i = 0; $i < self::CHART_DAYS; $i++) {
            $date = $start->addDays($i)->toDateString();

            $days[] = [
                'date' => $date,
                'enrollments' => (int) ($enrollmentsPerDay[$date] ?? 0),
                'revenue' => (int) ($revenuePerDay[$date] ?? 0),
            ];
        }

        return $days;
    }
}
