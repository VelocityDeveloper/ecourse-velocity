<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscussionPostRequest;
use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\User;
use App\Notifications\DiscussionReplyPosted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class LessonReplyController extends Controller
{
    /**
     * Reply to a discussion question.
     */
    public function store(DiscussionPostRequest $request, LessonQuestion $question): RedirectResponse
    {
        Gate::authorize('reply', $question);

        $reply = $question->replies()->create([
            'user_id' => $request->user()?->id,
            'body' => $request->string('body')->trim()->toString(),
        ]);

        if (config('app.discussion_notifications')) {
            Notification::send($this->recipients($reply), new DiscussionReplyPosted($reply));
        }

        return back();
    }

    /**
     * Everyone following the thread: the asker, earlier repliers and the
     * course's instructor, but never the one who just replied.
     *
     * @return Collection<int, User>
     */
    private function recipients(LessonReply $reply): Collection
    {
        $question = $reply->question;

        $ids = $question->replies()->pluck('user_id')
            ->push($question->user_id, $question->lesson->section->course->instructor_id)
            ->filter()
            ->unique()
            ->reject(fn (int $id): bool => $id === $reply->user_id);

        return User::query()->whereKey($ids->all())->whereNull('suspended_at')->get();
    }

    /**
     * Remove a reply.
     */
    public function destroy(LessonReply $reply): RedirectResponse
    {
        Gate::authorize('delete', $reply);

        $reply->delete();

        return back();
    }
}
