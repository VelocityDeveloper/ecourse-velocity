<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseReviewRequest;
use App\Models\Course;
use App\Models\CourseReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseReviewController extends Controller
{
    /**
     * List the student reviews of one course, inside the course's own menu.
     */
    public function course(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        return Inertia::render('courses/Reviews', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title],
            ...$this->listing($request, CourseReview::query()->where('course_id', $course->id)),
            'filters' => $request->only(['search', 'rating']),
        ]);
    }

    /**
     * Save the student's rating and review, replacing an earlier one.
     */
    public function update(CourseReviewRequest $request, Course $course): RedirectResponse
    {
        $course->reviews()->updateOrCreate(
            ['user_id' => $request->user()?->id],
            [
                'rating' => $request->integer('rating'),
                'comment' => $request->filled('comment') ? $request->string('comment')->trim()->toString() : null,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks for your review!')]);

        return back();
    }

    /**
     * Remove a review.
     */
    public function destroy(CourseReview $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review removed.')]);

        return back();
    }

    /**
     * Page through the reviews in scope, filtered by stars and search, with their rating summary.
     *
     * @param  Builder<CourseReview>  $scope
     * @return array{reviews: mixed, summary: array{average: float|null, count: int, distribution: array<int, int>}}
     */
    private function listing(Request $request, Builder $scope): array
    {
        $search = $request->string('search')->toString();
        $rating = $request->integer('rating');

        $reviews = (clone $scope)
            ->with(['user:id,name,slug,avatar_path', 'course:id,slug,title'])
            ->when(
                $rating >= CourseReview::MIN_RATING && $rating <= CourseReview::MAX_RATING,
                fn (Builder $query) => $query->where('rating', $rating),
            )
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CourseReview $review): array => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'author' => $review->user->only(['id', 'name', 'slug', 'avatar']),
                'course' => $review->course->only(['id', 'slug', 'title']),
                'can_delete' => Gate::allows('delete', $review),
            ]);

        return [
            'reviews' => $reviews,
            'summary' => CourseReview::summarize($scope),
        ];
    }
}
