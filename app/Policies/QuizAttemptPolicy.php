<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class QuizAttemptPolicy
{
    /**
     * Determine whether the user can see the attempt and its result.
     *
     * Only the student who made it may, and only while they can still open the course.
     */
    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id
            && Gate::forUser($user)->allows('learn', $attempt->quiz->section->course);
    }

    /**
     * Determine whether the user can hand the attempt in.
     */
    public function submit(User $user, QuizAttempt $attempt): bool
    {
        return ! $attempt->isSubmitted() && $this->view($user, $attempt);
    }
}
