<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Support\Str;

/**
 * Tells a course's instructor a learner asked something in a lesson.
 */
class DiscussionQuestionPosted extends AppNotification
{
    public function __construct(public LessonQuestion $question) {}

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'discussion',
            'tone' => 'info',
            'title' => "{$this->question->user->name} bertanya di {$this->question->lesson->title}",
            'body' => Str::limit($this->question->body, 120),
            'url' => DiscussionReplyPosted::questionPath($this->question),
        ];
    }
}
