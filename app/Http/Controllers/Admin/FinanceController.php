<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keuangan → Ringkasan: the money numbers an admin checks first, and what waits
 * for them. Every figure links on to the tab that holds the details.
 */
class FinanceController extends Controller
{
    /**
     * How many waiting orders and withdrawals the overview lists.
     */
    private const int WAITING_LIMIT = 5;

    public function index(Request $request): Response|RedirectResponse
    {
        // An instructor's overview is their balance, on the withdrawal page.
        if (! $request->user()?->isAdmin()) {
            return to_route('admin.withdrawals.index');
        }

        $today = Transaction::query()->where('paid_at', '>=', now()->startOfDay());
        $month = Transaction::query()->where('paid_at', '>=', now()->startOfMonth());

        $waitingOrders = Order::query()->where('status', Order::STATUS_AWAITING_CONFIRMATION);
        $waitingWithdrawals = Withdrawal::query()->pending();

        return Inertia::render('admin/Finance/Index', [
            'sales' => [
                'today' => (int) (clone $today)->sum('amount'),
                'today_count' => (clone $today)->count(),
                'month' => (int) (clone $month)->sum('amount'),
                'month_count' => (clone $month)->count(),
                'commission_month' => (int) (clone $month)->sum('commission_amount'),
                'instructor_month' => (int) (clone $month)->sum('instructor_amount'),
            ],
            'instructors' => [
                'balance' => $this->instructorBalances(),
                'paid_out' => (int) Withdrawal::query()->where('status', Withdrawal::STATUS_PAID)->sum('amount'),
            ],
            'waiting' => [
                'orders_count' => (clone $waitingOrders)->count(),
                'orders_total' => (int) (clone $waitingOrders)->sum('total'),
                'withdrawals_count' => (clone $waitingWithdrawals)->count(),
                'withdrawals_total' => (int) (clone $waitingWithdrawals)->sum('amount'),
            ],
            'waitingOrders' => (clone $waitingOrders)
                ->with('user:id,name')
                ->oldest('updated_at')
                ->limit(self::WAITING_LIMIT)
                ->get()
                ->map(fn (Order $order): array => [
                    'number' => $order->number,
                    'course_title' => $order->course_title,
                    'total' => (int) $order->total,
                    'student' => $order->user->name,
                    'updated_at' => $order->updated_at?->toIso8601String(),
                ]),
            'waitingWithdrawals' => (clone $waitingWithdrawals)
                ->with('user:id,name')
                ->oldest()
                ->limit(self::WAITING_LIMIT)
                ->get()
                ->map(fn (Withdrawal $withdrawal): array => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'bank_name' => $withdrawal->bank_name,
                    'instructor' => $withdrawal->user->name,
                    'created_at' => $withdrawal->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * What all instructors together can still withdraw, each clamped at zero
     * like InstructorBalance::available().
     */
    private function instructorBalances(): int
    {
        $earned = Transaction::query()
            ->join('orders', 'orders.id', '=', 'transactions.order_id')
            ->join('courses', 'courses.id', '=', 'orders.course_id')
            ->selectRaw('coalesce(sum(transactions.instructor_amount), 0)')
            ->whereColumn('courses.instructor_id', 'users.id');

        $withdrawn = Withdrawal::query()
            ->selectRaw('coalesce(sum(withdrawals.amount), 0)')
            ->whereColumn('withdrawals.user_id', 'users.id')
            ->whereIn('withdrawals.status', [Withdrawal::STATUS_PAID, Withdrawal::STATUS_PENDING]);

        return (int) User::query()
            ->where('role', User::ROLE_INSTRUCTOR)
            ->select('users.id')
            ->addSelect(['earned' => $earned, 'withdrawn' => $withdrawn])
            ->get()
            ->sum(fn (User $instructor): int => max(0, (int) $instructor->getAttribute('earned') - (int) $instructor->getAttribute('withdrawn')));
    }
}
