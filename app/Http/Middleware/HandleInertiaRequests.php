<?php

namespace App\Http\Middleware;

use App\Http\Controllers\NotificationController;
use App\Models\Category;
use App\Models\InstructorApplication;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * The most categories listed in the public header's "Kursus" submenu.
     */
    private const int NAV_CATEGORY_LIMIT = 8;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'branding' => [
                'logoUrl' => SiteSetting::logoUrl(),
                'paletteCss' => SiteSetting::paletteCss(),
            ],
            'site' => SiteSetting::publicSite(),
            // Categories with published courses, for the "Kursus" submenu of the public header.
            'navCategories' => fn () => Category::query()
                ->whereHas('courses', fn (Builder $query) => $query->published())
                ->orderBy('name')
                ->limit(self::NAV_CATEGORY_LIMIT)
                ->get(['id', 'name', 'slug']),
            'canRegister' => Features::enabled(Features::registration()),
            'auth' => [
                'user' => $request->user(),
            ],
            // Courses on the learner's wishlist, so every course card can show its heart filled in.
            'wishlistCourseIds' => fn (): array => $request->user()?->canLearn()
                ? $request->user()->wishlistedCourses()->pluck('courses.id')->all()
                : [],
            // Requests to become an instructor still waiting for an admin, for the sidebar badge.
            'pendingInstructorApplications' => fn (): int => $request->user()?->isAdmin()
                ? InstructorApplication::query()->pending()->count()
                : 0,
            // Payout requests waiting for the admin, for the sidebar badge.
            'pendingWithdrawals' => fn (): int => $request->user()?->isAdmin()
                ? Withdrawal::query()->pending()->count()
                : 0,
            // Payment proofs waiting for the admin to confirm, for the sidebar badge.
            'pendingOrders' => fn (): int => $request->user()?->isAdmin()
                ? Order::query()->where('status', Order::STATUS_AWAITING_CONFIRMATION)->count()
                : 0,
            // The bell: unread count and the latest notifications.
            'notifications' => fn (): ?array => $request->user() === null
                ? null
                : NotificationController::summary($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
