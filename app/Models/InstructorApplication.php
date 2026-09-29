<?php

namespace App\Models;

use Database\Factories\InstructorApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A request from a student to teach on the platform. The student keeps their
 * role until an admin approves it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $headline
 * @property string $expertise
 * @property string $experience
 * @property string $motivation
 * @property string|null $portfolio_url
 * @property string|null $phone
 * @property string $status
 * @property string|null $admin_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'headline', 'expertise', 'experience', 'motivation', 'portfolio_url', 'phone', 'status', 'admin_note', 'reviewed_by', 'reviewed_at'])]
class InstructorApplication extends Model
{
    /** @use HasFactory<InstructorApplicationFactory> */
    use HasFactory;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    public const array STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the applicant.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved or rejected the application.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Only applications still waiting for an admin.
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
