<?php

namespace App\Models;

use Database\Factories\LessonNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A student's private note on a lesson; one per student and lesson.
 *
 * @property int $id
 * @property int $user_id
 * @property int $lesson_id
 * @property string $body
 * @property-read User $user
 * @property-read Lesson $lesson
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'lesson_id', 'body'])]
class LessonNote extends Model
{
    /** @use HasFactory<LessonNoteFactory> */
    use HasFactory;

    /**
     * The longest note a student may keep on one lesson.
     */
    public const int MAX_LENGTH = 10000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'lesson_id' => 'integer',
        ];
    }

    /**
     * Get the student who wrote the note.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson the note belongs to.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
