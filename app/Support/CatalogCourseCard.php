<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Support\Str;

/**
 * The shape of a course card on the public site (resources/js/components/CourseCard.vue).
 * Load the course with Course::scopeForCatalogCard() first.
 */
final class CatalogCourseCard
{
    /**
     * @return array{id: int, title: string, summary: string, level: string, price: string, thumbnail_url: string|null, category: array<string, mixed>|null, instructor: array<string, mixed>|null, lessons_count: int, students_count: int, reviews_count: int, rating_average: float|null, is_enrolled: bool}
     */
    public static function from(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'summary' => Str::limit(strip_tags((string) $course->description), 110),
            'level' => $course->level,
            'price' => $course->price,
            'thumbnail_url' => $course->thumbnail_url,
            'category' => $course->category?->only(['id', 'name']),
            'instructor' => $course->instructor?->only(['id', 'name', 'avatar']),
            'lessons_count' => (int) $course->getAttribute('lessons_count'),
            'students_count' => (int) $course->getAttribute('students_count'),
            'reviews_count' => (int) $course->getAttribute('reviews_count'),
            'rating_average' => $course->getAttribute('reviews_avg_rating') === null
                ? null
                : round((float) $course->getAttribute('reviews_avg_rating'), 1),
            'is_enrolled' => (bool) $course->getAttribute('is_enrolled'),
        ];
    }
}
