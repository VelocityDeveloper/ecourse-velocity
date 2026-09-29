<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ConfirmOrder;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController as StudentOrderController;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderRejected;
use App\Support\PaymentSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * Status names for the CSV file, as the order pages show them.
     */
    private const array STATUS_LABELS = [
        Order::STATUS_PENDING => 'Menunggu pembayaran',
        Order::STATUS_AWAITING_CONFIRMATION => 'Menunggu konfirmasi',
        Order::STATUS_PAID => 'Lunas',
        Order::STATUS_EXPIRED => 'Kedaluwarsa',
        Order::STATUS_CANCELLED => 'Dibatalkan',
    ];

    /**
     * List orders, the ones waiting for confirmation first. Paid orders carry
     * their payment split, so this one page is both the to-do list and the books.
     */
    public function index(Request $request): Response
    {
        Order::expireOverdue();

        $this->validateFilters($request);

        $actor = $this->actor($request);

        $orders = $this->filtered($request)
            ->with(['user:id,name,email,avatar_path', 'course:id,slug', 'transaction.confirmer:id,name'])
            ->orderByRaw('case when status = ? then 0 else 1 end', [Order::STATUS_AWAITING_CONFIRMATION])
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                ...StudentOrderController::summary($order),
                'student' => $order->user->only(['id', 'name', 'email', 'avatar']),
                'proof_uploaded_at' => $order->proof_uploaded_at?->toIso8601String(),
                'payment' => $order->transaction === null ? null : [
                    'commission_rate' => (float) $order->transaction->commission_rate,
                    'commission_amount' => $order->transaction->commission_amount,
                    'instructor_amount' => $order->transaction->instructor_amount,
                    'confirmer' => $order->transaction->confirmer?->name,
                ],
            ]);

        // The money of the paid orders among the filtered ones.
        $payments = Transaction::query()->whereIn('order_id', $this->filtered($request, Order::STATUS_PAID)->select('orders.id'));

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'counts' => Order::query()->manageableBy($actor)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'statuses' => Order::STATUSES,
            'paid' => [
                'count' => (clone $payments)->count(),
                'amount' => (int) (clone $payments)->sum('amount'),
                'commission' => (int) (clone $payments)->sum('commission_amount'),
                'instructor' => (int) (clone $payments)->sum('instructor_amount'),
            ],
            'filters' => (object) $request->only(['status', 'search', 'from', 'to']),
            'paymentReady' => PaymentSettings::availableMethods() !== [],
            'canManage' => $actor->isAdmin(),
        ]);
    }

    /**
     * Download the filtered orders, with the payment split of the paid ones,
     * as a CSV file that opens in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);

        $orders = $this->filtered($request)
            ->with(['user:id,name,email', 'transaction.confirmer:id,name'])
            ->latest('id')
            ->get();

        // An instructor sees the platform's share as a cut from their sales.
        $platform = $request->user()?->isAdmin() ? 'Pemasukan platform' : 'Potongan platform';

        return response()->streamDownload(function () use ($orders, $platform): void {
            $out = fopen('php://output', 'w');
            assert($out !== false);

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nomor pesanan', 'Dibuat', 'Status', 'Siswa', 'Email', 'Kursus', 'Metode', 'Rekening/QRIS', 'Total (Rp)', 'Tanggal bayar', "{$platform} (%)", "{$platform} (Rp)", 'Bagian instruktur (Rp)', 'Dikonfirmasi oleh'], ';');

            foreach ($orders as $order) {
                $details = $order->payment_details ?? [];
                $payment = $order->transaction;

                fputcsv($out, [
                    $order->number,
                    $order->created_at?->format('Y-m-d H:i'),
                    self::STATUS_LABELS[$order->status] ?? $order->status,
                    $order->user->name,
                    $order->user->email,
                    $order->course_title,
                    match ($order->payment_method) {
                        Order::METHOD_QRIS => 'QRIS',
                        Order::METHOD_BANK_TRANSFER => 'Transfer bank',
                        default => '',
                    },
                    isset($details['bank']) ? "{$details['bank']} {$details['account_number']}" : ($details['qris_name'] ?? ''),
                    $order->total,
                    $order->paid_at?->format('Y-m-d H:i') ?? '',
                    $payment === null ? '' : str_replace('.', ',', (string) $payment->commission_rate),
                    $payment->commission_amount ?? '',
                    $payment->instructor_amount ?? '',
                    $payment?->confirmer->name ?? '',
                ], ';');
            }

            fclose($out);
        }, 'pesanan-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Show one order with its payment proof.
     *
     * Instructors may look at the orders of their own courses. Only admins
     * handle the payment, so only they get the actions and the student's
     * payment proof, sender name and note.
     */
    public function show(Request $request, Order $order): Response
    {
        $actor = $this->actor($request);

        abort_unless(Order::query()->manageableBy($actor)->whereKey($order->id)->exists(), 403);

        $order->load(['user:id,name,email,avatar_path', 'course:id,slug', 'handler:id,name', 'transaction.confirmer:id,name']);

        return Inertia::render('admin/Orders/Show', [
            'order' => [
                ...StudentOrderController::summary($order),
                'price' => $order->price,
                'payment_details' => $order->payment_details,
                'payer_name' => $actor->isAdmin() ? $order->payer_name : null,
                'payer_note' => $actor->isAdmin() ? $order->payer_note : null,
                'proof_uploaded_at' => $order->proof_uploaded_at?->toIso8601String(),
                'proof_url' => $order->proof_path === null || ! $actor->isAdmin() ? null : route('orders.proof.show', $order),
                'proof_is_pdf' => $order->proof_path !== null && str_ends_with(strtolower($order->proof_path), '.pdf'),
                'rejection_reason' => $order->rejection_reason,
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'handler' => $order->handler?->only(['id', 'name']),
                'student' => $order->user->only(['id', 'name', 'email', 'avatar']),
                'transaction' => $order->transaction === null ? null : [
                    'id' => $order->transaction->id,
                    'amount' => $order->transaction->amount,
                    'commission_rate' => (float) $order->transaction->commission_rate,
                    'commission_amount' => $order->transaction->commission_amount,
                    'instructor_amount' => $order->transaction->instructor_amount,
                    'note' => $order->transaction->note,
                    'paid_at' => $order->transaction->paid_at->toIso8601String(),
                    'confirmer' => $order->transaction->confirmer?->only(['id', 'name']),
                ],
            ],
            'canManage' => $actor->isAdmin(),
        ]);
    }

    /**
     * Confirm the payment: the student is enrolled right away.
     */
    public function confirm(Request $request, Order $order, ConfirmOrder $confirmOrder): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        if (! $order->isOpen()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Only an open order can be confirmed.')]);

            return back();
        }

        $confirmOrder($order, $this->actor($request), $request->filled('note') ? $request->string('note')->toString() : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment confirmed. The student is now enrolled.')]);

        return to_route('admin.orders.show', $order);
    }

    /**
     * Send the order back to the student to pay or upload proof again.
     */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        if ($order->status !== Order::STATUS_AWAITING_CONFIRMATION) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Only an order waiting for confirmation can be rejected.')]);

            return back();
        }

        if ($order->proof_path !== null) {
            Storage::disk(Order::PROOF_DISK)->delete($order->proof_path);
        }

        $order->update([
            'status' => Order::STATUS_PENDING,
            'rejection_reason' => $request->string('reason')->toString(),
            'proof_path' => null,
            'proof_uploaded_at' => null,
            'handled_by' => $this->actor($request)->id,
            'expires_at' => now()->addHours(PaymentSettings::expiryHours()),
        ]);

        $order->user->notify(new OrderRejected($order));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment rejected. The student can upload a new proof.')]);

        return to_route('admin.orders.show', $order);
    }

    /**
     * Cancel an order that will not be paid.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        if ($order->isOpen()) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'handled_by' => $this->actor($request)->id,
            ]);

            $order->user->notify(new OrderCancelled($order));

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);
        }

        return to_route('admin.orders.show', $order);
    }

    /**
     * Get the authenticated user making the request.
     */
    private function validateFilters(Request $request): void
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    /**
     * The orders the user may see, narrowed by the request's filters. The dates
     * match the payment date of paid orders and the order date of the others.
     * A given status replaces the requested one.
     *
     * @return Builder<Order>
     */
    private function filtered(Request $request, ?string $status = null): Builder
    {
        $status ??= $request->string('status')->toString();
        $search = $request->string('search')->trim()->toString();
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString())->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay() : null;

        $inRange = function (Builder $query, string $column) use ($from, $to): void {
            $query
                ->when($from !== null, fn (Builder $query) => $query->where($column, '>=', $from))
                ->when($to !== null, fn (Builder $query) => $query->where($column, '<=', $to));
        };

        return Order::query()
            ->manageableBy($this->actor($request))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('number', 'like', "%{$search}%")
                ->orWhere('course_title', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->when($from !== null || $to !== null, fn (Builder $query) => $query->where(fn (Builder $dated) => $dated
                ->where(fn (Builder $paid) => $paid->where('status', Order::STATUS_PAID)->tap(fn (Builder $q) => $inRange($q, 'paid_at')))
                ->orWhere(fn (Builder $other) => $other->where('status', '!=', Order::STATUS_PAID)->tap(fn (Builder $q) => $inRange($q, 'created_at')))));
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
