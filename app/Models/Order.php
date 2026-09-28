<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A student's purchase of a paid course, paid by manual bank transfer or QRIS
 * and confirmed by an admin.
 *
 * @property int $id
 * @property string $number
 * @property int $user_id
 * @property int|null $course_id
 * @property string $course_title
 * @property int $price
 * @property int $total
 * @property string $status
 * @property string|null $payment_method
 * @property array<string, string>|null $payment_details
 * @property string|null $proof_path
 * @property string|null $payer_name
 * @property string|null $payer_note
 * @property Carbon|null $proof_uploaded_at
 * @property string|null $rejection_reason
 * @property Carbon|null $expires_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $cancelled_at
 * @property int|null $handled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number', 'user_id', 'course_id', 'course_title', 'price', 'total', 'status',
    'payment_method', 'payment_details', 'proof_path', 'payer_name', 'payer_note', 'proof_uploaded_at',
    'rejection_reason', 'expires_at', 'paid_at', 'cancelled_at', 'handled_by',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Waiting for the student to pay and upload proof.
     */
    public const string STATUS_PENDING = 'pending';

    /**
     * Proof uploaded; waiting for an admin to check the payment.
     */
    public const string STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    /**
     * Payment confirmed; the student is enrolled.
     */
    public const string STATUS_PAID = 'paid';

    /**
     * Not paid before the deadline.
     */
    public const string STATUS_EXPIRED = 'expired';

    /**
     * Cancelled by the student or an admin.
     */
    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Every status an order can have.
     *
     * @var list<string>
     */
    public const array STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_AWAITING_CONFIRMATION,
        self::STATUS_PAID,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Statuses of an order that is still in progress.
     *
     * @var list<string>
     */
    public const array OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_AWAITING_CONFIRMATION];

    /**
     * Manual bank transfer to one of the admin's accounts.
     */
    public const string METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * Scanning the admin's static QRIS code.
     */
    public const string METHOD_QRIS = 'qris';

    /**
     * Every payment method.
     *
     * @var list<string>
     */
    public const array METHODS = [self::METHOD_BANK_TRANSFER, self::METHOD_QRIS];

    /**
     * The disk that keeps payment proofs away from the public web root.
     */
    public const string PROOF_DISK = 'local';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'course_id' => 'integer',
            'price' => 'integer',
            'total' => 'integer',
            'payment_details' => 'array',
            'proof_uploaded_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'handled_by' => 'integer',
        ];
    }

    /**
     * Look orders up by their number in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * Make a new, unused order number such as "INV-20260927-7K3P9".
     */
    public static function newNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $random = '';

            for ($i = 0; $i < 5; $i++) {
                $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $number = 'INV-'.now()->format('Ymd').'-'.$random;
        } while (self::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * Expire every unpaid order whose deadline has passed without a payment proof.
     */
    public static function expireOverdue(): int
    {
        return self::query()
            ->where('status', self::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Determine whether the order is still waiting for payment or confirmation.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /**
     * Determine whether the student may still upload a payment proof.
     */
    public function acceptsProof(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true)
            && ($this->status !== self::STATUS_PENDING || $this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Scope to orders that are still in progress.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', self::OPEN_STATUSES);
    }

    /**
     * Limit the query to the orders the user may see: every order for admins,
     * only the orders of their own courses for instructors.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->whereHas('course', fn (Builder $course) => $course->where('instructor_id', $user->id));
        }
    }

    /**
     * Get the student who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course being bought.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the admin who confirmed, rejected or cancelled the order last.
     *
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Get the payment recorded when the order was confirmed.
     *
     * @return HasOne<Transaction, $this>
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
