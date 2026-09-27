<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Support\CatalogCourseCard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    /**
     * Show the public profile of the given user.
     *
     * Instructor profiles are part of the public site; everyone else's profile
     * is only visible to signed-in users.
     */
    public function show(Request $request, User $user): Response
    {
        abort_if($request->user() === null && ! $user->isInstructor(), 404);

        $courses = $user->isInstructor()
            ? Course::query()
                ->published()
                ->where('instructor_id', $user->id)
                ->forCatalogCard($request->user()?->id)
                ->latest()
                ->get()
            : collect();

        $reviews = (int) $courses->sum('reviews_count');

        return Inertia::render('users/Show', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'headline' => $user->headline,
                'bio' => $user->bio,
                'joined_at' => $user->created_at?->toIso8601String(),
            ],
            'courses' => $courses->map(fn (Course $course): array => CatalogCourseCard::from($course))->values()->all(),
            'stats' => [
                'courses' => $courses->count(),
                'students' => (int) $courses->sum('students_count'),
                'reviews' => $reviews,
                'rating' => $reviews === 0 ? null : round(
                    $courses->sum(fn (Course $course): float => (float) $course->getAttribute('reviews_avg_rating') * (int) $course->getAttribute('reviews_count')) / $reviews,
                    1,
                ),
            ],
        ]);
    }
}
