<?php

namespace App\Models;

use App\Support\Slug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug Used in the public profile URL, /instruktur/{slug}.
 * @property string $role
 * @property string $email
 * @property string|null $avatar_path
 * @property string|null $headline
 * @property string|null $bio
 * @property Carbon|null $suspended_at Set while an admin has locked the account out.
 * @property string|null $suspension_reason
 * @property Carbon|null $purchase_blocked_at Set while the user may not buy courses.
 * @property string|null $purchase_block_reason
 * @property-read string|null $avatar
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['avatar'])]
#[Fillable(['name', 'email', 'password', 'role', 'avatar_path', 'headline', 'bio'])]
#[Hidden(['avatar_path', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const string ROLE_ADMIN = 'admin';

    public const string ROLE_INSTRUCTOR = 'instructor';

    public const string ROLE_STUDENT = 'student';

    /**
     * Every role a user may hold.
     *
     * @var list<string>
     */
    public const array ROLES = [self::ROLE_ADMIN, self::ROLE_INSTRUCTOR, self::ROLE_STUDENT];

    /**
     * The disk that user avatars are stored on.
     */
    public const string AVATAR_DISK = 'public';

    /**
     * The directory that user avatars are stored in.
     */
    public const string AVATAR_DIRECTORY = 'avatars';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'purchase_blocked_at' => 'datetime',
        ];
    }

    /**
     * Get the public URL of the user's avatar.
     *
     * Reads the raw attribute so partially selected users (e.g. `instructor:id,name`) still serialize.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            $avatarPath = $this->attributes['avatar_path'] ?? null;

            return $avatarPath === null ? null : Storage::disk(self::AVATAR_DISK)->url($avatarPath);
        });
    }

    /**
     * Determine whether the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Determine whether the user is an instructor.
     */
    public function isInstructor(): bool
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    /**
     * Determine whether the user can take courses: students, and instructors
     * learning from other instructors' courses.
     */
    public function canLearn(): bool
    {
        return $this->isStudent() || $this->isInstructor();
    }

    /**
     * Send a notification to every admin who can still sign in.
     */
    public static function notifyAdmins(BaseNotification $notification): void
    {
        Notification::send(
            self::query()->where('role', self::ROLE_ADMIN)->whereNull('suspended_at')->get(),
            $notification,
        );
    }

    /**
     * Determine whether an admin has locked the account out.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Determine whether an admin has stopped the user from buying courses.
     */
    public function isPurchaseBlocked(): bool
    {
        return $this->purchase_blocked_at !== null;
    }

    /**
     * Determine whether the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Get every enrollment of this student, active or cancelled.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the course orders this student has placed.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the certificates issued to this student.
     *
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the lessons this student has marked as complete.
     *
     * @return BelongsToMany<Lesson, $this>
     */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_completions')->withTimestamps();
    }

    /**
     * Get the lessons this student has bookmarked.
     *
     * @return BelongsToMany<Lesson, $this>
     */
    public function bookmarkedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_bookmarks')->withTimestamps();
    }

    /**
     * Get the courses this student saved to their wishlist to take later.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function wishlistedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_wishlists')->withTimestamps();
    }

    /**
     * Get the private notes this student has written on lessons.
     *
     * @return HasMany<LessonNote, $this>
     */
    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    /**
     * Get every quiz attempt this student has made.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the courses this user teaches.
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /**
     * Get every request this user sent to become an instructor.
     *
     * @return HasMany<InstructorApplication, $this>
     */
    public function instructorApplications(): HasMany
    {
        return $this->hasMany(InstructorApplication::class);
    }

    /**
     * Get every payout this instructor asked for.
     *
     * @return HasMany<Withdrawal, $this>
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Give every new user a profile slug made from their name.
     *
     * It is kept when the name changes, so shared profile links keep working.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (($user->slug ?? '') === '') {
                $user->slug = Slug::unique(
                    $user->name,
                    'pengguna',
                    fn (string $slug): bool => static::query()->where('slug', $slug)->exists(),
                );
            }
        });
    }
}
