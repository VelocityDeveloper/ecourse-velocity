<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The number of courses highlighted on the homepage.
     */
    private const int FEATURED_COURSES = 6;

    /**
     * Show the public homepage.
     */
    public function __invoke(Request $request): Response
    {
        $userId = $request->user()?->id;

        return Inertia::render('Welcome', [
            'stats' => [
                'courses' => Course::query()->published()->count(),
                'lessons' => Lesson::query()
                    ->whereHas('section.course', fn (Builder $course) => $course->published())
                    ->count(),
                'students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
                'instructors' => User::query()->where('role', User::ROLE_INSTRUCTOR)->count(),
            ],
            'featuredCourses' => Course::query()
                ->published()
                ->with(['category:id,name', 'instructor:id,name,avatar_path'])
                ->withCount(['enrollments as students_count' => fn (Builder $query) => $query->active()])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->withExists(['enrollments as is_enrolled' => fn (Builder $query) => $query->active()->where('user_id', $userId)])
                ->orderByDesc('students_count')
                ->latest()
                ->limit(self::FEATURED_COURSES)
                ->get()
                ->map(fn (Course $course): array => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'level' => $course->level,
                    'price' => $course->price,
                    'thumbnail_url' => $course->thumbnail_url,
                    'category' => $course->category?->only(['id', 'name']),
                    'instructor' => $course->instructor?->only(['id', 'name', 'avatar']),
                    'students_count' => (int) $course->students_count,
                    'reviews_count' => (int) $course->reviews_count,
                    'rating_average' => $course->reviews_avg_rating === null ? null : round((float) $course->reviews_avg_rating, 1),
                    'is_enrolled' => (bool) $course->is_enrolled,
                ]),
            'categories' => Category::query()
                ->select(['id', 'name', 'description'])
                ->withCount(['courses' => fn (Builder $query) => $query->published()])
                ->whereHas('courses', fn (Builder $query) => $query->published())
                ->orderByDesc('courses_count')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'courses_count' => (int) $category->courses_count,
                ]),
            'instructors' => User::query()
                ->select(['id', 'name', 'avatar_path', 'headline'])
                ->where('role', User::ROLE_INSTRUCTOR)
                ->withCount(['courses' => fn (Builder $query) => $query->published()])
                ->whereHas('courses', fn (Builder $query) => $query->published())
                ->orderByDesc('courses_count')
                ->limit(4)
                ->get()
                ->map(fn (User $instructor): array => [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'avatar' => $instructor->avatar,
                    'headline' => $instructor->headline,
                    'courses_count' => (int) $instructor->courses_count,
                ]),
        ]);
    }
}
