<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A confirmed payment: money received for an order.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property int $amount
 * @property string $commission_rate Percentage kept by the platform, e.g. "10.00".
 * @property int $commission_amount
 * @property int $instructor_amount
 * @property string $payment_method
 * @property array<string, string>|null $payment_details
 * @property int|null $confirmed_by
 * @property string|null $note
 * @property Carbon $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_id', 'user_id', 'amount', 'commission_rate', 'commission_amount', 'instructor_amount', 'payment_method', 'payment_details', 'confirmed_by', 'note', 'paid_at'])]
class Transaction extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'user_id' => 'integer',
            'amount' => 'integer',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'integer',
            'instructor_amount' => 'integer',
            'payment_details' => 'array',
            'confirmed_by' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Split a payment into the platform's commission and the instructor's share.
     * The commission is rounded to whole rupiah; the instructor gets the rest.
     *
     * @return array{commission_rate: string, commission_amount: int, instructor_amount: int}
     */
    public static function split(int $amount, float $rate): array
    {
        $rate = max(0.0, min(100.0, $rate));
        $commission = (int) round($amount * $rate / 100);

        return [
            'commission_rate' => number_format($rate, 2, '.', ''),
            'commission_amount' => $commission,
            'instructor_amount' => $amount - $commission,
        ];
    }

    /**
     * The column that counts as revenue for the user: instructors earn their share,
     * admins see the full amount paid.
     */
    public static function revenueColumnFor(User $user): string
    {
        return $user->isAdmin() ? 'amount' : 'instructor_amount';
    }

    /**
     * Limit the query to the payments the user may see: all of them for admins,
     * only those for their own courses for instructors.
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->whereHas('order', fn (Builder $order) => $order->manageableBy($user));
        }
    }

    /**
     * Get the order this payment settles.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the student who paid.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who confirmed the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
