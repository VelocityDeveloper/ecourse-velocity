<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can list courses.
     *
     * Admins see every course, instructors only their own; students have no
     * access to course management at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can view the course detail.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $course->isOwnedBy($user));
    }

    /**
     * Determine whether the user can see the course in the student catalog.
     *
     * Published courses are open to everyone, guests included; a course taken
     * off the catalog stays visible to the students still enrolled in it and
     * to its managers.
     */
    public function viewInCatalog(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $this->update($user, $course)
            || $course->enrollments()->active()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can open the lessons and quizzes of the course.
     *
     * Students need an active enrollment; staff who manage the course may
     * preview it without enrolling.
     */
    public function learn(User $user, Course $course): bool
    {
        return $this->update($user, $course)
            || $course->enrollments()->active()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can rate and review the course.
     *
     * Only learners with an active enrollment may, so every review comes from a learner.
     */
    public function review(User $user, Course $course): bool
    {
        return $this->takes($user, $course)
            && $course->enrollments()->active()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can enroll themselves in the course.
     */
    public function enroll(?User $user, Course $course): bool
    {
        return $user !== null && $this->takes($user, $course) && $course->isPublished();
    }

    /**
     * Determine whether the user can save the course to their wishlist.
     *
     * Like enrolling, it is for learners and published courses only.
     */
    public function wishlist(User $user, Course $course): bool
    {
        return $this->takes($user, $course) && $course->isPublished();
    }

    /**
     * Determine whether the user can take the course as a learner.
     *
     * Instructors may learn too, from any course except their own.
     */
    private function takes(User $user, Course $course): bool
    {
        return $user->canLearn() && ! $course->isOwnedBy($user);
    }

    /**
     * Determine whether the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * Determine whether the user can edit the course.
     *
     * An instructor may never touch a course owned by another instructor.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isInstructor() && $course->isOwnedBy($user));
    }

    /**
     * Determine whether the user can change the course status.
     *
     * Which statuses may actually be selected is role dependent and enforced
     * by UpdateCourseStatusRequest.
     */
    public function updateStatus(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    /**
     * Determine whether the user can delete the course.
     *
     * Admins may delete any course. An instructor may only delete their own
     * course while it is still a draft.
     */
    public function delete(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor()
            && $course->isOwnedBy($user)
            && $course->status === Course::STATUS_DRAFT;
    }
}
