<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscussionPostRequest;
use App\Models\LessonQuestion;
use App\Models\LessonReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LessonReplyController extends Controller
{
    /**
     * Reply to a discussion question.
     */
    public function store(DiscussionPostRequest $request, LessonQuestion $question): RedirectResponse
    {
        Gate::authorize('reply', $question);

        $question->replies()->create([
            'user_id' => $request->user()?->id,
            'body' => $request->string('body')->trim()->toString(),
        ]);

        return back();
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
