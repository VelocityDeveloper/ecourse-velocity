<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The bell: every user's in-app notifications, read one by one or all at once.
 */
class NotificationController extends Controller
{
    /**
     * How many notifications the bell's dropdown lists.
     */
    public const int RECENT = 8;

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $unreadOnly = $request->query('filter') === 'unread';

        $notifications = ($unreadOnly ? $user->unreadNotifications() : $user->notifications())
            ->paginate(20)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification): array => self::present($notification));

        return Inertia::render('notifications/Index', [
            'items' => $notifications,
            'filter' => $unreadOnly ? 'unread' : 'all',
        ]);
    }

    /**
     * Mark one notification read and open the page it points to.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $this->user($request)->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        // Only paths inside this app, never an outside address.
        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : to_route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * What the bell needs on every page: the unread count and the latest few.
     *
     * @return array{unread: int, recent: array<int, array<string, mixed>>}
     */
    public static function summary(User $user): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'recent' => $user->notifications()
                ->limit(self::RECENT)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => self::present($notification))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'kind' => $notification->data['kind'] ?? 'info',
            'tone' => $notification->data['tone'] ?? 'info',
            'title' => $notification->data['title'] ?? '',
            'body' => $notification->data['body'] ?? '',
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
