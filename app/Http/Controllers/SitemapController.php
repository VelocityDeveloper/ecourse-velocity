<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Post;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * The list of public pages for search engines: /sitemap.xml.
     */
    public function index(): Response
    {
        $latestCourse = Course::query()->published()->max('updated_at');
        $latestPost = Post::query()->published()->max('updated_at');

        $pages = [
            ['url' => Seo::homeUrl(), 'lastmod' => max($latestCourse, $latestPost), 'priority' => '1.0', 'image' => Seo::homeImageUrl()],
            ['url' => Seo::absolute(route('catalog.index', absolute: false)), 'lastmod' => $latestCourse, 'priority' => '0.9', 'image' => null],
        ];

        Category::query()
            ->whereHas('courses', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Category $category) use (&$pages): void {
                $pages[] = [
                    'url' => Seo::absolute(route('catalog.index', ['kategori' => $category->slug], absolute: false)),
                    'lastmod' => $category->updated_at,
                    'priority' => '0.7',
                    'image' => null,
                ];
            });

        Course::query()
            ->published()
            ->with('category:id,slug')
            ->latest('updated_at')
            ->get(['id', 'slug', 'category_id', 'thumbnail_path', 'updated_at'])
            ->each(function (Course $course) use (&$pages): void {
                $pages[] = [
                    'url' => Seo::absolute($course->permalink()),
                    'lastmod' => $course->updated_at,
                    'priority' => '0.8',
                    'image' => $course->thumbnail_url,
                ];
            });

        $pages[] = ['url' => Seo::absolute(route('blog.index', absolute: false)), 'lastmod' => $latestPost, 'priority' => '0.6', 'image' => null];

        Post::query()
            ->published()
            ->latest('published_at')
            ->get(['id', 'slug', 'cover_path', 'updated_at'])
            ->each(function (Post $post) use (&$pages): void {
                $pages[] = [
                    'url' => Seo::absolute(route('blog.show', $post, absolute: false)),
                    'lastmod' => $post->updated_at,
                    'priority' => '0.6',
                    'image' => $post->cover_url,
                ];
            });

        // Instructors with something to show; the profile of anyone else is not public.
        User::query()
            ->where('role', User::ROLE_INSTRUCTOR)
            ->whereHas('courses', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (User $user) use (&$pages): void {
                $pages[] = [
                    'url' => Seo::absolute(route('users.show', $user, absolute: false)),
                    'lastmod' => $user->updated_at,
                    'priority' => '0.5',
                    'image' => null,
                ];
            });

        return response()
            ->view('sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Tell crawlers what to skip and where the sitemap is: /robots.txt.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Private areas: they only send a crawler to the login page. Exact paths end in $
            // or /, so a course category such as /belajar-bahasa/… stays open.
            ...array_map(fn (string $path): string => 'Disallow: /'.$path, [
                'dasbor$', 'dasbor/', 'admin/', 'belajar/', 'belajar-saya', 'pengaturan/', 'notifikasi',
                'beli/', 'masuk$', 'daftar$', 'lupa-kata-sandi$', 'atur-ulang-kata-sandi/', 'captcha/',
            ]),
            'Disallow: /*?*search=',
            '',
            'Sitemap: '.Seo::absolute(route('sitemap', absolute: false)),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
