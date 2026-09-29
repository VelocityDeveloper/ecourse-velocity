<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tells the asker, the others in the thread and the instructor about a new reply.
 */
class DiscussionReplyPosted extends AppNotification
{
    public function __construct(public LessonReply $reply) {}

    public function toArray(object $notifiable): array
    {
        $question = $this->reply->question;
        $author = $this->reply->user;
        $name = $author->isAdmin() || $author->id === $question->lesson->section->course->instructor_id
            ? "Instruktur {$author->name}"
            : $author->name;

        $recipientId = $notifiable instanceof User ? $notifiable->id : null;

        $title = match (true) {
            $recipientId === $question->user_id => "{$name} membalas pertanyaan Anda",
            $recipientId === $question->lesson->section->course->instructor_id => "{$name} membalas diskusi di {$question->lesson->title}",
            default => "{$name} membalas diskusi yang Anda ikuti",
        };

        return [
            'kind' => 'discussion',
            'tone' => 'info',
            'title' => $title,
            'body' => Str::limit($this->reply->body, 120),
            'url' => self::questionPath($question),
        ];
    }

    /**
     * The lesson page, opened at the question.
     */
    public static function questionPath(LessonQuestion $question): string
    {
        $lesson = $question->lesson;

        return self::path('learn.lessons.show', [
            'course' => $lesson->section->course->slug,
            'lesson' => $lesson->slug,
            'pertanyaan' => $question->id,
        ]);
    }
}
