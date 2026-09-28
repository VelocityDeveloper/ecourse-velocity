<?php

namespace App\Http\Controllers;

use App\Actions\SanitizeLessonContent;
use App\Http\Requests\LessonRequest;
use App\Http\Requests\MoveRequest;
use App\Http\Requests\MoveToSectionRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * List every lesson across the courses the user may manage.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $actor = $this->actor($request);
        $search = $request->string('search')->toString();
        $type = $request->string('content_type')->toString();

        $lessons = Lesson::query()
            ->with(['section:id,title,course_id', 'section.course:id,slug,title,instructor_id'])
            ->withCount('attachments')
            ->whereHas('section.course', fn (Builder $query) => $query->manageableBy($actor))
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->when(in_array($type, Lesson::CONTENT_TYPES, true), fn (Builder $query) => $query->where('content_type', $type))
            ->when($request->filled('course_id'), fn (Builder $query) => $query->whereHas(
                'section',
                fn (Builder $section) => $section->where('course_id', $request->integer('course_id'))
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'content_type' => $lesson->content_type,
                'duration_minutes' => $lesson->duration_minutes,
                'has_content' => $lesson->content !== null,
                'attachments_count' => (int) $lesson->attachments_count,
                'section' => ['id' => $lesson->section->id, 'title' => $lesson->section->title],
                'course' => ['id' => $lesson->section->course->id, 'slug' => $lesson->section->course->slug, 'title' => $lesson->section->course->title],
            ]);

        return Inertia::render('lessons/Index', [
            'lessons' => $lessons,
            'filters' => $request->only(['search', 'course_id', 'content_type']),
            'courses' => $this->courseOptions($actor),
            'contentTypes' => Lesson::CONTENT_TYPES,
        ]);
    }

    /**
     * Show the full page editor for a lesson.
     */
    public function edit(Course $course, Lesson $lesson): Response
    {
        $lesson->load(['section.course', 'attachments']);
        Gate::authorize('update', $lesson->section->course);

        return Inertia::render('lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'content_type' => $lesson->content_type,
                'content' => $lesson->content,
                'content_url' => $lesson->content_url,
                'duration_minutes' => $lesson->duration_minutes,
                'attachments' => $lesson->attachments
                    ->map(fn (LessonAttachment $attachment): array => [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => $attachment->size,
                        'mime_type' => $attachment->mime_type,
                        'url' => $attachment->url,
                    ])
                    ->all(),
            ],
            'section' => ['id' => $lesson->section->id, 'title' => $lesson->section->title],
            'course' => ['id' => $lesson->section->course->id, 'slug' => $lesson->section->course->slug, 'title' => $lesson->section->course->title],
            'contentTypes' => Lesson::CONTENT_TYPES,
            'maxAttachmentKilobytes' => LessonAttachment::MAX_KILOBYTES,
        ]);
    }

    /**
     * Append a lesson to the section.
     */
    public function store(LessonRequest $request, Section $section, SanitizeLessonContent $sanitize): RedirectResponse
    {
        Gate::authorize('update', $section->course);

        $section->lessons()->create([
            ...$request->validated(),
            'content' => $sanitize($request->input('content')),
            'position' => ((int) $section->lessons()->max('position')) + 1,
        ]);

        return back();
    }

    /**
     * Update the lesson details and material.
     */
    public function update(LessonRequest $request, Lesson $lesson, SanitizeLessonContent $sanitize): RedirectResponse
    {
        Gate::authorize('update', $lesson->section->course);

        $lesson->update([
            ...$request->validated(),
            'content' => $sanitize($request->input('content')),
        ]);

        return back();
    }

    /**
     * Move the lesson one step up or down within its section.
     */
    public function move(MoveRequest $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson->section->course);

        $lesson->move($request->string('direction')->toString());

        return back();
    }

    /**
     * Delete the lesson.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson->section->course);

        $lesson->delete();
        $lesson->resequenceSiblings();

        return back();
    }

    /**
     * Move an existing lesson into another section.
     *
     * The lesson keeps its material and attachments; only its place in the
     * curriculum changes. The section it leaves is resequenced.
     */
    public function moveToSection(MoveToSectionRequest $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson->section->course);

        $destination = $request->destination();
        $formerSectionId = $lesson->section_id;

        if ($formerSectionId === $destination->id) {
            return back();
        }

        DB::transaction(function () use ($lesson, $destination, $formerSectionId): void {
            $lesson->update([
                'section_id' => $destination->id,
                'position' => ((int) $destination->lessons()->max('position')) + 1,
            ]);

            $this->resequenceLessons($formerSectionId);
        });

        return back();
    }

    /**
     * Close the gaps left in a section after a lesson moved away.
     */
    private function resequenceLessons(int $sectionId): void
    {
        Lesson::query()
            ->where('section_id', $sectionId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (Lesson $lesson, int $index): void {
                $lesson->update(['position' => $index + 1]);
            });
    }

    /**
     * Get the courses the user may manage, as select options.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courseOptions(User $actor): array
    {
        return Course::query()
            ->manageableBy($actor)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->toArray();
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
