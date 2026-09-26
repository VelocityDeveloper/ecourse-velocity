<?php

namespace App\Models;

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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $role
 * @property string $email
 * @property string|null $avatar_path
 * @property string|null $headline
 * @property string|null $bio
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
}
