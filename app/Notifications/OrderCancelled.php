<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Tells a student the admin cancelled their order.
 */
class OrderCancelled extends AppNotification
{
    public function __construct(public Order $order) {}

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order',
            'tone' => 'warning',
            'title' => 'Pesanan dibatalkan admin',
            'body' => "{$this->order->course_title}. Hubungi admin bila ada pertanyaan.",
            'url' => self::path('orders.show', $this->order),
        ];
    }
}
