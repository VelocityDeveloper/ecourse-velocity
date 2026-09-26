<?php

namespace App\Http\Controllers;

use App\Actions\BuildLearningOutline;
use App\Actions\CalculateCourseProgress;
use App\Actions\ResolveVideoEmbed;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\LessonQuestion;
use App\Models\LessonReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearnController extends Controller
{
    /**
     * The most discussion threads loaded with a lesson.
     */
    private const int DISCUSSION_LIMIT = 50;

    /**
     * Open the course where the student left off.
     *
     * The last lesson they opened wins while it is unfinished; otherwise they
     * continue at the first lesson or quiz they have not finished yet.
     */
    public function show(Request $request, Course $course, CalculateCourseProgress $calculateProgress): RedirectResponse
    {
        if (Gate::denies('learn', $course)) {
            return $this->notEnrolled($course);
        }

        $actor = $this->actor($request);
        $progress = $calculateProgress($actor, $course);
        $lastLesson = $this->enrollmentFor($actor, $course)?->lastLesson;

        if ($lastLesson instanceof Lesson
            && $lastLesson->belongsToCourse($course)
            && ! in_array($lastLesson->id, $progress['completed_lesson_ids'], true)) {
            return to_route('learn.lessons.show', [$course, $lastLesson]);
        }

        $course->load(['sections.lessons:id,section_id,position', 'sections.quizzes:id,section_id,position']);

        foreach ($course->sections as $section) {
            foreach ($section->lessons as $lesson) {
                if (! in_array($lesson->id, $progress['completed_lesson_ids'], true)) {
                    return to_route('learn.lessons.show', [$course, $lesson]);
                }
            }

            foreach ($section->quizzes as $quiz) {
                if (! in_array($quiz->id, $progress['attempted_quiz_ids'], true)) {
                    return to_route('learn.quizzes.show', [$course, $quiz]);
                }
            }
        }

        $firstLesson = $course->sections->flatMap->lessons->first();

        if ($firstLesson instanceof Lesson) {
            return to_route('learn.lessons.show', [$course, $firstLesson]);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('This course has no lessons yet.')]);

        return to_route('catalog.show', $course);
    }

    /**
     * Show a lesson inside the learning space, with the student's note and the discussion.
     */
    public function lesson(Request $request, Course $course, Lesson $lesson, BuildLearningOutline $buildOutline, ResolveVideoEmbed $resolveVideo): Response|RedirectResponse
    {
        if (Gate::denies('learn', $course)) {
            return $this->notEnrolled($course);
        }

        abort_unless($lesson->belongsToCourse($course), 404);

        $actor = $this->actor($request);
        $this->enrollmentFor($actor, $course)?->recordActivity($lesson);

        $outline = $buildOutline($actor, $course);
        $lesson->load('attachments');

        return Inertia::render('learn/Lesson', [
            'outline' => $outline,
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content_type' => $lesson->content_type,
                'content' => $lesson->content,
                'content_url' => $lesson->content_url,
                'embed_url' => $resolveVideo($lesson->content_url),
                'duration_minutes' => $lesson->duration_minutes,
                'section_title' => $lesson->section->title,
                'is_completed' => $actor->completedLessons()->whereKey($lesson->id)->exists(),
                'is_bookmarked' => $actor->bookmarkedLessons()->whereKey($lesson->id)->exists(),
                'note' => $actor->lessonNotes()->where('lesson_id', $lesson->id)->value('body'),
                'attachments' => $lesson->attachments
                    ->map(fn (LessonAttachment $attachment): array => [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => $attachment->size,
                        'mime_type' => $attachment->mime_type,
                    ])
                    ->all(),
            ],
            'discussion' => $this->discussion($lesson, $course),
            'neighbours' => BuildLearningOutline::neighbours($outline['items'], 'lesson', $lesson->id),
        ]);
    }

    /**
     * Mark the lesson as complete for the current student.
     */
    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->belongsToCourse($course), 404);

        $this->actor($request)->completedLessons()->syncWithoutDetaching([$lesson->id]);

        return back();
    }

    /**
     * Mark the lesson as not complete again.
     */
    public function uncomplete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->belongsToCourse($course), 404);

        $this->actor($request)->completedLessons()->detach($lesson->id);

        return back();
    }

    /**
     * Download a lesson attachment, only for people who may learn the course.
     */
    public function attachment(LessonAttachment $attachment): StreamedResponse
    {
        Gate::authorize('learn', $attachment->lesson->section->course);

        return Storage::disk(LessonAttachment::DISK)->download($attachment->path, $attachment->name);
    }

    /**
     * Build the lesson's discussion threads, newest first.
     *
     * @return list<array<string, mixed>>
     */
    private function discussion(Lesson $lesson, Course $course): array
    {
        $person = fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'is_staff' => $user->isAdmin() || $course->isOwnedBy($user),
        ];

        return $lesson->questions()
            ->with(['user:id,name,role,avatar_path', 'replies.user:id,name,role,avatar_path'])
            ->limit(self::DISCUSSION_LIMIT)
            ->get()
            ->map(fn (LessonQuestion $question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'created_at' => $question->created_at?->toIso8601String(),
                'author' => $person($question->user),
                'can_delete' => Gate::allows('delete', $question),
                'replies' => $question->replies
                    ->map(fn (LessonReply $reply): array => [
                        'id' => $reply->id,
                        'body' => $reply->body,
                        'created_at' => $reply->created_at?->toIso8601String(),
                        'author' => $person($reply->user),
                        'can_delete' => Gate::allows('delete', $reply),
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Get the student's active enrollment in the course, if they have one.
     */
    private function enrollmentFor(User $user, Course $course): ?Enrollment
    {
        return $course->enrollments()->active()->where('user_id', $user->id)->first();
    }

    /**
     * Send someone without access back to the course page with an explanation.
     */
    private function notEnrolled(Course $course): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'warning', 'message' => __('Enroll in this course to open its lessons.')]);

        return to_route('catalog.show', $course);
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        assert($actor instanceof User);

        return $actor;
    }
}
