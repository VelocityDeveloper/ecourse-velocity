<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int|null $category_id
 * @property int $instructor_id
 * @property string $price
 * @property string $level
 * @property string|null $thumbnail_path
 * @property string $status
 * @property-read string|null $thumbnail_url
 * @property-read Category|null $category
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['thumbnail_url'])]
#[Fillable(['title', 'slug', 'description', 'category_id', 'instructor_id', 'price', 'level', 'thumbnail_path', 'status'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_PUBLISHED = 'published';

    public const string STATUS_ARCHIVED = 'archived';

    /**
     * The disk that course thumbnails are stored on.
     */
    public const string THUMBNAIL_DISK = 'public';

    /**
     * The directory that course thumbnails are stored in.
     */
    public const string THUMBNAIL_DIRECTORY = 'course-thumbnails';

    /**
     * Every status a course may hold.
     *
     * @var list<string>
     */
    public const array STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    /**
     * Statuses an instructor may move their own course to.
     *
     * @var list<string>
     */
    public const array INSTRUCTOR_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
    ];

    /**
     * Every difficulty level a course may hold.
     *
     * @var list<string>
     */
    public const array LEVELS = ['beginner', 'intermediate', 'advanced'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'category_id' => 'integer',
            'instructor_id' => 'integer',
        ];
    }

    /**
     * Get the instructor who owns this course.
     *
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the category this course belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the public URL of the course thumbnail, if one was uploaded.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnail_path === null
            ? null
            : Storage::disk(self::THUMBNAIL_DISK)->url($this->thumbnail_path),
        );
    }

    /**
     * Get the curriculum sections of this course, in order.
     *
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    /**
     * Determine whether the given user is the instructor of this course.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->instructor_id === $user->id;
    }

    /**
     * Get every enrollment in this course, active or cancelled.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the ratings and reviews students left on this course.
     *
     * @return HasMany<CourseReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    /**
     * Determine whether the course is open to students.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Limit the query to courses open to students.
     *
     * @param  Builder<Course>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Limit the query to the courses the given user is allowed to manage.
     *
     * Admins manage every course; instructors only ever see their own.
     *
     * @param  Builder<Course>  $query
     */
    public function scopeManageableBy(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->where('instructor_id', $user->id);
        }
    }

    /**
     * Cascade deletes through Eloquent so curriculum files are cleaned up.
     */
    protected static function booted(): void
    {
        static::deleting(function (Course $course): void {
            $course->sections->each(fn (Section $section) => $section->delete());
        });
    }
}
