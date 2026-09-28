<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Support\CatalogCourseCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseWishlistController extends Controller
{
    /**
     * List the courses the student saved to their wishlist, most recent first.
     *
     * Courses that are no longer published are left out.
     */
    public function index(Request $request): Response
    {
        $courses = $this->actor($request)->wishlistedCourses()
            ->published()
            ->forCatalogCard($request->user()?->id)
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(fn (Course $course): array => [
                ...CatalogCourseCard::from($course),
                'wishlisted_at' => $course->pivot?->created_at?->toIso8601String(),
            ]);

        return Inertia::render('learning/Wishlist', [
            'courses' => $courses,
        ]);
    }

    /**
     * Save a course to the wishlist.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('wishlist', $course);

        $this->actor($request)->wishlistedCourses()->syncWithoutDetaching([$course->id]);

        return back();
    }

    /**
     * Remove a course from the wishlist.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->actor($request)->wishlistedCourses()->detach($course->id);

        return back();
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
