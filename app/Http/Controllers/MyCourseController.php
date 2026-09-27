<?php

namespace App\Http\Controllers;

use App\Actions\CalculateCourseProgress;
use App\Actions\CheckCertificateEligibility;
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
    public function index(Request $request, CalculateCourseProgress $calculateProgress, CheckCertificateEligibility $checkEligibility): Response
    {
        $actor = $request->user();

        assert($actor instanceof User);

        $certificates = $actor->certificates()->pluck('code', 'course_id');

        $enrollments = $actor->enrollments()
            ->active()
            ->with(['course.category:id,name', 'course.instructor:id,name,avatar_path'])
            ->latest('enrolled_at')
            ->get()
            ->map(function (Enrollment $enrollment) use ($actor, $calculateProgress, $checkEligibility, $certificates): array {
                $progress = $calculateProgress($actor, $enrollment->course);
                $certificateCode = $certificates[$enrollment->course_id] ?? null;
                $eligibility = $certificateCode === null && $progress['percent'] === 100
                    ? $checkEligibility($actor, $enrollment->course)
                    : null;

                return [
                    'id' => $enrollment->id,
                    'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                    'can_cancel' => Gate::allows('cancel', $enrollment),
                    'progress' => [
                        'completed' => $progress['completed'],
                        'total' => $progress['total'],
                        'percent' => $progress['percent'],
                    ],
                    'certificate' => [
                        'code' => $certificateCode,
                        'eligible' => $certificateCode !== null || ($eligibility['eligible'] ?? false),
                        'final_percent' => $eligibility['final_percent'] ?? null,
                        'passing_grade' => $eligibility['passing_grade'] ?? null,
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
