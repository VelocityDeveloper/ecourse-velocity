<?php

namespace App\Models;

use Database\Factories\CourseReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A student's star rating and optional written review of a course.
 *
 * @property int $id
 * @property int $course_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $comment
 * @property-read Course $course
 * @property-read User $user
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['course_id', 'user_id', 'rating', 'comment'])]
class CourseReview extends Model
{
    /** @use HasFactory<CourseReviewFactory> */
    use HasFactory;

    public const int MIN_RATING = 1;

    public const int MAX_RATING = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'user_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    /**
     * Get the reviewed course.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the student who wrote the review.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Summarise the star ratings of the given reviews: the average, the count and how many of each star.
     *
     * @param  Builder<CourseReview>|HasMany<CourseReview, Course>  $reviews
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    public static function summarize(Builder|HasMany $reviews): array
    {
        $counts = $reviews
            ->reorder()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = (int) $counts->sum();
        $distribution = [];

        foreach (range(self::MAX_RATING, self::MIN_RATING) as $stars) {
            $distribution[$stars] = (int) ($counts[$stars] ?? 0);
        }

        return [
            'average' => $count === 0
                ? null
                : round($counts->map(fn (mixed $total, mixed $stars): int => (int) $stars * (int) $total)->sum() / $count, 1),
            'count' => $count,
            'distribution' => $distribution,
        ];
    }
}
