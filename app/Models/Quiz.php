<?php

namespace App\Models;

use App\Concerns\HasPosition;
use Database\Factories\QuizFactory;
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
 * @property string|null $description
 * @property int|null $time_limit_minutes
 * @property int|null $passing_score Minimum percentage to pass, or null when the quiz has none.
 * @property int $weight How much the quiz counts towards the final course grade.
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['section_id', 'title', 'description', 'time_limit_minutes', 'passing_score', 'weight', 'position'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, HasPosition;

    /**
     * The longest time limit, in minutes, a quiz may be given.
     */
    public const int MAX_TIME_LIMIT_MINUTES = 600;

    /**
     * The largest weight a quiz may carry in the final course grade.
     */
    public const int MAX_WEIGHT = 100;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_id' => 'integer',
            'time_limit_minutes' => 'integer',
            'passing_score' => 'integer',
            'weight' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Determine whether a score percentage meets the passing score.
     *
     * Returns null when the quiz has no passing score.
     */
    public function isPassingPercent(int $percent): ?bool
    {
        return $this->passing_score === null ? null : $percent >= $this->passing_score;
    }

    /**
     * Determine whether this quiz is part of the given course.
     */
    public function belongsToCourse(Course $course): bool
    {
        return $this->section()->where('course_id', $course->id)->exists();
    }

    /**
     * Get the section this quiz belongs to.
     *
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the questions in this quiz, in order.
     *
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    /**
     * Get every attempt students have made at this quiz.
     *
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the other quizzes of the same section.
     *
     * @return Builder<static>
     */
    public function siblings(): Builder
    {
        return static::query()->where('section_id', $this->section_id);
    }
}
