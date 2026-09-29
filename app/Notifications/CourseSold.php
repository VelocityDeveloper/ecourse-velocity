<?php

namespace App\Notifications;

use App\Models\Transaction;

/**
 * Tells an instructor one of their courses sold, with their share.
 */
class CourseSold extends AppNotification
{
    public function __construct(public Transaction $transaction) {}

    public function toArray(object $notifiable): array
    {
        $order = $this->transaction->order;

        return [
            'kind' => 'sale',
            'tone' => 'success',
            'title' => "Kursus terjual: {$order->course_title}",
            'body' => 'Pendapatan Anda '.self::rupiah($this->transaction->instructor_amount)." dari {$order->user->name}.",
            'url' => self::path('admin.orders.show', $order),
        ];
    }
}
