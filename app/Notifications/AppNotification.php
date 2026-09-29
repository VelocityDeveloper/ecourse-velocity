<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * A notification that shows under the bell in the app, and is emailed too when
 * `mailed()` says so. The bell entry is stored right away; only the email waits
 * for the queue.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * What the bell shows: an icon kind, a tone, a title, one line of detail
     * and the page the notification opens.
     *
     * @return array{kind: string, tone: string, title: string, body: string, url: string}
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * Whether the notification also goes out by email.
     */
    protected function mailed(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->mailed() ? ['database', 'mail'] : ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * A path inside the app, so stored links survive a change of domain.
     *
     * @param  array<array-key, mixed>|object|string  $parameters
     */
    protected static function path(string $route, mixed $parameters = []): string
    {
        return route($route, $parameters, false);
    }

    protected static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
