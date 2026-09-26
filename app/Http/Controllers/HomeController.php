<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\CatalogCourseCard;
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
     * The number of student reviews shown as success stories, and the lowest rating shown.
     */
    private const int TESTIMONIALS = 8;

    private const int TESTIMONIAL_MIN_RATING = 4;

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
            'testimonials' => $this->testimonials(),
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
                ->select(['id', 'name', 'description', 'image_path'])
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
                    'image_url' => $category->image_url,
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

    /**
     * The alumni stories for the homepage: the admin's testimonials, or the latest
     * good course reviews while none have been added.
     *
     * @return list<array{id: string, name: string, subtitle: string|null, quote: string, rating: int, avatar: string|null}>
     */
    private function testimonials(): array
    {
        $testimonials = Testimonial::query()->active()->ordered()->get()
            ->map(fn (Testimonial $item): array => [
                'id' => 't'.$item->id,
                'name' => $item->display_name,
                'subtitle' => $item->subtitle,
                'quote' => $item->quote,
                'rating' => $item->rating,
                'avatar' => $item->photo_url,
            ]);

        if ($testimonials->isNotEmpty()) {
            return array_values($testimonials->all());
        }

        return array_values(CourseReview::query()
            ->whereNotNull('comment')
            ->where('rating', '>=', self::TESTIMONIAL_MIN_RATING)
            ->whereHas('course', fn (Builder $course) => $course->published())
            ->with(['user:id,name,avatar_path', 'course:id,title'])
            ->latest()
            ->limit(self::TESTIMONIALS)
            ->get()
            ->map(fn (CourseReview $review): array => [
                'id' => 'r'.$review->id,
                'name' => $review->user->name,
                'subtitle' => $review->course->title,
                'quote' => (string) $review->comment,
                'rating' => $review->rating,
                'avatar' => $review->user->avatar,
            ])
            ->all());
    }
}
