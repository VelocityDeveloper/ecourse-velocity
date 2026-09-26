<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List every course category.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $categories = Category::query()
            ->withCount('courses')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Categories/Create');
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->safe()->only(['name', 'slug', 'description']));
        $this->replaceImage($request, $category);
        $category->save();

        return to_route('admin.categories.index');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('admin/Categories/Edit', [
            'category' => $category->only(['id', 'name', 'slug', 'description', 'image_url']),
        ]);
    }

    /**
     * Update the given category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill($request->safe()->only(['name', 'slug', 'description']));
        $this->replaceImage($request, $category);
        $category->save();

        return to_route('admin.categories.index');
    }

    /**
     * Delete the given category, leaving its courses uncategorised.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image_path !== null) {
            Storage::disk(Category::IMAGE_DISK)->delete($category->image_path);
        }

        $category->delete();

        return to_route('admin.categories.index');
    }

    /**
     * Store a newly uploaded image, or remove the current one, deleting the old file.
     */
    private function replaceImage(StoreCategoryRequest|UpdateCategoryRequest $request, Category $category): void
    {
        if (! $request->hasFile('image') && ! $request->boolean('remove_image')) {
            return;
        }

        if ($category->image_path !== null) {
            Storage::disk(Category::IMAGE_DISK)->delete($category->image_path);
        }

        $path = $request->hasFile('image')
            ? $request->file('image')?->store(Category::IMAGE_DIRECTORY, Category::IMAGE_DISK)
            : null;

        $category->image_path = is_string($path) ? $path : null;
    }
}
