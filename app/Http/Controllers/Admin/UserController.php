<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount([
                'courses',
                'orders as paid_orders_count' => fn (Builder $query) => $query->where('status', Order::STATUS_PAID),
            ])
            ->when($request->search, function (Builder $query, $search) {
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->role, function (Builder $query, $role) {
                $query->where('role', $role);
            })
            ->when($request->status, fn (Builder $query, $status) => match ($status) {
                'active' => $query->whereNull('suspended_at')->whereNull('purchase_blocked_at'),
                'suspended' => $query->whereNotNull('suspended_at'),
                'purchase_blocked' => $query->whereNotNull('purchase_blocked_at'),
                default => $query,
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'filters' => (object) $request->only(['search', 'role', 'status']),
            'restrictionCounts' => [
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
                'purchase_blocked' => User::query()->whereNotNull('purchase_blocked_at')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,instructor,student',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return to_route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,instructor,student',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return to_route('admin.users.index');
    }

    /**
     * Delete an account.
     *
     * Deleting a user also deletes their orders and payments, and an
     * instructor's courses with every enrollment in them, so accounts with
     * sales history or courses are suspended instead.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Cannot delete your own account.')]);

            return back();
        }

        if ($user->courses()->exists() || $user->orders()->where('status', Order::STATUS_PAID)->exists()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('This account has courses or paid orders, so it cannot be deleted. Suspend it instead.')]);

            return back();
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $user->name])]);

        return to_route('admin.users.index');
    }

    /**
     * Lock the account out: it cannot log in, and an open session ends on the next request.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Cannot suspend your own account.')]);

            return back();
        }

        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $request->input('reason'),
            'remember_token' => null,
        ])->save();

        // Log them out of every device right away rather than on their next click.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was suspended.', ['name' => $user->name])]);

        return back();
    }

    /**
     * Let a suspended account log in again.
     */
    public function unsuspend(User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name can log in again.', ['name' => $user->name])]);

        return back();
    }

    /**
     * Stop the user from buying courses. They keep the courses they have.
     */
    public function blockPurchases(Request $request, User $user): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $user->forceFill([
            'purchase_blocked_at' => now(),
            'purchase_block_reason' => $request->input('reason'),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name can no longer buy courses.', ['name' => $user->name])]);

        return back();
    }

    /**
     * Let the user buy courses again.
     */
    public function unblockPurchases(User $user): RedirectResponse
    {
        $user->forceFill(['purchase_blocked_at' => null, 'purchase_block_reason' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name can buy courses again.', ['name' => $user->name])]);

        return back();
    }
}
