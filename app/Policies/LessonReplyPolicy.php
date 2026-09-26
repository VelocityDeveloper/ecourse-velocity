<?php

namespace App\Policies;

use App\Models\LessonReply;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class LessonReplyPolicy
{
    /**
     * Determine whether the user can remove the reply.
     *
     * Authors may remove their own replies; the course's staff may moderate any.
     */
    public function delete(User $user, LessonReply $reply): bool
    {
        return $reply->user_id === $user->id
            || Gate::forUser($user)->allows('update', $reply->question->lesson->section->course);
    }
}
