<?php

namespace App\Models;

use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An instructor's request to be paid out part of their earnings. The admin
 * transfers the money by hand, then marks it paid (or rejects it).
 *
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_name
 * @property string|null $note
 * @property string $status
 * @property string|null $admin_note
 * @property string|null $proof_path
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'amount', 'bank_name', 'account_number', 'account_name', 'note', 'status', 'admin_note', 'proof_path', 'processed_by', 'processed_at'])]
class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_PAID = 'paid';

    public const string STATUS_REJECTED = 'rejected';

    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    public const array STATUSES = [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_REJECTED, self::STATUS_CANCELLED];

    /**
     * Transfer receipts are private: they show bank details.
     */
    public const string PROOF_DISK = 'local';

    public const string PROOF_DIRECTORY = 'withdrawal-proofs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the instructor asking to be paid.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who paid or rejected the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Only requests still waiting for the admin.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
