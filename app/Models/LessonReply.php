<?php

namespace App\Models;

use Database\Factories\LessonReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A reply in a lesson discussion thread.
 *
 * @property int $id
 * @property int $lesson_question_id
 * @property int $user_id
 * @property string $body
 * @property-read User $user
 * @property-read LessonQuestion $question
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['lesson_question_id', 'user_id', 'body'])]
class LessonReply extends Model
{
    /** @use HasFactory<LessonReplyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_question_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Get the person who wrote the reply.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question being answered.
     *
     * @return BelongsTo<LessonQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(LessonQuestion::class, 'lesson_question_id');
    }
}
