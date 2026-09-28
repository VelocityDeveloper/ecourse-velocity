<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ConfirmOrder;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController as StudentOrderController;
use App\Models\Order;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * List orders, the ones waiting for confirmation first.
     */
    public function index(Request $request): Response
    {
        Order::expireOverdue();

        $request->validate([
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $actor = $this->actor($request);
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $orders = Order::query()
            ->manageableBy($actor)
            ->with(['user:id,name,email,avatar_path', 'course:id,slug'])
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('number', 'like', "%{$search}%")
                ->orWhere('course_title', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->orderByRaw('case when status = ? then 0 else 1 end', [Order::STATUS_AWAITING_CONFIRMATION])
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                ...StudentOrderController::summary($order),
                'student' => $order->user->only(['id', 'name', 'email', 'avatar']),
                'proof_uploaded_at' => $order->proof_uploaded_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'counts' => Order::query()->manageableBy($actor)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'statuses' => Order::STATUSES,
            'filters' => $request->only(['status', 'search']),
            'paymentReady' => PaymentSettings::availableMethods() !== [],
            'canManage' => $actor->isAdmin(),
        ]);
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

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);
        }

        return to_route('admin.orders.show', $order);
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
