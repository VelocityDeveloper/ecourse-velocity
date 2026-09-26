<?php

namespace App\Policies;

use App\Models\CourseReview;
use App\Models\User;

class CourseReviewPolicy
{
    /**
     * Determine whether the user can remove the review.
     *
     * Students may withdraw their own review; admins may remove any review.
     */
    public function delete(User $user, CourseReview $review): bool
    {
        return $review->user_id === $user->id || $user->isAdmin();
    }
}
