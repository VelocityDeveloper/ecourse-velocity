<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * How many other articles are suggested under an article.
     */
    private const int RELATED_LIMIT = 3;

    /**
     * List the published articles, newest first.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $posts = Post::query()
            ->published()
            ->with('author:id,name,slug,avatar_path')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
            ))
            ->latest('published_at')
            ->latest('id')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->card($post));

        $seo = Seo::blog($search !== '', $posts->currentPage());

        return Inertia::render('blog/Index', [
            'seoTitle' => $seo['title'],
            'posts' => $posts,
            'filters' => $request->only(['search']),
        ])->withViewData(['seo' => $seo]);
    }

    /**
     * Show one article. Admins can preview it before it is published.
     */
    public function show(Request $request, Post $post): Response
    {
        $isAdmin = $request->user()?->isAdmin() ?? false;

        abort_unless($post->isPublished() || $isAdmin, 404);

        $post->load('author:id,name,slug,avatar_path,headline,role');
        $seo = Seo::post($post);

        return Inertia::render('blog/Show', [
            'seoTitle' => $seo['title'],
            'post' => [
                ...$this->card($post),
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'updated_at' => $post->updated_at?->toIso8601String(),
                'is_live' => $post->isPublished(),
                'author' => $post->author === null ? null : [
                    ...$post->author->only(['id', 'name', 'slug', 'avatar', 'headline']),
                    'is_instructor' => $post->author->isInstructor(),
                ],
            ],
            'related' => Post::query()
                ->published()
                ->whereKeyNot($post->id)
                ->with('author:id,name,slug,avatar_path')
                ->latest('published_at')
                ->limit(self::RELATED_LIMIT)
                ->get()
                ->map(fn (Post $related): array => $this->card($related))
                ->all(),
            'can' => [
                'edit' => $isAdmin,
            ],
        ])->withViewData(['seo' => $seo]);
    }

    /**
     * The fields an article card shows.
     *
     * @return array<string, mixed>
     */
    private function card(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'summary' => $post->summary,
            'cover_url' => $post->cover_url,
            'reading_minutes' => $post->reading_minutes,
            'published_at' => $post->published_at?->toIso8601String(),
            'author' => $post->author?->only(['id', 'name', 'slug', 'avatar']),
        ];
    }
}
