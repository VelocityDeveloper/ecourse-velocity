<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRequest;
use App\Http\Requests\SectionRequest;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    /**
     * Append a section to the course curriculum.
     */
    public function store(SectionRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->sections()->create([
            ...$request->validated(),
            'position' => ((int) $course->sections()->max('position')) + 1,
        ]);

        return back();
    }

    /**
     * Rename or re-describe a section.
     */
    public function update(SectionRequest $request, Section $section): RedirectResponse
    {
        Gate::authorize('update', $section->course);

        $section->update($request->validated());

        return back();
    }

    /**
     * Move the section one step up or down the curriculum.
     */
    public function move(MoveRequest $request, Section $section): RedirectResponse
    {
        Gate::authorize('update', $section->course);

        $section->move($request->string('direction')->toString());

        return back();
    }

    /**
     * Delete the section along with its lessons.
     */
    public function destroy(Section $section): RedirectResponse
    {
        Gate::authorize('update', $section->course);

        $section->delete();
        $section->resequenceSiblings();

        return back();
    }
}
