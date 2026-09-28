<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelEnrollmentRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    /**
     * List the enrollments in the courses the user manages.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Enrollment::class);

        $actor = $this->actor($request);
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $enrollments = Enrollment::query()
            ->manageableBy($actor)
            ->with(['user:id,name,email,avatar_path', 'course:id,slug,title,instructor_id', 'enrolledBy:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->whereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn (Builder $course) => $course->where('title', 'like', "%{$search}%"))
            ))
            ->when($request->filled('course_id'), fn (Builder $query) => $query->where('course_id', $request->integer('course_id')))
            ->when(in_array($status, Enrollment::STATUSES, true), fn (Builder $query) => $query->where('status', $status))
            ->latest('enrolled_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'cancelled_at' => $enrollment->cancelled_at?->toIso8601String(),
                'is_self_enrolled' => $enrollment->isSelfEnrolled(),
                'enrolled_by' => $enrollment->enrolledBy?->only(['id', 'name']),
                'student' => $enrollment->user->only(['id', 'name', 'email', 'avatar']),
                'course' => $enrollment->course->only(['id', 'slug', 'title']),
                'can_cancel' => Gate::allows('cancel', $enrollment),
            ]);

        return Inertia::render('enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only(['search', 'course_id', 'status']),
            'statuses' => Enrollment::STATUSES,
            'courses' => Course::query()
                ->manageableBy($actor)
                ->where('status', '!=', Course::STATUS_ARCHIVED)
                ->orderBy('title')
                ->get(['id', 'title']),
            'students' => Inertia::optional(fn (): array => User::query()
                ->where('role', User::ROLE_STUDENT)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $student): array => $student->only(['id', 'name', 'email']))
                ->all()),
        ]);
    }

    /**
     * Show the full detail of an enrollment.
     */
    public function show(Enrollment $enrollment): Response
    {
        Gate::authorize('view', $enrollment);

        $enrollment->load([
            'user:id,name,slug,email,role,avatar_path,headline,created_at',
            'course.instructor:id,name',
            'course.category:id,name',
            'enrolledBy:id,name,role',
            'cancelledBy:id,name,role',
        ]);

        return Inertia::render('enrollments/Show', [
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'cancelled_at' => $enrollment->cancelled_at?->toIso8601String(),
                'cancellation_reason' => $enrollment->cancellation_reason,
                'is_self_enrolled' => $enrollment->isSelfEnrolled(),
                'enrolled_by' => $enrollment->enrolledBy?->only(['id', 'name', 'role']),
                'cancelled_by' => $enrollment->cancelledBy?->only(['id', 'name', 'role']),
                'student' => [
                    ...$enrollment->user->only(['id', 'name', 'slug', 'email', 'avatar', 'headline']),
                    'joined_at' => $enrollment->user->created_at?->toIso8601String(),
                ],
                'course' => [
                    'id' => $enrollment->course->id,
                    'slug' => $enrollment->course->slug,
                    'title' => $enrollment->course->title,
                    'status' => $enrollment->course->status,
                    'level' => $enrollment->course->level,
                    'price' => $enrollment->course->price,
                    'thumbnail_url' => $enrollment->course->thumbnail_url,
                    'category' => $enrollment->course->category?->only(['id', 'name']),
                    'instructor' => $enrollment->course->instructor?->only(['id', 'name']),
                ],
            ],
            'can' => [
                'cancel' => Gate::allows('cancel', $enrollment),
            ],
        ]);
    }

    /**
     * Enroll a student in a course by hand.
     */
    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $student = $request->student();
        $course = $request->course();

        Enrollment::enroll($student, $course, $this->actor($request));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':student has been enrolled in :course.', ['student' => $student->name, 'course' => $course->title]),
        ]);

        return back();
    }

    /**
     * Cancel an active enrollment.
     */
    public function cancel(CancelEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $reason = $request->string('reason')->trim()->toString();

        $enrollment->cancel($this->actor($request), $reason === '' ? null : $reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Enrollment cancelled.')]);

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
