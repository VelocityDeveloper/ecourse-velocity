<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells an instructor their payout was transferred or turned down.
 */
class WithdrawalProcessed extends AppNotification
{
    public function __construct(public Withdrawal $withdrawal) {}

    protected function mailed(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $withdrawal = $this->withdrawal;
        $amount = 'Rp '.number_format($withdrawal->amount, 0, ',', '.');
        $paid = $withdrawal->status === Withdrawal::STATUS_PAID;

        $message = (new MailMessage)
            ->subject($paid ? "Penarikan dana {$amount} sudah ditransfer" : "Penarikan dana {$amount} ditolak")
            ->greeting('Halo, '.$withdrawal->user->name.'!')
            ->line($paid
                ? "Penarikan dana sebesar {$amount} sudah ditransfer ke {$withdrawal->bank_name} {$withdrawal->account_number} a.n. {$withdrawal->account_name}."
                : "Penarikan dana sebesar {$amount} belum dapat diproses. Jumlahnya sudah dikembalikan ke saldo Anda.");

        if ($withdrawal->admin_note !== null && $withdrawal->admin_note !== '') {
            $message->line('Catatan admin: '.$withdrawal->admin_note);
        }

        return $message->action('Lihat Penarikan Dana', route('admin.withdrawals.index'));
    }

    /**
     * @return array{kind: string, tone: string, title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $amount = self::rupiah($this->withdrawal->amount);
        $paid = $this->withdrawal->status === Withdrawal::STATUS_PAID;

        return [
            'kind' => 'withdrawal',
            'tone' => $paid ? 'success' : 'warning',
            'title' => $paid ? "Penarikan {$amount} sudah ditransfer" : "Penarikan {$amount} ditolak",
            'body' => $paid
                ? "Ke {$this->withdrawal->bank_name} {$this->withdrawal->account_number}."
                : ($this->withdrawal->admin_note ?: 'Jumlahnya sudah dikembalikan ke saldo Anda.'),
            'url' => self::path('admin.withdrawals.index'),
        ];
    }
}
