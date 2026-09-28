<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;

/**
 * The quotes in the homepage "Cerita Sukses Alumni" section.
 *
 * By default they are the latest good course reviews; an admin can switch to
 * the hand-written testimonials in Pengaturan Situs → Testimoni instead.
 */
final class HomeTestimonials
{
    public const string SOURCE_REVIEWS = 'reviews';

    public const string SOURCE_MANUAL = 'manual';

    /**
     * @var list<string>
     */
    public const array SOURCES = [self::SOURCE_REVIEWS, self::SOURCE_MANUAL];

    /**
     * How many testimonials are shown unless the admin picks another number, and the range they may pick from.
     */
    public const int DEFAULT_LIMIT = 8;

    public const int MIN_LIMIT = 1;

    public const int MAX_LIMIT = 24;

    /**
     * The lowest rating a review needs to make it in.
     */
    public const int MIN_RATING = 4;

    /**
     * The source the admin picked.
     */
    public static function source(): string
    {
        $source = SiteSetting::get(SiteSetting::TESTIMONIAL_SOURCE);

        return in_array($source, self::SOURCES, true) ? $source : self::SOURCE_REVIEWS;
    }

    /**
     * How many testimonials the homepage shows, whichever the source.
     */
    public static function limit(): int
    {
        $limit = (int) SiteSetting::get(SiteSetting::TESTIMONIAL_LIMIT, (string) self::DEFAULT_LIMIT);

        return $limit >= self::MIN_LIMIT && $limit <= self::MAX_LIMIT ? $limit : self::DEFAULT_LIMIT;
    }

    /**
     * The testimonials to show on the homepage.
     *
     * Manual mode with no active testimonial still shows the reviews, so the section is never empty.
     *
     * @return list<array{id: string, name: string, subtitle: string|null, quote: string, rating: int, avatar: string|null}>
     */
    public static function forHomepage(): array
    {
        if (self::source() === self::SOURCE_MANUAL) {
            $manual = Testimonial::query()->active()->ordered()->limit(self::limit())->get()
                ->map(fn (Testimonial $item): array => [
                    'id' => 't'.$item->id,
                    'name' => $item->display_name,
                    'subtitle' => $item->subtitle,
                    'quote' => $item->quote,
                    'rating' => $item->rating,
                    'avatar' => $item->photo_url,
                ]);

            if ($manual->isNotEmpty()) {
                return array_values($manual->all());
            }
        }

        return array_map(fn (array $review): array => [
            'id' => 'r'.$review['id'],
            'name' => $review['name'],
            'subtitle' => $review['course'],
            'quote' => $review['quote'],
            'rating' => $review['rating'],
            'avatar' => $review['avatar'],
        ], self::reviews());
    }

    /**
     * The latest reviews with a comment and at least MIN_RATING stars, from published courses.
     *
     * @return list<array{id: int, name: string, course: string, quote: string, rating: int, avatar: string|null, created_at: string|null}>
     */
    public static function reviews(): array
    {
        return array_values(CourseReview::query()
            ->whereNotNull('comment')
            ->where('rating', '>=', self::MIN_RATING)
            ->whereHas('course', fn (Builder $course) => $course->where('status', Course::STATUS_PUBLISHED))
            ->with(['user:id,name,avatar_path', 'course:id,title'])
            ->latest()
            ->limit(self::limit())
            ->get()
            ->map(fn (CourseReview $review): array => [
                'id' => $review->id,
                'name' => $review->user->name,
                'course' => $review->course->title,
                'quote' => (string) $review->comment,
                'rating' => $review->rating,
                'avatar' => $review->user->avatar,
                'created_at' => $review->created_at?->toIso8601String(),
            ])
            ->all());
    }
}
