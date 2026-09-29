<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalProcessed;
use App\Notifications\WithdrawalRequested;
use App\Support\InstructorBalance;
use App\Support\PaymentSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Penarikan Dana": instructors ask to be paid out their earnings, and the
 * admin transfers the money by hand and records it here.
 */
class WithdrawalController extends Controller
{
    /**
     * Instructors see their balance and requests; the admin sees every request.
     */
    public function index(Request $request): Response
    {
        $user = $this->actor($request);

        return $user->isAdmin() ? $this->adminIndex($request) : $this->instructorIndex($user);
    }

    /**
     * Ask for a payout.
     */
    public function store(Request $request): RedirectResponse
    {
        $instructor = $this->actor($request);
        abort_unless($instructor->isInstructor(), 403);

        $minimum = PaymentSettings::withdrawalMinimum();

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:'.max(1, $minimum)],
            'bank_name' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9 .\-]+$/'],
            'account_name' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Lock the instructor row so two quick requests cannot both spend the same balance.
        $withdrawal = DB::transaction(function () use ($instructor, $validated): Withdrawal {
            User::query()->whereKey($instructor->id)->lockForUpdate()->first();

            if ($instructor->withdrawals()->pending()->exists()) {
                throw ValidationException::withMessages(['amount' => __('You already have a withdrawal waiting to be processed.')]);
            }

            $available = InstructorBalance::for($instructor)->available();

            if ($validated['amount'] > $available) {
                throw ValidationException::withMessages(['amount' => __('The amount is more than your available balance.')]);
            }

            return $instructor->withdrawals()->create([
                ...$validated,
                'status' => Withdrawal::STATUS_PENDING,
            ]);
        });

        User::notifyAdmins(new WithdrawalRequested($withdrawal));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Withdrawal requested. The admin will transfer it and let you know.')]);

        return to_route('admin.withdrawals.index');
    }

    /**
     * Take back a request the admin has not handled yet.
     */
    public function cancel(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->user_id === $this->actor($request)->id, 403);

        if (! $withdrawal->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This withdrawal has already been processed.')]);

            return back();
        }

        $withdrawal->update(['status' => Withdrawal::STATUS_CANCELLED]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Withdrawal cancelled.')]);

        return back();
    }

    /**
     * Record that the admin transferred the money, with an optional receipt.
     */
    public function pay(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $request->validate([
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $withdrawal->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This withdrawal has already been processed.')]);

            return back();
        }

        $proof = $request->file('proof');

        $withdrawal->update([
            'status' => Withdrawal::STATUS_PAID,
            'admin_note' => $request->input('note'),
            'proof_path' => $proof !== null && ! is_array($proof)
                ? $proof->store(Withdrawal::PROOF_DIRECTORY, Withdrawal::PROOF_DISK)
                : null,
            'processed_by' => $this->actor($request)->id,
            'processed_at' => now(),
        ]);

        $withdrawal->user->notify(new WithdrawalProcessed($withdrawal));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Withdrawal marked as paid.')]);

        return back();
    }

    /**
     * Turn a request down. The amount goes back to the instructor's balance.
     */
    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:1000']], attributes: ['note' => __('rejection reason')]);

        if (! $withdrawal->isPending()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This withdrawal has already been processed.')]);

            return back();
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_REJECTED,
            'admin_note' => $request->string('note')->toString(),
            'processed_by' => $this->actor($request)->id,
            'processed_at' => now(),
        ]);

        $withdrawal->user->notify(new WithdrawalProcessed($withdrawal));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Withdrawal rejected. The amount is back in the instructor balance.')]);

        return back();
    }

    /**
     * Show the transfer receipt to the instructor it belongs to, or an admin.
     */
    public function proof(Request $request, Withdrawal $withdrawal): StreamedResponse
    {
        $actor = $this->actor($request);
        abort_unless($actor->isAdmin() || $actor->id === $withdrawal->user_id, 403);
        abort_if($withdrawal->proof_path === null || ! Storage::disk(Withdrawal::PROOF_DISK)->exists($withdrawal->proof_path), 404);

        return Storage::disk(Withdrawal::PROOF_DISK)->response($withdrawal->proof_path);
    }

    private function instructorIndex(User $instructor): Response
    {
        $withdrawals = $instructor->withdrawals()
            ->with('processor:id,name')
            ->latest('id')
            ->paginate(10);

        $withdrawals->getCollection()->transform(fn (Withdrawal $withdrawal): array => $this->row($withdrawal));

        $last = $instructor->withdrawals()->latest('id')->first(['bank_name', 'account_number', 'account_name']);

        return Inertia::render('withdrawals/Index', [
            'balance' => InstructorBalance::for($instructor)->toArray(),
            'withdrawals' => $withdrawals,
            'minimum' => PaymentSettings::withdrawalMinimum(),
            'hasPending' => $instructor->withdrawals()->pending()->exists(),
            // The account used last time, so a regular payout is one click.
            'lastAccount' => $last?->only(['bank_name', 'account_number', 'account_name']),
        ]);
    }

    private function adminIndex(Request $request): Response
    {
        $status = in_array($request->query('status'), [...Withdrawal::STATUSES, 'all'], true)
            ? (string) $request->query('status')
            : Withdrawal::STATUS_PENDING;
        $search = $request->string('search')->trim()->toString();

        $withdrawals = Withdrawal::query()
            ->with(['user:id,name,slug,email,avatar_path', 'processor:id,name'])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            // Oldest waiting first, so nobody waits longest.
            ->when(
                $status === Withdrawal::STATUS_PENDING,
                fn (Builder $query) => $query->oldest('id'),
                fn (Builder $query) => $query->latest('id'),
            )
            ->paginate(15)
            ->withQueryString();

        $balances = [];

        $withdrawals->getCollection()->transform(function (Withdrawal $withdrawal) use (&$balances): array {
            $balances[$withdrawal->user_id] ??= InstructorBalance::for($withdrawal->user)->toArray();

            return [
                ...$this->row($withdrawal),
                'instructor' => [
                    ...$withdrawal->user->only(['id', 'name', 'slug', 'email', 'avatar']),
                    'balance' => $balances[$withdrawal->user_id],
                ],
            ];
        });

        $counts = Withdrawal::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('admin/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'counts' => collect(Withdrawal::STATUSES)->mapWithKeys(fn (string $key) => [$key => (int) ($counts[$key] ?? 0)]),
            'summary' => [
                'pending_amount' => (int) Withdrawal::query()->pending()->sum('amount'),
                'paid_this_month' => (int) Withdrawal::query()
                    ->where('status', Withdrawal::STATUS_PAID)
                    ->where('processed_at', '>=', now()->startOfMonth())
                    ->sum('amount'),
                'paid_all_time' => (int) Withdrawal::query()->where('status', Withdrawal::STATUS_PAID)->sum('amount'),
            ],
            'filters' => (object) ['status' => $status, 'search' => $search],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Withdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->id,
            'amount' => $withdrawal->amount,
            'bank_name' => $withdrawal->bank_name,
            'account_number' => $withdrawal->account_number,
            'account_name' => $withdrawal->account_name,
            'note' => $withdrawal->note,
            'status' => $withdrawal->status,
            'admin_note' => $withdrawal->admin_note,
            'has_proof' => $withdrawal->proof_path !== null,
            'processor' => $withdrawal->processor?->only(['id', 'name']),
            'processed_at' => $withdrawal->processed_at?->toIso8601String(),
            'created_at' => $withdrawal->created_at?->toIso8601String(),
        ];
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
