<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscussionPostRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Notifications\DiscussionQuestionPosted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LessonQuestionController extends Controller
{
    /**
     * Ask a question in the lesson's discussion.
     */
    public function store(DiscussionPostRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->belongsToCourse($course), 404);

        $question = $lesson->questions()->create([
            'user_id' => $request->user()?->id,
            'body' => $request->string('body')->trim()->toString(),
        ]);

        // The instructor answers questions, unless they asked it themselves.
        $instructor = $course->instructor;

        if (config('app.discussion_notifications') && $instructor !== null && $instructor->id !== $question->user_id) {
            $instructor->notify(new DiscussionQuestionPosted($question));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Question posted.')]);

        return back();
    }

    /**
     * Remove a question together with its replies.
     */
    public function destroy(LessonQuestion $question): RedirectResponse
    {
        Gate::authorize('delete', $question);

        $question->delete();

        return back();
    }
}
