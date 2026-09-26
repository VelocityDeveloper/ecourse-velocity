<?php

namespace App\Http\Controllers;

use App\Actions\CalculateCourseProgress;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MyCourseController extends Controller
{
    /**
     * List the courses the current user is actively enrolled in, with their progress.
     */
    public function index(Request $request, CalculateCourseProgress $calculateProgress): Response
    {
        $actor = $request->user();

        assert($actor instanceof User);

        $enrollments = $actor->enrollments()
            ->active()
            ->with(['course.category:id,name', 'course.instructor:id,name,avatar_path'])
            ->latest('enrolled_at')
            ->get()
            ->map(function (Enrollment $enrollment) use ($actor, $calculateProgress): array {
                $progress = $calculateProgress($actor, $enrollment->course);

                return [
                    'id' => $enrollment->id,
                    'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                    'can_cancel' => Gate::allows('cancel', $enrollment),
                    'progress' => [
                        'completed' => $progress['completed'],
                        'total' => $progress['total'],
                        'percent' => $progress['percent'],
                    ],
                    'course' => [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                        'level' => $enrollment->course->level,
                        'thumbnail_url' => $enrollment->course->thumbnail_url,
                        'category' => $enrollment->course->category?->only(['id', 'name']),
                        'instructor' => $enrollment->course->instructor?->only(['id', 'name', 'avatar']),
                    ],
                ];
            });

        return Inertia::render('my-courses/Index', [
            'enrollments' => $enrollments,
        ]);
    }
}
