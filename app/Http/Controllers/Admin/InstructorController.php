<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The instructors as people: their courses, students and rating. Their money
 * lives in Keuangan (InstructorFinanceController).
 */
class InstructorController extends Controller
{
    /**
     * The orderings the list offers, keyed by their query value.
     */
    public const array SORTS = ['name', 'newest', 'courses', 'students'];

    public function index(Request $request): Response
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
        ]);

        $search = $request->string('search')->trim()->toString();
        $ownCourse = fn (Builder $query) => $query->whereColumn('courses.instructor_id', 'users.id');

        $query = User::query()
            ->where('role', User::ROLE_INSTRUCTOR)
            ->select('users.*')
            ->withCount([
                'courses',
                'courses as published_courses_count' => fn (Builder $query) => $query->where('status', Course::STATUS_PUBLISHED),
            ])
            ->addSelect([
                'students_count' => Enrollment::query()
                    ->selectRaw('count(*)')
                    ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                    ->where('enrollments.status', Enrollment::STATUS_ACTIVE)
                    ->tap($ownCourse),
                'rating' => CourseReview::query()
                    ->selectRaw('avg(course_reviews.rating)')
                    ->join('courses', 'courses.id', '=', 'course_reviews.course_id')
                    ->tap($ownCourse),
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('headline', 'like', "%{$search}%")));

        $query = match ($request->input('sort', 'name')) {
            'newest' => $query->latest(),
            'courses' => $query->orderByDesc('courses_count')->orderBy('name'),
            'students' => $query->orderByDesc('students_count')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        $instructors = $query->paginate(20)->withQueryString()->through(function (User $instructor): array {
            $rating = $instructor->getAttribute('rating');

            return [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'email' => $instructor->email,
                'slug' => $instructor->slug,
                'headline' => $instructor->headline,
                'avatar' => $instructor->avatar,
                'joined_at' => $instructor->created_at?->toIso8601String(),
                'suspended' => $instructor->isSuspended(),
                'courses_count' => (int) $instructor->getAttribute('courses_count'),
                'published_courses_count' => (int) $instructor->getAttribute('published_courses_count'),
                'students_count' => (int) $instructor->getAttribute('students_count'),
                'rating' => $rating === null ? null : round((float) $rating, 1),
            ];
        });

        return Inertia::render('admin/Instructors/Index', [
            'instructors' => $instructors,
            'filters' => (object) $request->only(['search', 'sort']),
        ]);
    }
}
