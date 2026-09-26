<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
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
            ? $user->courses()
                ->published()
                ->latest()
                ->get()
                ->map(fn (Course $course): array => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'level' => $course->level,
                    'thumbnail_url' => $course->thumbnail_url,
                ])
            : collect();

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
            'courses' => $courses,
        ]);
    }
}
