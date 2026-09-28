<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\CatalogCourseCard;
use App\Support\HomeTestimonials;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The number of courses highlighted on the homepage.
     */
    private const int FEATURED_COURSES = 8;

    /**
     * How many instructors the homepage slider shows (four per view on large screens).
     */
    private const int INSTRUCTOR_LIMIT = 12;

    /**
     * Show the public homepage.
     */
    public function __invoke(Request $request): Response
    {
        $userId = $request->user()?->id;

        return Inertia::render('Welcome', [
            'banner' => SiteSetting::banner(),
            'banners' => Banner::query()->active()->ordered()->get(['id', 'title', 'image_path', 'link_url'])
                ->map(fn (Banner $banner): array => $banner->only(['id', 'title', 'image_url', 'link_url'])),
            'testimonials' => HomeTestimonials::forHomepage(),
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
                ->forCatalogCard($userId)
                ->orderByDesc('students_count')
                ->latest()
                ->limit(self::FEATURED_COURSES)
                ->get()
                ->map(fn (Course $course): array => CatalogCourseCard::from($course)),
            'categories' => Category::query()
                ->select(['id', 'name', 'slug', 'description', 'image_path'])
                ->withCount(['courses' => fn (Builder $query) => $query->published()])
                ->whereHas('courses', fn (Builder $query) => $query->published())
                ->orderByDesc('courses_count')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image_url' => $category->image_url,
                    'courses_count' => (int) $category->courses_count,
                ]),
            'instructors' => User::query()
                ->select(['id', 'name', 'slug', 'avatar_path', 'headline'])
                ->where('role', User::ROLE_INSTRUCTOR)
                ->withCount(['courses' => fn (Builder $query) => $query->published()])
                ->whereHas('courses', fn (Builder $query) => $query->published())
                ->orderByDesc('courses_count')
                ->limit(self::INSTRUCTOR_LIMIT)
                ->get()
                ->map(fn (User $instructor): array => [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'slug' => $instructor->slug,
                    'avatar' => $instructor->avatar,
                    'headline' => $instructor->headline,
                    'courses_count' => (int) $instructor->courses_count,
                ]),
        ]);
    }
}
