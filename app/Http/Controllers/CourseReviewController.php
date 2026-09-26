<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseReviewRequest;
use App\Models\Course;
use App\Models\CourseReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseReviewController extends Controller
{
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
}
