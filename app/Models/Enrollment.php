<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $status
 * @property int|null $enrolled_by
 * @property Carbon $enrolled_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property int|null $last_lesson_id
 * @property Carbon|null $last_accessed_at
 * @property-read Lesson|null $lastLesson
 * @property-read User $user
 * @property-read Course $course
 * @property-read User|null $enrolledBy
 * @property-read User|null $cancelledBy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'course_id', 'status', 'enrolled_by', 'enrolled_at', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'last_lesson_id', 'last_accessed_at'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Every status an enrollment may hold.
     *
     * @var list<string>
     */
    public const array STATUSES = [self::STATUS_ACTIVE, self::STATUS_CANCELLED];

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
            'enrolled_by' => 'integer',
            'cancelled_by' => 'integer',
            'enrolled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_lesson_id' => 'integer',
            'last_accessed_at' => 'datetime',
        ];
    }

    /**
     * Enroll the student in the course, reactivating a cancelled enrollment if one exists.
     *
     * Passing no enroller marks the enrollment as made by the student themselves.
     */
    public static function enroll(User $student, Course $course, ?User $enroller = null): self
    {
        $enrollment = self::query()->firstOrNew([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $enrollment->fill([
            'status' => self::STATUS_ACTIVE,
            'enrolled_by' => $enroller?->id,
            'enrolled_at' => now(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
        ])->save();

        // A course the student now has no longer belongs on their wishlist.
        $student->wishlistedCourses()->detach($course->id);

        return $enrollment;
    }

    /**
     * Remember that the student was just learning, and which lesson they opened.
     *
     * Opening a quiz only refreshes the activity time, so resuming still lands
     * on the last lesson the student read.
     */
    public function recordActivity(?Lesson $lesson = null): void
    {
        $this->forceFill(array_filter([
            'last_accessed_at' => now(),
            'last_lesson_id' => $lesson?->id,
        ], fn (mixed $value): bool => $value !== null))->save();
    }

    /**
     * Cancel the enrollment on behalf of the given user.
     */
    public function cancel(User $canceller, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $canceller->id,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Determine whether the enrollment currently grants access to the course.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Determine whether the student enrolled on their own rather than being added by staff.
     */
    public function isSelfEnrolled(): bool
    {
        return $this->enrolled_by === null || $this->enrolled_by === $this->user_id;
    }

    /**
     * Limit the query to enrollments that are still active.
     *
     * @param  Builder<Enrollment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Limit the query to the enrollments the given staff member may manage.
     *
     * Admins manage every enrollment; instructors only those in their own courses.
     *
     * @param  Builder<Enrollment>  $query
     */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->whereHas('course', fn (Builder $course) => $course->where('instructor_id', $user->id));
        }
    }

    /**
     * Get the enrolled student.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course the student is enrolled in.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the staff member who enrolled the student, if it was not the student.
     *
     * @return BelongsTo<User, $this>
     */
    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Get the lesson the student opened most recently.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }

    /**
     * Get the user who cancelled the enrollment.
     *
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
