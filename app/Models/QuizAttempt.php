<?php

namespace App\Models;

use App\Actions\GradeQuizAttempt;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $submitted_at
 * @property array<int, list<int>|string>|null $answers
 * @property int|null $score
 * @property int $max_score
 * @property bool $is_late
 * @property-read User $user
 * @property-read Quiz $quiz
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'quiz_id', 'started_at', 'expires_at', 'submitted_at', 'answers', 'score', 'max_score', 'is_late'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /**
     * Seconds of network slack allowed after the timer runs out before answers are refused.
     */
    public const int GRACE_SECONDS = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'quiz_id' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'answers' => 'array',
            'score' => 'integer',
            'max_score' => 'integer',
            'is_late' => 'boolean',
        ];
    }

    /**
     * Start a new attempt for the student, timed by the quiz's limit.
     */
    public static function start(User $student, Quiz $quiz): self
    {
        $startedAt = now();

        return self::query()->create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'started_at' => $startedAt,
            'expires_at' => $quiz->time_limit_minutes === null
                ? null
                : $startedAt->copy()->addMinutes($quiz->time_limit_minutes),
        ]);
    }

    /**
     * Grade and close the attempt.
     *
     * Answers that arrive after the deadline (plus grace) are discarded, so a
     * late attempt is closed with no answers and scores zero.
     *
     * @param  array<int|string, mixed>  $answers  Selected option ids (or the typed text) keyed by question id.
     */
    public function handIn(array $answers, GradeQuizAttempt $grade): void
    {
        $isLate = $this->isPastDeadline();
        $quiz = $this->quiz->loadMissing(['questions.options', 'questions.scores']);
        $result = $grade($quiz, $isLate ? [] : $answers);

        $this->update([
            'submitted_at' => now(),
            'answers' => $result['answers'],
            'score' => $result['score'],
            'max_score' => $result['max_score'],
            'is_late' => $isLate,
        ]);
    }

    /**
     * Determine whether the attempt has been handed in.
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Determine whether answers arriving now come too late to count.
     */
    public function isPastDeadline(): bool
    {
        return $this->expires_at !== null
            && now()->greaterThan($this->expires_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    /**
     * Limit the query to attempts that are still being worked on.
     *
     * @param  Builder<QuizAttempt>  $query
     */
    public function scopeInProgress(Builder $query): void
    {
        $query->whereNull('submitted_at');
    }

    /**
     * Get the student who made the attempt.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quiz that was attempted.
     *
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
