<?php

namespace App\Policies;

use App\Models\LessonQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class LessonQuestionPolicy
{
    /**
     * Determine whether the user can remove the question and its replies.
     *
     * Authors may remove their own questions; the course's staff may moderate any.
     */
    public function delete(User $user, LessonQuestion $question): bool
    {
        return $question->user_id === $user->id
            || Gate::forUser($user)->allows('update', $question->lesson->section->course);
    }

    /**
     * Determine whether the user can reply to the question.
     */
    public function reply(User $user, LessonQuestion $question): bool
    {
        return Gate::forUser($user)->allows('learn', $question->lesson->section->course);
    }
}
