<?php

namespace App\Models;

use App\Concerns\HasPosition;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string|null $description
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['course_id', 'title', 'description', 'position'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, HasPosition;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Get the course this section belongs to.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons in this section, in curriculum order.
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * Get the quizzes in this section, in order.
     *
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('position');
    }

    /**
     * Get the other sections of the same course.
     *
     * @return Builder<static>
     */
    public function siblings(): Builder
    {
        return static::query()->where('course_id', $this->course_id);
    }

    /**
     * Cascade deletes through Eloquent so lesson files are cleaned up.
     */
    protected static function booted(): void
    {
        static::deleting(function (Section $section): void {
            $section->lessons->each(fn (Lesson $lesson) => $lesson->delete());
        });
    }
}
