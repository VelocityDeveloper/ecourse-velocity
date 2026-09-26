<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can open the enrollment management list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can view the enrollment detail.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->manages($user, $enrollment);
    }

    /**
     * Determine whether the user can cancel the enrollment.
     *
     * Students may drop their own courses; staff may cancel any enrollment they manage.
     */
    public function cancel(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->isActive()) {
            return false;
        }

        return $enrollment->user_id === $user->id || $this->manages($user, $enrollment);
    }

    /**
     * Determine whether the user manages the course the enrollment belongs to.
     */
    private function manages(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin()
            || ($user->isInstructor() && $enrollment->course->isOwnedBy($user));
    }
}
