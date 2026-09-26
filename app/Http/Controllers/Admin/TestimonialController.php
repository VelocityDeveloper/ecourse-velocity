<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Requests\MoveRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    /**
     * List the testimonials in homepage order.
     */
    public function index(): Response
    {
        return Inertia::render('admin/Testimonials/Index', [
            'testimonials' => Testimonial::query()->ordered()->get(),
        ]);
    }

    /**
     * Add a testimonial at the end of the list.
     */
    public function store(TestimonialRequest $request): RedirectResponse
    {
        $testimonial = new Testimonial([
            ...$this->attributes($request),
            'sort_order' => ((int) Testimonial::query()->max('sort_order')) + 1,
        ]);
        $this->replacePhoto($request, $testimonial);
        $testimonial->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Testimonial added.')]);

        return to_route('admin.testimonials.index');
    }

    /**
     * Update a testimonial.
     */
    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->fill($this->attributes($request, $testimonial));
        $this->replacePhoto($request, $testimonial);
        $testimonial->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Testimonial updated.')]);

        return to_route('admin.testimonials.index');
    }

    /**
     * Swap a testimonial with its neighbour in the homepage order.
     */
    public function move(MoveRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $items = Testimonial::query()->ordered()->get()->values();
        $index = $items->search(fn (Testimonial $item) => $item->is($testimonial));
        $target = $request->string('direction')->toString() === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $target >= 0 && $target < $items->count()) {
            $order = $items->all();
            [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

            foreach ($order as $position => $item) {
                $item->update(['sort_order' => $position]);
            }
        }

        return to_route('admin.testimonials.index');
    }

    /**
     * Delete a testimonial and its photo.
     */
    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        if ($testimonial->photo_path !== null) {
            Storage::disk(Testimonial::PHOTO_DISK)->delete($testimonial->photo_path);
        }

        $testimonial->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Testimonial deleted.')]);

        return to_route('admin.testimonials.index');
    }

    /**
     * The validated fields to save; the switches keep their value when left out.
     *
     * @return array<string, mixed>
     */
    private function attributes(TestimonialRequest $request, ?Testimonial $current = null): array
    {
        return [
            ...$request->safe()->only(['name', 'quote', 'rating']),
            'subtitle' => $request->filled('subtitle') ? trim($request->string('subtitle')->toString()) : null,
            'mask_name' => $request->boolean('mask_name', $current !== null && $current->mask_name),
            'is_active' => $request->boolean('is_active', $current === null || $current->is_active),
        ];
    }

    /**
     * Store a newly uploaded photo, or remove the current one, deleting the old file.
     */
    private function replacePhoto(TestimonialRequest $request, Testimonial $testimonial): void
    {
        if (! $request->hasFile('photo') && ! $request->boolean('remove_photo')) {
            return;
        }

        if ($testimonial->photo_path !== null) {
            Storage::disk(Testimonial::PHOTO_DISK)->delete($testimonial->photo_path);
        }

        $path = $request->hasFile('photo')
            ? $request->file('photo')?->store(Testimonial::PHOTO_DIRECTORY, Testimonial::PHOTO_DISK)
            : null;

        $testimonial->photo_path = is_string($path) ? $path : null;
    }
}
