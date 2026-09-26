<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Http\Requests\MoveRequest;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    /**
     * List the promo banners in slider order.
     */
    public function index(): Response
    {
        return Inertia::render('admin/Banners/Index', [
            'banners' => Banner::query()->ordered()->get(),
        ]);
    }

    /**
     * Add a banner at the end of the slider.
     */
    public function store(BannerRequest $request): RedirectResponse
    {
        $path = $request->file('image')?->store(Banner::IMAGE_DIRECTORY, Banner::IMAGE_DISK);

        abort_if($path === null || $path === false, 500);

        Banner::query()->create([
            ...$request->safe()->only(['title', 'link_url']),
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $path,
            'sort_order' => ((int) Banner::query()->max('sort_order')) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Banner added.')]);

        return to_route('admin.banners.index');
    }

    /**
     * Update a banner's text, link, visibility or image.
     */
    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->fill([
            ...$request->safe()->only(['title', 'link_url']),
            'is_active' => $request->boolean('is_active', $banner->is_active),
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')?->store(Banner::IMAGE_DIRECTORY, Banner::IMAGE_DISK);

            if (is_string($path)) {
                Storage::disk(Banner::IMAGE_DISK)->delete($banner->image_path);
                $banner->image_path = $path;
            }
        }

        $banner->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Banner updated.')]);

        return to_route('admin.banners.index');
    }

    /**
     * Swap a banner with its neighbour in the slider order.
     */
    public function move(MoveRequest $request, Banner $banner): RedirectResponse
    {
        $banners = Banner::query()->ordered()->get()->values();
        $index = $banners->search(fn (Banner $item) => $item->is($banner));
        $target = $request->string('direction')->toString() === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $target >= 0 && $target < $banners->count()) {
            $order = $banners->all();
            [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

            foreach ($order as $position => $item) {
                $item->update(['sort_order' => $position]);
            }
        }

        return to_route('admin.banners.index');
    }

    /**
     * Delete a banner and its image.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        Storage::disk(Banner::IMAGE_DISK)->delete($banner->image_path);
        $banner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Banner deleted.')]);

        return to_route('admin.banners.index');
    }
}
