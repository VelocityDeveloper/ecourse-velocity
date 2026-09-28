<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonBookmarkController extends Controller
{
    /**
     * List the lessons the student has bookmarked, most recent first.
     *
     * Lessons from courses the student can no longer open are left out.
     */
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        $bookmarks = $actor->bookmarkedLessons()
            ->with('section:id,course_id,title', 'section.course:id,title,slug,instructor_id')
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->filter(fn (Lesson $lesson): bool => Gate::allows('learn', $lesson->section->course))
            ->map(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'content_type' => $lesson->content_type,
                'duration_minutes' => $lesson->duration_minutes,
                'bookmarked_at' => $lesson->pivot?->created_at?->toIso8601String(),
                'section' => ['id' => $lesson->section->id, 'title' => $lesson->section->title],
                'course' => $lesson->section->course->only(['id', 'slug', 'title']),
            ])
            ->values();

        return Inertia::render('learning/Bookmarks', [
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Bookmark a lesson.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->belongsToCourse($course), 404);

        $this->actor($request)->bookmarkedLessons()->syncWithoutDetaching([$lesson->id]);

        return back();
    }

    /**
     * Remove a lesson bookmark.
     */
    public function destroy(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->belongsToCourse($course), 404);

        $this->actor($request)->bookmarkedLessons()->detach($lesson->id);

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
