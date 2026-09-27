<?php

namespace App\Models;

use App\Concerns\HasPosition;
use Database\Factories\QuizQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quiz_id
 * @property string $question
 * @property string $answer_mode
 * @property int $points
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quiz_id', 'question', 'answer_mode', 'points', 'position'])]
class QuizQuestion extends Model
{
    /** @use HasFactory<QuizQuestionFactory> */
    use HasFactory, HasPosition;

    /**
     * Exactly one option is correct and the question is worth a flat score.
     */
    public const string MODE_SINGLE = 'single';

    /**
     * Several options are correct and the score rises with how many are picked.
     */
    public const string MODE_MULTIPLE = 'multiple';

    /**
     * The statement is either true or false and the question is worth a flat score.
     */
    public const string MODE_TRUE_FALSE = 'true_false';

    /**
     * The student types a short answer that is matched against the accepted
     * answers (stored as options, all correct) and the question is worth a flat score.
     */
    public const string MODE_SHORT_ANSWER = 'short_answer';

    /**
     * Every answer mode a question may use.
     *
     * @var list<string>
     */
    public const array ANSWER_MODES = [self::MODE_SINGLE, self::MODE_MULTIPLE, self::MODE_TRUE_FALSE, self::MODE_SHORT_ANSWER];

    /**
     * The longest short answer a student may type.
     */
    public const int MAX_SHORT_ANSWER_LENGTH = 500;

    /**
     * The fixed options of a true or false question, in the order they are shown.
     *
     * @var list<string>
     */
    public const array TRUE_FALSE_OPTIONS = ['True', 'False'];

    /**
     * The largest number of options a single question may offer.
     */
    public const int MAX_OPTIONS = 10;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'points' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Get the quiz this question belongs to.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the answer options, in the order they are shown.
     *
     * @return HasMany<QuizOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('position');
    }

    /**
     * Get the scoring tiers, from fewest correct answers to most.
     *
     * @return HasMany<QuizQuestionScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(QuizQuestionScore::class)->orderBy('correct_count');
    }

    /**
     * Determine whether this question accepts more than one correct option.
     */
    public function isMultipleAnswer(): bool
    {
        return $this->answer_mode === self::MODE_MULTIPLE;
    }

    /**
     * Determine whether the student types the answer instead of picking options.
     */
    public function isShortAnswer(): bool
    {
        return $this->answer_mode === self::MODE_SHORT_ANSWER;
    }

    /**
     * Reduce an answer to the form it is compared in: case, surrounding
     * punctuation and repeated whitespace do not matter.
     */
    public static function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text, " \t\n\r\0\x0B.,;:!?\"'`");
    }

    /**
     * Get the most points this question can award.
     *
     * A single answer question is worth its flat score; a multiple answer one
     * is worth the top tier, which is reached by picking every correct option.
     */
    public function maxPoints(): int
    {
        if (! $this->isMultipleAnswer()) {
            return $this->points;
        }

        return (int) $this->scores->max('points');
    }

    /**
     * Get the other questions of the same quiz.
     *
     * @return Builder<static>
     */
    public function siblings(): Builder
    {
        return static::query()->where('quiz_id', $this->quiz_id);
    }
}
