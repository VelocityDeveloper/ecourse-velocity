<?php

namespace App\Models;

use Database\Factories\LessonQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A question asked in a lesson's discussion.
 *
 * @property int $id
 * @property int $lesson_id
 * @property int $user_id
 * @property string $body
 * @property-read User $user
 * @property-read Lesson $lesson
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['lesson_id', 'user_id', 'body'])]
class LessonQuestion extends Model
{
    /** @use HasFactory<LessonQuestionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Get the person who asked the question.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson the question was asked in.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the replies to the question, oldest first.
     *
     * @return HasMany<LessonReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(LessonReply::class)->oldest();
    }
}
