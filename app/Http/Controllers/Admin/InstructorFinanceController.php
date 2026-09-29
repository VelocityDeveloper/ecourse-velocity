<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstructorFinanceController extends Controller
{
    /**
     * The orderings the instructor list offers, keyed by their query value.
     */
    public const array SORTS = ['revenue', 'students', 'courses', 'newest', 'name'];

    /**
     * Narrow the list to instructors who sold something in the period, or who did not.
     */
    public const array SALES_FILTERS = ['with_sales', 'without_sales'];

    /**
     * How many months the revenue chart on an instructor's page covers.
     */
    private const int CHART_MONTHS = 12;

    /**
     * List every instructor with their courses, students, rating and revenue.
     */
    public function index(Request $request): Response
    {
        $this->validateFilters($request);

        $instructors = $this->filtered($request)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $instructor): array => $this->row($instructor));

        $period = $this->periodTransactions($request);

        return Inertia::render('admin/Finance/Instructors/Index', [
            'instructors' => $instructors,
            'summary' => [
                'instructors' => User::query()->where('role', User::ROLE_INSTRUCTOR)->count(),
                'with_sales' => DB::query()->fromSub($this->instructors($request)->toBase(), 'instructors')->where('gross', '>', 0)->count(),
                'gross' => (int) (clone $period)->sum('transactions.amount'),
                'commission' => (int) (clone $period)->sum('transactions.commission_amount'),
                'revenue' => (int) (clone $period)->sum('transactions.instructor_amount'),
                'transactions' => (clone $period)->count(),
            ],
            'filters' => (object) $request->only(['search', 'from', 'to', 'sort', 'sales']),
        ]);
    }

    /**
     * One instructor's revenue: totals, per course, per month and latest payments.
     */
    public function show(Request $request, User $user): Response
    {
        abort_unless($user->isInstructor(), 404);

        $this->validateFilters($request);

        $instructor = $this->instructors($request)->whereKey($user->id)->firstOrFail();
        $payments = fn (): Builder => $this->transactionsOf($user)->tap(fn (Builder $query) => $this->inPeriod($query, $request));

        $courses = Course::query()
            ->where('instructor_id', $user->id)
            ->withCount(['enrollments as students_count' => fn (Builder $query) => $query->where('status', Enrollment::STATUS_ACTIVE)])
            ->withAvg('reviews', 'rating')
            ->addSelect([
                'revenue' => $this->courseTransactions($request)->selectRaw('coalesce(sum(transactions.instructor_amount), 0)'),
                'gross' => $this->courseTransactions($request)->selectRaw('coalesce(sum(transactions.amount), 0)'),
                'commission' => $this->courseTransactions($request)->selectRaw('coalesce(sum(transactions.commission_amount), 0)'),
                'sales_count' => $this->courseTransactions($request)->selectRaw('count(*)'),
            ])
            ->orderByDesc('revenue')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'status' => $course->status,
                'price' => (int) $course->price,
                'students_count' => (int) $course->getAttribute('students_count'),
                'rating' => $course->getAttribute('reviews_avg_rating') === null ? null : round((float) $course->getAttribute('reviews_avg_rating'), 1),
                'revenue' => (int) $course->getAttribute('revenue'),
                'gross' => (int) $course->getAttribute('gross'),
                'commission' => (int) $course->getAttribute('commission'),
                'sales_count' => (int) $course->getAttribute('sales_count'),
            ]);

        $latest = $payments()
            ->with(['order:id,number,course_title', 'user:id,name,email'])
            ->latest('transactions.paid_at')
            ->limit(10)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'commission_rate' => (float) $transaction->commission_rate,
                'commission_amount' => $transaction->commission_amount,
                'instructor_amount' => $transaction->instructor_amount,
                'paid_at' => $transaction->paid_at->toIso8601String(),
                'order_number' => $transaction->order->number,
                'course_title' => $transaction->order->course_title,
                'student' => $transaction->user->only(['name', 'email']),
            ]);

        return Inertia::render('admin/Finance/Instructors/Show', [
            'instructor' => $this->row($instructor) + [
                'bio' => $user->bio,
                'revenue_all_time' => (int) $this->transactionsOf($user)->sum('transactions.instructor_amount'),
            ],
            'courses' => $courses,
            'monthly' => $this->monthly($user),
            'transactions' => $latest,
            'filters' => (object) $request->only(['from', 'to']),
        ]);
    }

    /**
     * Download the filtered instructor list with their revenue as a CSV file that opens in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);

        $instructors = $this->filtered($request)->get();

        return response()->streamDownload(function () use ($instructors): void {
            $out = fopen('php://output', 'w');
            assert($out !== false);

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nama', 'Email', 'Bergabung', 'Kursus terbit', 'Total kursus', 'Siswa aktif', 'Rating', 'Transaksi', 'Penjualan periode (Rp)', 'Pemasukan platform periode (Rp)', 'Pendapatan bersih periode (Rp)', 'Pendapatan bersih hari ini (Rp)', 'Pendapatan bersih bulan ini (Rp)', 'Pendapatan bersih total (Rp)', 'Sudah ditarik (Rp)', 'Penarikan diproses (Rp)', 'Saldo tersedia (Rp)'], ';');

            foreach ($instructors as $instructor) {
                $row = $this->row($instructor);

                fputcsv($out, [
                    $row['name'],
                    $row['email'],
                    Carbon::parse($row['joined_at'])->format('Y-m-d'),
                    $row['published_courses_count'],
                    $row['courses_count'],
                    $row['students_count'],
                    $row['rating'] ?? '',
                    $row['sales_count'],
                    $row['gross'],
                    $row['commission'],
                    $row['revenue'],
                    $row['revenue_today'],
                    $row['revenue_this_month'],
                    $row['revenue_all_time'],
                    $row['withdrawn'],
                    $row['withdrawal_pending'],
                    $row['balance'],
                ], ';');
            }

            fclose($out);
        }, 'keuangan-instruktur-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'sales' => ['nullable', 'in:'.implode(',', self::SALES_FILTERS)],
        ]);
    }

    /**
     * Instructors narrowed by search and sales, in the requested order.
     *
     * @return Builder<User>
     */
    private function filtered(Request $request): Builder
    {
        $search = $request->string('search')->toString();

        $query = $this->instructors($request)
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('headline', 'like', "%{$search}%")))
            ->when($request->input('sales') === 'with_sales', fn (Builder $query) => $query->whereExists($this->salesOfInstructor($request)))
            ->when($request->input('sales') === 'without_sales', fn (Builder $query) => $query->whereNotExists($this->salesOfInstructor($request)));

        return match ($request->input('sort', 'revenue')) {
            'students' => $query->orderByDesc('students_count')->orderBy('name'),
            'courses' => $query->orderByDesc('courses_count')->orderBy('name'),
            'newest' => $query->latest(),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('revenue')->orderByDesc('revenue_all_time')->orderBy('name'),
        };
    }

    /**
     * Payments in the period for the courses of the outer `users` row. A subquery rather
     * than HAVING on the revenue column, which SQLite refuses without GROUP BY.
     */
    private function salesOfInstructor(Request $request): QueryBuilder
    {
        return $this->joinedTransactions($request)
            ->whereColumn('courses.instructor_id', 'users.id')
            ->select('transactions.id')
            ->toBase();
    }

    /**
     * Instructors with their counts and revenue columns selected.
     *
     * @return Builder<User>
     */
    private function instructors(Request $request): Builder
    {
        $ownCourse = fn (Builder $query) => $query->whereColumn('courses.instructor_id', 'users.id');

        return User::query()
            ->where('role', User::ROLE_INSTRUCTOR)
            ->select('users.*')
            ->withCount([
                'courses',
                'courses as published_courses_count' => fn (Builder $query) => $query->where('status', Course::STATUS_PUBLISHED),
            ])
            ->addSelect([
                'students_count' => Enrollment::query()
                    ->selectRaw('count(*)')
                    ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                    ->where('enrollments.status', Enrollment::STATUS_ACTIVE)
                    ->tap($ownCourse),
                'rating' => CourseReview::query()
                    ->selectRaw('avg(course_reviews.rating)')
                    ->join('courses', 'courses.id', '=', 'course_reviews.course_id')
                    ->tap($ownCourse),
                'revenue' => $this->revenueSubquery($request)->tap($ownCourse),
                'gross' => $this->joinedTransactions($request)->selectRaw('coalesce(sum(transactions.amount), 0)')->tap($ownCourse),
                'commission' => $this->joinedTransactions($request)->selectRaw('coalesce(sum(transactions.commission_amount), 0)')->tap($ownCourse),
                'sales_count' => $this->salesCountSubquery($request)->tap($ownCourse),
                'revenue_today' => $this->revenueSubquery()->where('transactions.paid_at', '>=', now()->startOfDay())->tap($ownCourse),
                'revenue_this_month' => $this->revenueSubquery()->where('transactions.paid_at', '>=', now()->startOfMonth())->tap($ownCourse),
                'revenue_all_time' => $this->revenueSubquery()->tap($ownCourse),
                'withdrawn' => $this->withdrawalSubquery(Withdrawal::STATUS_PAID),
                'withdrawal_pending' => $this->withdrawalSubquery(Withdrawal::STATUS_PENDING),
            ]);
    }

    /**
     * The total of the outer `users` row's withdrawals with the given status.
     *
     * @return Builder<Withdrawal>
     */
    private function withdrawalSubquery(string $status): Builder
    {
        return Withdrawal::query()
            ->selectRaw('coalesce(sum(withdrawals.amount), 0)')
            ->whereColumn('withdrawals.user_id', 'users.id')
            ->where('withdrawals.status', $status);
    }

    /**
     * The instructor's share (after the platform commission) of payments for courses,
     * joined up to `courses` so callers can pick whose courses.
     * Without a request it covers all time; with one, the requested period.
     *
     * @return Builder<Transaction>
     */
    private function revenueSubquery(?Request $request = null): Builder
    {
        return $this->joinedTransactions($request)->selectRaw('coalesce(sum(transactions.instructor_amount), 0)');
    }

    /**
     * @return Builder<Transaction>
     */
    private function salesCountSubquery(Request $request): Builder
    {
        return $this->joinedTransactions($request)->selectRaw('count(*)');
    }

    /**
     * @return Builder<Transaction>
     */
    private function joinedTransactions(?Request $request): Builder
    {
        $query = Transaction::query()
            ->join('orders', 'orders.id', '=', 'transactions.order_id')
            ->join('courses', 'courses.id', '=', 'orders.course_id');

        if ($request !== null) {
            $this->inPeriod($query, $request);
        }

        return $query;
    }

    /**
     * Payments for the course of the outer `courses` query, without joining `courses`
     * again (an inner join would shadow the outer table).
     *
     * @return Builder<Transaction>
     */
    private function courseTransactions(Request $request): Builder
    {
        $query = Transaction::query()
            ->join('orders', 'orders.id', '=', 'transactions.order_id')
            ->whereColumn('orders.course_id', 'courses.id');

        $this->inPeriod($query, $request);

        return $query;
    }

    /**
     * All payments for instructors' courses in the requested period.
     *
     * @return Builder<Transaction>
     */
    private function periodTransactions(Request $request): Builder
    {
        return $this->joinedTransactions($request)
            ->join('users', 'users.id', '=', 'courses.instructor_id')
            ->where('users.role', User::ROLE_INSTRUCTOR);
    }

    /**
     * Every payment for one instructor's courses.
     *
     * @return Builder<Transaction>
     */
    private function transactionsOf(User $instructor): Builder
    {
        return $this->joinedTransactions(null)
            ->select('transactions.*')
            ->where('courses.instructor_id', $instructor->id);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    private function inPeriod(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('from'), fn (Builder $query) => $query->where('transactions.paid_at', '>=', Carbon::parse($request->string('from')->toString())->startOfDay()))
            ->when($request->filled('to'), fn (Builder $query) => $query->where('transactions.paid_at', '<=', Carbon::parse($request->string('to')->toString())->endOfDay()));
    }

    /**
     * Revenue for each of the last months, oldest first, with empty months included.
     *
     * @return list<array{month: string, revenue: int}>
     */
    private function monthly(User $instructor): array
    {
        $start = now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        $totals = $this->transactionsOf($instructor)
            ->where('transactions.paid_at', '>=', $start)
            ->get(['transactions.instructor_amount', 'transactions.paid_at'])
            ->groupBy(fn (Transaction $transaction): string => $transaction->paid_at->format('Y-m'))
            ->map(fn ($group): int => (int) $group->sum('instructor_amount'));

        $months = [];

        for ($i = 0; $i < self::CHART_MONTHS; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $months[] = ['month' => $key, 'revenue' => (int) ($totals[$key] ?? 0)];
        }

        return $months;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $instructor): array
    {
        $rating = $instructor->getAttribute('rating');
        $allTime = (int) $instructor->getAttribute('revenue_all_time');
        $withdrawn = (int) $instructor->getAttribute('withdrawn');
        $pending = (int) $instructor->getAttribute('withdrawal_pending');

        return [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'email' => $instructor->email,
            'slug' => $instructor->slug,
            'headline' => $instructor->headline,
            'avatar' => $instructor->avatar,
            'joined_at' => $instructor->created_at?->toIso8601String(),
            'courses_count' => (int) $instructor->getAttribute('courses_count'),
            'published_courses_count' => (int) $instructor->getAttribute('published_courses_count'),
            'students_count' => (int) $instructor->getAttribute('students_count'),
            'rating' => $rating === null ? null : round((float) $rating, 1),
            'sales_count' => (int) $instructor->getAttribute('sales_count'),
            'revenue' => (int) $instructor->getAttribute('revenue'),
            'gross' => (int) $instructor->getAttribute('gross'),
            'commission' => (int) $instructor->getAttribute('commission'),
            'revenue_today' => (int) $instructor->getAttribute('revenue_today'),
            'revenue_this_month' => (int) $instructor->getAttribute('revenue_this_month'),
            'revenue_all_time' => $allTime,
            'withdrawn' => $withdrawn,
            'withdrawal_pending' => $pending,
            // Same rule as InstructorBalance::available(), from the columns already selected.
            'balance' => max(0, $allTime - $withdrawn - $pending),
        ];
    }
}
