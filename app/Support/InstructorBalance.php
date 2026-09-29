<?php

namespace App\Support;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;

/**
 * What an instructor has earned (their share of every confirmed sale after
 * the platform commission) and how much of it they can still withdraw.
 */
final readonly class InstructorBalance
{
    public function __construct(
        public int $earned,
        public int $paidOut,
        public int $pending,
    ) {}

    public static function for(User $instructor): self
    {
        $withdrawals = $instructor->withdrawals()
            ->whereIn('status', [Withdrawal::STATUS_PAID, Withdrawal::STATUS_PENDING])
            ->selectRaw('status, sum(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return new self(
            earned: (int) Transaction::query()
                ->whereHas('order.course', fn ($course) => $course->where('instructor_id', $instructor->id))
                ->sum('instructor_amount'),
            paidOut: (int) ($withdrawals[Withdrawal::STATUS_PAID] ?? 0),
            pending: (int) ($withdrawals[Withdrawal::STATUS_PENDING] ?? 0),
        );
    }

    /**
     * What may still be requested. Never negative, even if a later commission
     * change lowered earnings below what was already paid out.
     */
    public function available(): int
    {
        return max(0, $this->earned - $this->paidOut - $this->pending);
    }

    /**
     * @return array{earned: int, paid_out: int, pending: int, available: int}
     */
    public function toArray(): array
    {
        return [
            'earned' => $this->earned,
            'paid_out' => $this->paidOut,
            'pending' => $this->pending,
            'available' => $this->available(),
        ];
    }
}
