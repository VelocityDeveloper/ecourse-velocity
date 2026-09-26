<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonAttachmentRequest;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class LessonAttachmentController extends Controller
{
    /**
     * Attach one or more files to the lesson.
     */
    public function store(StoreLessonAttachmentRequest $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson->section->course);

        foreach (Arr::wrap($request->file('files')) as $file) {
            $path = $file->store(LessonAttachment::DIRECTORY, LessonAttachment::DISK);

            if ($path === false) {
                continue;
            }

            $lesson->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => max(0, (int) $file->getSize()),
            ]);
        }

        return back();
    }

    /**
     * Remove an attachment, deleting the stored file with it.
     */
    public function destroy(LessonAttachment $attachment): RedirectResponse
    {
        Gate::authorize('update', $attachment->lesson->section->course);

        $attachment->delete();

        return back();
    }
}
