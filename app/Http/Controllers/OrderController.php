<?php

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrder;
use App\Http\Requests\SubmitPaymentProofRequest;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * Show the checkout page of a paid course: what is bought and how to pay.
     */
    public function checkout(Request $request, Course $course): Response|RedirectResponse
    {
        if ($redirect = $this->refuseCheckout($request, $course)) {
            return $redirect;
        }

        $course->loadMissing(['category:id,name,slug', 'instructor:id,name']);

        return Inertia::render('orders/Checkout', [
            'course' => [
                'id' => $course->id,
                'slug' => $course->slug,
                'url' => $course->permalink(),
                'title' => $course->title,
                'thumbnail_url' => $course->thumbnail_url,
                'level' => $course->level,
                'category' => $course->category?->only(['id', 'name']),
                'instructor' => $course->instructor?->only(['id', 'name']),
                'price' => (int) round((float) $course->price),
            ],
            'buyer' => $this->actor($request)->only(['name', 'email']),
            'methods' => PaymentSettings::availableMethods(),
            'expiryHours' => PaymentSettings::expiryHours(),
        ]);
    }

    /**
     * Place the order: the invoice is created now, with the chosen payment method.
     */
    public function store(Request $request, Course $course, PlaceOrder $placeOrder): RedirectResponse
    {
        if ($redirect = $this->refuseCheckout($request, $course)) {
            return $redirect;
        }

        $request->validate([
            'payment_method' => ['required', 'string', Rule::in(PaymentSettings::availableMethods())],
        ]);

        $order = $placeOrder($this->actor($request), $course, $request->string('payment_method')->toString());

        return to_route('orders.show', $order);
    }

    /**
     * List the student's orders, newest first.
     */
    public function index(Request $request): Response
    {
        Order::expireOverdue();

        return Inertia::render('orders/Index', [
            'orders' => $this->actor($request)->orders()
                ->with('course:id,slug')
                ->latest('id')
                ->get()
                ->map(fn (Order $order): array => self::summary($order))
                ->all(),
        ]);
    }

    /**
     * Show the invoice of one order with how to pay it.
     */
    public function show(Request $request, Order $order): Response
    {
        $this->authorizeOwner($request, $order);
        Order::expireOverdue();
        $order->refresh();

        return Inertia::render('orders/Show', [
            'order' => $this->detail($order),
            'buyer' => $this->actor($request)->only(['name', 'email']),
            'payment' => PaymentSettings::forCheckout(),
        ]);
    }

    /**
     * Show the separate page where the student sends the payment proof.
     */
    public function createProof(Request $request, Order $order): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        Order::expireOverdue();
        $order->refresh();

        if (! $order->acceptsProof()) {
            return to_route('orders.show', $order);
        }

        return Inertia::render('orders/Proof', [
            'order' => $this->detail($order),
            'bankAccounts' => PaymentSettings::bankAccounts(),
            'qrisName' => PaymentSettings::forCheckout()['qris_name'],
            'maxProofKb' => SubmitPaymentProofRequest::MAX_PROOF_KB,
        ]);
    }

    /**
     * Save the payment proof and hand the order to an admin for checking.
     */
    public function submitProof(SubmitPaymentProofRequest $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);
        Order::expireOverdue();
        $order->refresh();

        if (! $order->acceptsProof()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This order can no longer be paid.')]);

            return back();
        }

        $method = $order->payment_method ?? Order::METHOD_BANK_TRANSFER;
        $details = $method === Order::METHOD_BANK_TRANSFER
            ? PaymentSettings::bankAccounts()[$request->integer('bank_index')]
            : ['qris_name' => (string) (PaymentSettings::forCheckout()['qris_name'] ?? 'QRIS')];

        $file = $request->file('proof');
        assert($file !== null && ! is_array($file));

        if ($order->proof_path !== null) {
            Storage::disk(Order::PROOF_DISK)->delete($order->proof_path);
        }

        $order->update([
            'status' => Order::STATUS_AWAITING_CONFIRMATION,
            'payment_details' => $details,
            'proof_path' => $file->store('payment-proofs', Order::PROOF_DISK),
            'payer_name' => $request->string('payer_name')->toString(),
            'payer_note' => $request->filled('payer_note') ? $request->string('payer_note')->toString() : null,
            'proof_uploaded_at' => now(),
            'rejection_reason' => null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment proof sent. We will confirm it soon.')]);

        return to_route('orders.show', $order);
    }

    /**
     * Cancel an order the student no longer wants to pay.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if ($order->isOpen()) {
            $order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);
        }

        return to_route('orders.show', $order);
    }

    /**
     * Show the uploaded payment proof to its owner or an admin.
     */
    public function proof(Request $request, Order $order): StreamedResponse
    {
        $actor = $this->actor($request);
        abort_unless($actor->id === $order->user_id || $actor->isAdmin(), 403);
        abort_if($order->proof_path === null || ! Storage::disk(Order::PROOF_DISK)->exists($order->proof_path), 404);

        return Storage::disk(Order::PROOF_DISK)->response($order->proof_path);
    }

    /**
     * The fields every order list shows.
     *
     * @return array<string, mixed>
     */
    public static function summary(Order $order): array
    {
        return [
            'number' => $order->number,
            'course_id' => $order->course_id,
            'course_slug' => $order->course?->slug,
            'course_title' => $order->course_title,
            'total' => $order->total,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'created_at' => $order->created_at?->toIso8601String(),
            'expires_at' => $order->expires_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
        ];
    }

    /**
     * The invoice fields the order pages show.
     *
     * @return array<string, mixed>
     */
    private function detail(Order $order): array
    {
        return [
            ...self::summary($order),
            'price' => $order->price,
            'payment_details' => $order->payment_details,
            'payer_name' => $order->payer_name,
            'payer_note' => $order->payer_note,
            'proof_uploaded_at' => $order->proof_uploaded_at?->toIso8601String(),
            'has_proof' => $order->proof_path !== null,
            'rejection_reason' => $order->rejection_reason,
            'accepts_proof' => $order->acceptsProof(),
            'seconds_left' => $order->status === Order::STATUS_PENDING && $order->expires_at !== null
                ? max(0, (int) now()->diffInSeconds($order->expires_at, false))
                : null,
        ];
    }

    /**
     * Send the student elsewhere when the course cannot be bought (again) right now.
     */
    private function refuseCheckout(Request $request, Course $course): ?RedirectResponse
    {
        Gate::authorize('enroll', $course);

        $student = $this->actor($request);

        if ($course->enrollments()->active()->where('user_id', $student->id)->exists()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('You are already enrolled in this course.')]);

            return to_route('catalog.show', $course->permalinkParameters());
        }

        if (! $course->isPaid()) {
            return to_route('catalog.show', $course->permalinkParameters());
        }

        Order::expireOverdue();
        $open = $student->orders()->open()->where('course_id', $course->id)->latest('id')->first();

        return $open === null ? null : to_route('orders.show', $open);
    }

    /**
     * Only the student who placed the order may open it here.
     */
    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($this->actor($request)->id === $order->user_id, 403);
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
