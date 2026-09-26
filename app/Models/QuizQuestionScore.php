<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * How many points a question awards for a given number of correct options.
 *
 * @property int $id
 * @property int $quiz_question_id
 * @property int $correct_count
 * @property int $points
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quiz_question_id', 'correct_count', 'points'])]
class QuizQuestionScore extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quiz_question_id' => 'integer',
            'correct_count' => 'integer',
            'points' => 'integer',
        ];
    }

    /**
     * Get the question this scoring tier belongs to.
     *
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
