<?php

namespace App\Support\Dashboard;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\InstructorApplication;
use App\Models\LessonQuestion;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\InstructorBalance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * The numbers behind the staff dashboards. Admins see the whole platform and its
 * money; instructors see their own courses, their net earnings and their students.
 */
final class BuildDashboard
{
    /**
     * How many rows each ranked list or feed shows.
     */
    private const int LIST_LIMIT = 5;

    /**
     * How many courses the instructor's performance table shows.
     */
    private const int COURSE_TABLE_LIMIT = 10;

    public function __construct(
        private readonly User $actor,
        private readonly DashboardPeriod $period,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function admin(): array
    {
        return [
            'period' => $this->period->toArray(),
            'kpis' => [
                'revenue' => $this->compareSum($this->transactions(), 'paid_at', 'amount'),
                'commission' => $this->compareSum($this->transactions(), 'paid_at', 'commission_amount'),
                'enrollments' => $this->compareCount($this->enrollments(), 'enrolled_at'),
                'new_users' => $this->compareCount(User::query(), 'created_at'),
            ],
            'actions' => [
                'awaiting_orders' => Order::query()->where('status', Order::STATUS_AWAITING_CONFIRMATION)->count(),
                'pending_withdrawals' => Withdrawal::query()->where('status', Withdrawal::STATUS_PENDING)->count(),
                'pending_withdrawals_amount' => (int) Withdrawal::query()->where('status', Withdrawal::STATUS_PENDING)->sum('amount'),
                'pending_applications' => InstructorApplication::query()->where('status', InstructorApplication::STATUS_PENDING)->count(),
                'pending_courses' => Course::query()->where('status', Course::STATUS_PENDING)->count(),
                'unanswered_questions' => $this->unansweredQuestions()->count(),
            ],
            'series' => $this->series('amount'),
            'finance' => $this->platformFinance(),
            'orders' => $this->orderOutcomes(),
            'learning' => $this->learning(),
            'platform' => [
                'students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
                'instructors' => User::query()->where('role', User::ROLE_INSTRUCTOR)->count(),
                'courses' => Course::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn (mixed $total): int => (int) $total),
                'active_enrollments' => Enrollment::query()->active()->count(),
                'rating' => CourseReview::summarize(CourseReview::query()),
            ],
            'topCourses' => $this->topCourses('amount'),
            'topInstructors' => $this->topInstructors(),
            'recentPayments' => $this->recentPayments('amount'),
            'recentEnrollments' => $this->recentEnrollments(),
            'questions' => $this->questionList(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function instructor(): array
    {
        return [
            'period' => $this->period->toArray(),
            'kpis' => [
                'revenue' => $this->compareSum($this->transactions(), 'paid_at', 'instructor_amount'),
                'enrollments' => $this->compareCount($this->enrollments(), 'enrolled_at'),
                'students' => $this->enrollments()->active()->distinct()->count('user_id'),
                'rating' => CourseReview::summarize($this->reviews()),
            ],
            'balance' => InstructorBalance::for($this->actor)->toArray(),
            'actions' => [
                'unanswered_questions' => $this->unansweredQuestions()->count(),
                'pending_courses' => $this->courses()->where('status', Course::STATUS_PENDING)->count(),
                'draft_courses' => $this->courses()->where('status', Course::STATUS_DRAFT)->count(),
                'awaiting_orders' => Order::query()->manageableBy($this->actor)->where('status', Order::STATUS_AWAITING_CONFIRMATION)->count(),
            ],
            'series' => $this->series('instructor_amount'),
            'learning' => $this->learning(),
            'courses' => $this->coursePerformance(),
            'questions' => $this->questionList(),
            'recentReviews' => $this->recentReviews(),
            'recentPayments' => $this->recentPayments('instructor_amount'),
        ];
    }

    /**
     * @return Builder<Course>
     */
    private function courses(): Builder
    {
        return Course::query()->manageableBy($this->actor);
    }

    /**
     * @return Builder<Enrollment>
     */
    private function enrollments(): Builder
    {
        return Enrollment::query()->manageableBy($this->actor);
    }

    /**
     * @return Builder<Transaction>
     */
    private function transactions(): Builder
    {
        return Transaction::query()->manageableBy($this->actor);
    }

    /**
     * @return Builder<CourseReview>
     */
    private function reviews(): Builder
    {
        return CourseReview::query()->whereHas('course', fn (Builder $course) => $course->manageableBy($this->actor));
    }

    /**
     * Ids of the courses the viewer manages, as a subquery.
     */
    private function courseIds(): QueryBuilder
    {
        return $this->courses()->select('courses.id')->toBase();
    }

    /**
     * A count in this period and in the one before it.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array{current: int, previous: int}
     */
    private function compareCount(Builder $query, string $column): array
    {
        return [
            'current' => (clone $query)->where($column, '>=', $this->period->start)->count(),
            'previous' => (clone $query)->where($column, '>=', $this->period->previousStart)->where($column, '<', $this->period->start)->count(),
        ];
    }

    /**
     * A sum in this period and in the one before it.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array{current: int, previous: int}
     */
    private function compareSum(Builder $query, string $column, string $sum): array
    {
        return [
            'current' => (int) (clone $query)->where($column, '>=', $this->period->start)->sum($sum),
            'previous' => (int) (clone $query)->where($column, '>=', $this->period->previousStart)->where($column, '<', $this->period->start)->sum($sum),
        ];
    }

    /**
     * Revenue and new enrollments per chart bucket, oldest first.
     *
     * @return list<array{bucket: string, revenue: int, enrollments: int}>
     */
    private function series(string $revenueColumn): array
    {
        $enrollments = $this->enrollments()
            ->where('enrolled_at', '>=', $this->period->start)
            ->pluck('enrolled_at')
            ->countBy(fn (CarbonImmutable $at): string => $this->period->bucket($at));

        $revenue = $this->transactions()
            ->where('paid_at', '>=', $this->period->start)
            ->get([$revenueColumn, 'paid_at'])
            ->groupBy(fn (Transaction $transaction): string => $this->period->bucket($transaction->paid_at))
            ->map(fn ($bucket): int => (int) $bucket->sum($revenueColumn));

        return array_map(fn (string $bucket): array => [
            'bucket' => $bucket,
            'revenue' => (int) ($revenue[$bucket] ?? 0),
            'enrollments' => (int) ($enrollments[$bucket] ?? 0),
        ], $this->period->buckets());
    }

    /**
     * Where the platform's money stands, all time.
     *
     * @return array<string, int>
     */
    private function platformFinance(): array
    {
        $totals = Transaction::query()
            ->selectRaw('coalesce(sum(amount), 0) as gross, coalesce(sum(commission_amount), 0) as commission, coalesce(sum(instructor_amount), 0) as instructor_share')
            ->first();
        $withdrawals = Withdrawal::query()
            ->whereIn('status', [Withdrawal::STATUS_PAID, Withdrawal::STATUS_PENDING])
            ->selectRaw('status, sum(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $share = (int) $totals?->getAttribute('instructor_share');
        $paidOut = (int) ($withdrawals[Withdrawal::STATUS_PAID] ?? 0);
        $pending = (int) ($withdrawals[Withdrawal::STATUS_PENDING] ?? 0);

        return [
            'gross' => (int) $totals?->getAttribute('gross'),
            'commission' => (int) $totals?->getAttribute('commission'),
            'instructor_share' => $share,
            'paid_out' => $paidOut,
            'pending_payout' => $pending,
            'owed' => max(0, $share - $paidOut - $pending),
        ];
    }

    /**
     * What became of the orders placed in this period.
     *
     * @return array{placed: int, paid: int, open: int, lost: int}
     */
    private function orderOutcomes(): array
    {
        $counts = Order::query()
            ->manageableBy($this->actor)
            ->where('created_at', '>=', $this->period->start)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total): int => (int) $total);

        return [
            'placed' => $counts->sum(),
            'paid' => $counts[Order::STATUS_PAID] ?? 0,
            'open' => collect(Order::OPEN_STATUSES)->sum(fn (string $status): int => $counts[$status] ?? 0),
            'lost' => ($counts[Order::STATUS_EXPIRED] ?? 0) + ($counts[Order::STATUS_CANCELLED] ?? 0),
        ];
    }

    /**
     * How much studying happened in the viewer's courses this period.
     *
     * @return array<string, mixed>
     */
    private function learning(): array
    {
        $lessons = fn (): QueryBuilder => DB::table('lesson_completions')
            ->join('lessons', 'lessons.id', '=', 'lesson_completions.lesson_id')
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->whereIn('sections.course_id', $this->courseIds());
        $quizzes = fn (): QueryBuilder => DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('sections', 'sections.id', '=', 'quizzes.section_id')
            ->whereIn('sections.course_id', $this->courseIds());
        $certificates = fn (): QueryBuilder => Certificate::query()->whereIn('course_id', $this->courseIds())->toBase();

        $compare = fn (callable $query, string $column): array => [
            'current' => $query()->where($column, '>=', $this->period->start)->count(),
            'previous' => $query()->where($column, '>=', $this->period->previousStart)->where($column, '<', $this->period->start)->count(),
        ];

        return [
            'lessons_completed' => $compare($lessons, 'lesson_completions.created_at'),
            'quizzes_submitted' => $compare($quizzes, 'quiz_attempts.submitted_at'),
            'certificates' => $compare($certificates, 'issued_at'),
            // Only the latest visit is kept, so this has no earlier period to compare with.
            'active_learners' => $this->enrollments()->active()->where('last_accessed_at', '>=', $this->period->start)->distinct()->count('user_id'),
        ];
    }

    /**
     * The viewer's share of each course's payments in this period, as a subquery.
     *
     * @return Builder<Transaction>
     */
    private function periodRevenue(string $revenueColumn): Builder
    {
        return Transaction::query()
            ->selectRaw($revenueColumn === 'amount' ? 'coalesce(sum(transactions.amount), 0)' : 'coalesce(sum(transactions.instructor_amount), 0)')
            ->join('orders', 'orders.id', '=', 'transactions.order_id')
            ->whereColumn('orders.course_id', 'courses.id')
            ->where('transactions.paid_at', '>=', $this->period->start);
    }

    /**
     * The courses that earned most in this period, then by new students.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topCourses(string $revenueColumn): array
    {
        return $this->courses()
            ->select('courses.*')
            ->addSelect(['period_revenue' => $this->periodRevenue($revenueColumn)])
            ->withCount(['enrollments as new_enrollments' => fn (Builder $query) => $query->where('enrolled_at', '>=', $this->period->start)])
            ->withAvg('reviews', 'rating')
            ->with('instructor:id,name')
            ->orderByDesc('period_revenue')
            ->orderByDesc('new_enrollments')
            ->orderBy('title')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Course $course): array => [
                'slug' => $course->slug,
                'title' => $course->title,
                'thumbnail_url' => $course->thumbnail_url,
                'instructor' => $course->instructor?->name,
                'revenue' => (int) $course->getAttribute('period_revenue'),
                'new_enrollments' => (int) $course->getAttribute('new_enrollments'),
                'rating' => $course->getAttribute('reviews_avg_rating') === null ? null : round((float) $course->getAttribute('reviews_avg_rating'), 1),
            ])
            ->values()
            ->all();
    }

    /**
     * The instructors whose courses earned most in this period.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topInstructors(): array
    {
        $earnings = Transaction::query()
            ->join('orders', 'orders.id', '=', 'transactions.order_id')
            ->join('courses', 'courses.id', '=', 'orders.course_id')
            ->where('transactions.paid_at', '>=', $this->period->start)
            ->groupBy('courses.instructor_id')
            ->selectRaw('courses.instructor_id, sum(transactions.amount) as gross, sum(transactions.instructor_amount) as net, count(*) as sales')
            ->orderByDesc('gross')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->keyBy('instructor_id');

        $instructors = User::query()
            ->whereIn('id', $earnings->keys())
            ->withCount(['courses as published_courses' => fn (Builder $query) => $query->where('status', Course::STATUS_PUBLISHED)])
            ->get()
            ->keyBy('id');

        return $earnings
            ->filter(fn (Transaction $row): bool => $instructors->has($row->getAttribute('instructor_id')))
            ->map(function (Transaction $row) use ($instructors): array {
                $instructor = $instructors[$row->getAttribute('instructor_id')];

                return [
                    'id' => $instructor->id,
                    'slug' => $instructor->slug,
                    'name' => $instructor->name,
                    'avatar' => $instructor->avatar,
                    'courses' => (int) $instructor->getAttribute('published_courses'),
                    'sales' => (int) $row->getAttribute('sales'),
                    'gross' => (int) $row->getAttribute('gross'),
                    'net' => (int) $row->getAttribute('net'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Every course the instructor teaches with how it is doing, busiest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function coursePerformance(): array
    {
        $courses = $this->courses()
            ->where('status', '!=', Course::STATUS_ARCHIVED)
            ->select('courses.*')
            ->addSelect(['period_revenue' => $this->periodRevenue('instructor_amount')])
            ->withCount([
                'enrollments as active_students' => fn (Builder $query) => $query->where('status', Enrollment::STATUS_ACTIVE),
                'enrollments as new_enrollments' => fn (Builder $query) => $query->where('enrolled_at', '>=', $this->period->start),
                'lessons',
                'reviews',
            ])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('active_students')
            ->orderBy('title')
            ->limit(self::COURSE_TABLE_LIMIT)
            ->get();

        $ids = $courses->modelKeys();

        // Lessons finished by students who still take the course.
        $completions = DB::table('lesson_completions')
            ->join('lessons', 'lessons.id', '=', 'lesson_completions.lesson_id')
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->join('enrollments', fn ($join) => $join
                ->on('enrollments.user_id', '=', 'lesson_completions.user_id')
                ->on('enrollments.course_id', '=', 'sections.course_id'))
            ->where('enrollments.status', Enrollment::STATUS_ACTIVE)
            ->whereIn('sections.course_id', $ids)
            ->groupBy('sections.course_id')
            ->selectRaw('sections.course_id, count(*) as total')
            ->pluck('total', 'course_id');

        $certificates = Certificate::query()
            ->whereIn('course_id', $ids)
            ->selectRaw('course_id, count(*) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        return $courses->map(function (Course $course) use ($completions, $certificates): array {
            $students = (int) $course->getAttribute('active_students');
            $lessons = (int) $course->getAttribute('lessons_count');
            $possible = $students * $lessons;

            return [
                'slug' => $course->slug,
                'title' => $course->title,
                'status' => $course->status,
                'thumbnail_url' => $course->thumbnail_url,
                'students' => $students,
                'new_enrollments' => (int) $course->getAttribute('new_enrollments'),
                'average_progress' => $possible === 0 ? null : (int) round(min(100, (int) ($completions[$course->id] ?? 0) / $possible * 100)),
                'certificates' => (int) ($certificates[$course->id] ?? 0),
                'rating' => $course->getAttribute('reviews_avg_rating') === null ? null : round((float) $course->getAttribute('reviews_avg_rating'), 1),
                'reviews' => (int) $course->getAttribute('reviews_count'),
                'revenue' => (int) $course->getAttribute('period_revenue'),
            ];
        })->values()->all();
    }

    /**
     * @return Builder<LessonQuestion>
     */
    private function unansweredQuestions(): Builder
    {
        return LessonQuestion::query()
            ->whereHas('lesson.section.course', fn (Builder $course) => $course->manageableBy($this->actor))
            ->whereDoesntHave('replies');
    }

    /**
     * The oldest questions still waiting for an answer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionList(): array
    {
        return $this->unansweredQuestions()
            ->with(['user:id,name,avatar_path', 'lesson:id,section_id,title,slug', 'lesson.section:id,course_id', 'lesson.section.course:id,slug,title'])
            ->oldest()
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (LessonQuestion $question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'created_at' => $question->created_at?->toIso8601String(),
                'author' => $question->user->only(['id', 'name', 'avatar']),
                'lesson' => $question->lesson->only(['slug', 'title']),
                'course' => $question->lesson->section->course->only(['slug', 'title']),
            ])
            ->values()
            ->all();
    }

    /**
     * The latest confirmed payments, showing the viewer's share.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentPayments(string $revenueColumn): array
    {
        return $this->transactions()
            ->with(['order:id,number,course_title,user_id', 'order.user:id,name,avatar_path'])
            ->latest('paid_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'order_number' => $transaction->order->number,
                'course_title' => $transaction->order->course_title,
                'amount' => (int) $transaction->getAttribute($revenueColumn),
                'paid_at' => $transaction->paid_at->toIso8601String(),
                'student' => $transaction->order->user?->only(['id', 'name', 'avatar']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentEnrollments(): array
    {
        return $this->enrollments()
            ->with(['user:id,name,avatar_path', 'course:id,slug,title'])
            ->latest('enrolled_at')
            ->latest('id')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'is_self_enrolled' => $enrollment->isSelfEnrolled(),
                'student' => $enrollment->user->only(['id', 'name', 'avatar']),
                'course' => $enrollment->course->only(['slug', 'title']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentReviews(): array
    {
        return $this->reviews()
            ->with(['user:id,name,avatar_path', 'course:id,slug,title'])
            ->latest()
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (CourseReview $review): array => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'author' => $review->user->only(['id', 'name', 'avatar']),
                'course' => $review->course->only(['slug', 'title']),
            ])
            ->values()
            ->all();
    }
}
