<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells a student their payment proof was turned down, and why.
 */
class OrderRejected extends AppNotification
{
    public function __construct(public Order $order) {}

    protected function mailed(): bool
    {
        return true;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'payment',
            'tone' => 'warning',
            'title' => 'Bukti bayar ditolak',
            'body' => "{$this->order->course_title}: {$this->order->rejection_reason}",
            'url' => self::path('orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bukti bayar {$this->order->number} ditolak")
            ->greeting('Halo, '.$this->order->user->name.'!')
            ->line("Bukti pembayaran untuk kursus {$this->order->course_title} belum dapat kami terima.")
            ->line('Alasan: '.$this->order->rejection_reason)
            ->line('Silakan bayar atau unggah ulang bukti pembayaran.')
            ->action('Lihat Pesanan', route('orders.show', $this->order));
    }
}
