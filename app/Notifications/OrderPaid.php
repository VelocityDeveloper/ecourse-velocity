<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells a student their payment was confirmed and the course is open.
 */
class OrderPaid extends AppNotification
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
            'tone' => 'success',
            'title' => 'Pembayaran dikonfirmasi',
            'body' => "Anda sudah terdaftar di {$this->order->course_title}. Selamat belajar!",
            'url' => self::path('my-courses.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pembayaran {$this->order->number} dikonfirmasi")
            ->greeting('Halo, '.$this->order->user->name.'!')
            ->line('Pembayaran Anda sebesar '.self::rupiah($this->order->total)." untuk kursus {$this->order->course_title} sudah kami konfirmasi.")
            ->line('Anda sudah terdaftar dan bisa langsung mulai belajar.')
            ->action('Mulai Belajar', route('my-courses.index'));
    }
}
