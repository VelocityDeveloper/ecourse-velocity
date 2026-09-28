<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SanitizeLessonContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * List every blog article, newest first.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $posts = Post::query()
            ->with('author:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->when(in_array($status, Post::STATUSES, true), fn (Builder $query) => $query->where('status', $status))
            ->orderByRaw('published_at IS NULL DESC')
            ->latest('published_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'is_live' => $post->isPublished(),
                'published_at' => $post->published_at?->toIso8601String(),
                'updated_at' => $post->updated_at?->toIso8601String(),
                'cover_url' => $post->cover_url,
                'author' => $post->author?->name,
            ]);

        return Inertia::render('admin/Posts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['search', 'status']),
            'counts' => [
                'all' => Post::query()->count(),
                Post::STATUS_PUBLISHED => Post::query()->where('status', Post::STATUS_PUBLISHED)->count(),
                Post::STATUS_DRAFT => Post::query()->where('status', Post::STATUS_DRAFT)->count(),
            ],
        ]);
    }

    /**
     * Show the form for writing an article.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Posts/Create');
    }

    /**
     * Store a newly written article.
     */
    public function store(PostRequest $request, SanitizeLessonContent $sanitize): RedirectResponse
    {
        $post = new Post;
        $post->user_id = $request->user()?->id;
        $this->fill($post, $request, $sanitize);
        $post->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article saved.')]);

        return to_route('admin.posts.edit', $post->slug);
    }

    /**
     * Show the form for editing an article.
     */
    public function edit(Post $post): Response
    {
        return Inertia::render('admin/Posts/Edit', [
            'post' => [
                ...$post->only(['id', 'title', 'slug', 'excerpt', 'content', 'status', 'cover_url']),
                'published_at' => $post->published_at?->toIso8601String(),
                'is_live' => $post->isPublished(),
            ],
        ]);
    }

    /**
     * Update the given article.
     */
    public function update(PostRequest $request, Post $post, SanitizeLessonContent $sanitize): RedirectResponse
    {
        $this->fill($post, $request, $sanitize);
        $post->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article saved.')]);

        return to_route('admin.posts.edit', $post->slug);
    }

    /**
     * Delete the given article and its cover image.
     */
    public function destroy(Post $post): RedirectResponse
    {
        if ($post->cover_path !== null) {
            Storage::disk(Post::COVER_DISK)->delete($post->cover_path);
        }

        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article deleted.')]);

        return to_route('admin.posts.index');
    }

    /**
     * Copy the validated fields onto the article.
     */
    private function fill(Post $post, PostRequest $request, SanitizeLessonContent $sanitize): void
    {
        $post->fill($request->safe()->only(['title', 'slug', 'excerpt', 'status']));
        $post->content = $sanitize($request->string('content')->toString());

        $publishedAt = $request->filled('published_at')
            ? Date::parse($request->string('published_at')->toString())->utc()
            : null;

        // Publishing without a date publishes now; a draft keeps whatever date it had.
        if ($post->status === Post::STATUS_PUBLISHED) {
            $post->published_at = $publishedAt ?? $post->published_at ?? now();
        } elseif ($publishedAt !== null) {
            $post->published_at = $publishedAt;
        }

        $this->replaceCover($request, $post);
    }

    /**
     * Store a newly uploaded cover, or remove the current one, deleting the old file.
     */
    private function replaceCover(PostRequest $request, Post $post): void
    {
        if (! $request->hasFile('cover') && ! $request->boolean('remove_cover')) {
            return;
        }

        if ($post->cover_path !== null) {
            Storage::disk(Post::COVER_DISK)->delete($post->cover_path);
        }

        $path = $request->hasFile('cover')
            ? $request->file('cover')?->store(Post::COVER_DIRECTORY, Post::COVER_DISK)
            : null;

        $post->cover_path = is_string($path) ? $path : null;
    }
}
