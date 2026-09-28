<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonNoteRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonNoteController extends Controller
{
    /**
     * List every note the student has written, newest first.
     */
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $search = $request->string('search')->toString();

        $notes = $actor->lessonNotes()
            ->with('lesson:id,section_id,title,slug', 'lesson.section:id,course_id,title', 'lesson.section.course:id,title,slug')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('body', 'like', "%{$search}%")
                    ->orWhereHas('lesson', fn (Builder $lesson) => $lesson->where('title', 'like', "%{$search}%"))
            ))
            ->latest('updated_at')
            ->get()
            ->map(fn (LessonNote $note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'lesson' => $note->lesson->only(['id', 'slug', 'title']),
                'section' => ['id' => $note->lesson->section->id, 'title' => $note->lesson->section->title],
                'course' => $note->lesson->section->course->only(['id', 'slug', 'title']),
            ]);

        return Inertia::render('learning/Notes', [
            'notes' => $notes,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Save the student's note on a lesson, or remove it when left empty.
     */
    public function update(LessonNoteRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->belongsToCourse($course), 404);

        $actor = $this->actor($request);
        $body = trim($request->string('body')->toString());

        if ($body === '') {
            $actor->lessonNotes()->where('lesson_id', $lesson->id)->delete();
        } else {
            $actor->lessonNotes()->updateOrCreate(['lesson_id' => $lesson->id], ['body' => $body]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $body === '' ? __('Note removed.') : __('Note saved.')]);

        return back();
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
