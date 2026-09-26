<?php

namespace App\Models;

use App\Concerns\HasPosition;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property string $content_type
 * @property string|null $content
 * @property string|null $content_url
 * @property int|null $duration_minutes
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['section_id', 'title', 'content_type', 'content', 'content_url', 'duration_minutes', 'position'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, HasPosition;

    public const string TYPE_VIDEO = 'video';

    public const string TYPE_ARTICLE = 'article';

    /**
     * Every kind of content a lesson may hold.
     *
     * @var list<string>
     */
    public const array CONTENT_TYPES = [self::TYPE_VIDEO, self::TYPE_ARTICLE];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_id' => 'integer',
            'position' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Determine whether this lesson is part of the given course.
     */
    public function belongsToCourse(Course $course): bool
    {
        return $this->section()->where('course_id', $course->id)->exists();
    }

    /**
     * Get the section this lesson belongs to.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the files attached to this lesson.
     *
     * @return HasMany<LessonAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('id');
    }

    /**
     * Get the discussion questions asked in this lesson, newest first.
     *
     * @return HasMany<LessonQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuestion::class)->latest();
    }

    /**
     * Get the other lessons of the same section.
     *
     * @return Builder<static>
     */
    public function siblings(): Builder
    {
        return static::query()->where('section_id', $this->section_id);
    }

    /**
     * Cascade deletes through Eloquent so attachment files are cleaned up.
     *
     * The database cascade would remove the rows, but not the stored files.
     */
    protected static function booted(): void
    {
        static::deleting(function (Lesson $lesson): void {
            $lesson->attachments->each(
                fn (LessonAttachment $attachment) => $attachment->delete(),
            );
        });
    }
}
