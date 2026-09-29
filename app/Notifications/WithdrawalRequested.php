<?php

namespace App\Notifications;

use App\Models\Withdrawal;

/**
 * Tells admins an instructor asked for a payout.
 */
class WithdrawalRequested extends AppNotification
{
    public function __construct(public Withdrawal $withdrawal) {}

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'withdrawal',
            'tone' => 'info',
            'title' => 'Permintaan penarikan '.self::rupiah($this->withdrawal->amount),
            'body' => "{$this->withdrawal->user->name} · {$this->withdrawal->bank_name} {$this->withdrawal->account_number}",
            'url' => self::path('admin.withdrawals.index'),
        ];
    }
}
