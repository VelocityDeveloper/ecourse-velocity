<?php

namespace App\Notifications;

use App\Models\Order;

/**
 * Tells admins a student sent a payment proof to check.
 */
class PaymentProofSubmitted extends AppNotification
{
    public function __construct(public Order $order) {}

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'payment',
            'tone' => 'info',
            'title' => 'Bukti bayar baru perlu dicek',
            'body' => "{$this->order->user->name} · {$this->order->course_title} · ".self::rupiah($this->order->total),
            'url' => self::path('admin.orders.show', $this->order),
        ];
    }
}
