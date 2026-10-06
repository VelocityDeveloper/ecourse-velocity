<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Course;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The search engine and social sharing tags of the public pages.
 *
 * The app renders in the browser, so these go into the HTML from the server
 * (resources/views/partials/seo.blade.php): WhatsApp, Facebook and most
 * crawlers never run the page's JavaScript. Each page also gets the same
 * title as a `seoTitle` prop, which app.ts uses as the document title.
 *
 * @phpstan-type SeoTags array{
 *     title: string,
 *     description: string,
 *     keywords: string|null,
 *     url: string,
 *     type: string,
 *     image: string|null,
 *     robots: string,
 *     site_name: string,
 *     google_verification: string|null,
 *     article: array{published_time: string|null, modified_time: string|null, author: string|null}|null,
 *     json_ld: list<array<string, mixed>>
 * }
 */
class Seo
{
    /**
     * Search results cut titles at about this many characters.
     */
    public const int TITLE_LIMIT = 60;

    /**
     * Search results cut descriptions at about this many characters.
     */
    public const int DESCRIPTION_LIMIT = 160;

    private const string INDEX = 'index, follow, max-image-preview:large';

    private const string NO_INDEX = 'noindex, follow';

    /**
     * The homepage title used until the admin writes one.
     */
    public static function defaultHomeTitle(): string
    {
        return self::siteName().' - Kursus Online dengan Instruktur Praktisi';
    }

    /**
     * The homepage title: the admin's own, or the default.
     */
    public static function homeTitle(): string
    {
        return SiteSetting::get('seo_title') ?? self::defaultHomeTitle();
    }

    /**
     * A page title followed by the site name, as every page but the homepage shows it.
     */
    public static function pageTitle(string $title): string
    {
        return $title.' - '.self::siteName();
    }

    /**
     * The homepage, from Pengaturan Situs → SEO.
     *
     * @return SeoTags
     */
    public static function home(): array
    {
        $description = self::text(
            SiteSetting::get('seo_description'),
            SiteSetting::get('site_description'),
            SiteSetting::DEFAULT_SITE_DESCRIPTION,
        );

        return self::tags(
            title: self::homeTitle(),
            description: $description,
            url: self::homeUrl(),
            image: self::homeImageUrl(),
            keywords: SiteSetting::get('seo_keywords'),
            googleVerification: SiteSetting::get('seo_google_verification'),
            jsonLd: [
                ['@context' => 'https://schema.org', ...self::organization(), 'description' => $description],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => self::siteName(),
                    'url' => self::homeUrl(),
                    'inLanguage' => 'id-ID',
                    // Lets Google offer a search box for the course catalogue under the result.
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => self::absolute(route('catalog.index', absolute: false)).'?search={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        );
    }

    /**
     * A course page: the course's own SEO texts, else its title and description.
     *
     * @param  array{average: float|null, count: int}  $rating
     * @return SeoTags
     */
    public static function course(Course $course, array $rating): array
    {
        $url = self::absolute($course->permalink());
        $description = self::text($course->seo_description, $course->description, SiteSetting::get('site_description'));
        $image = $course->thumbnail_url ?? self::homeImageUrl();
        $isFree = ! $course->isPaid();

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => $description,
            'url' => $url,
            'image' => $image,
            'inLanguage' => 'id-ID',
            'educationalLevel' => $course->level,
            'isAccessibleForFree' => $isFree,
            'provider' => self::organization(),
            'offers' => [
                '@type' => 'Offer',
                'category' => $isFree ? 'Free' : 'Paid',
                'price' => (float) $course->price,
                'priceCurrency' => 'IDR',
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
            ],
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'courseMode' => 'Online',
                'courseWorkload' => null,
                'instructor' => $course->instructor === null ? null : self::person($course->instructor, hasProfile: true),
            ],
            'aggregateRating' => $rating['count'] === 0 ? null : [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating['average'],
                'ratingCount' => $rating['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ], fn (mixed $value): bool => $value !== null);
        $schema['hasCourseInstance'] = array_filter($schema['hasCourseInstance'], fn (mixed $value): bool => $value !== null);

        return self::tags(
            title: $course->seo_title ?? self::pageTitle($course->title),
            description: $description,
            url: $url,
            image: $image,
            // Drafts are only visible to their owner and admins; keep them out of search.
            robots: $course->status === Course::STATUS_PUBLISHED ? self::INDEX : self::NO_INDEX,
            jsonLd: [
                $schema,
                self::breadcrumbs([
                    'Kursus' => route('catalog.index', absolute: false),
                    ...($course->category === null ? [] : [
                        $course->category->name => route('catalog.index', ['kategori' => $course->category->slug], absolute: false),
                    ]),
                    $course->title => $course->permalink(),
                ]),
            ],
        );
    }

    /**
     * A blog article, shared as an article with its cover picture.
     *
     * @return SeoTags
     */
    public static function post(Post $post): array
    {
        $url = self::absolute(route('blog.show', $post, absolute: false));
        $description = self::text($post->seo_description, $post->summary);
        $image = $post->cover_url ?? self::homeImageUrl();
        $published = $post->published_at?->toIso8601String();
        $modified = $post->updated_at?->toIso8601String();

        return self::tags(
            title: $post->seo_title ?? self::pageTitle($post->title),
            description: $description,
            url: $url,
            image: $image,
            type: 'article',
            robots: $post->isPublished() ? self::INDEX : self::NO_INDEX,
            article: [
                'published_time' => $published,
                'modified_time' => $modified,
                'author' => $post->author?->name,
            ],
            jsonLd: [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => Str::limit($post->title, 110, ''),
                    'description' => $description,
                    'image' => $image,
                    'url' => $url,
                    'mainEntityOfPage' => $url,
                    'inLanguage' => 'id-ID',
                    'datePublished' => $published,
                    'dateModified' => $modified,
                    'author' => $post->author === null ? self::organization() : self::person($post->author, $post->author->isInstructor()),
                    'publisher' => self::organization(),
                ], fn (mixed $value): bool => $value !== null),
                self::breadcrumbs([
                    'Blog' => route('blog.index', absolute: false),
                    $post->title => route('blog.show', $post, absolute: false),
                ]),
            ],
        );
    }

    /**
     * The course catalogue, or one category of it.
     *
     * Search results and sorted or filtered-by-level views are left out of the
     * index: they repeat the catalogue under endless addresses.
     *
     * @return SeoTags
     */
    public static function catalog(?Category $category, bool $isVariant, int $page): array
    {
        $title = $category === null ? 'Katalog Kursus' : 'Kursus '.$category->name;
        $parameters = array_filter([
            'kategori' => $category?->slug,
            'page' => $page > 1 ? $page : null,
        ]);

        return self::tags(
            title: self::pageTitle($page > 1 ? $title.' - Halaman '.$page : $title),
            description: self::text(
                $category?->description,
                $category === null
                    ? 'Jelajahi semua kursus online di '.self::siteName().': belajar terstruktur dengan kuis, sertifikat, dan instruktur praktisi.'
                    : 'Kumpulan kursus online '.$category->name.' di '.self::siteName().', lengkap dengan kuis, sertifikat, dan instruktur praktisi.',
            ),
            url: self::absolute(route('catalog.index', $parameters, absolute: false)),
            image: self::homeImageUrl(),
            robots: $isVariant ? self::NO_INDEX : self::INDEX,
            jsonLd: [self::breadcrumbs([
                'Kursus' => route('catalog.index', absolute: false),
                ...($category === null ? [] : [$category->name => route('catalog.index', ['kategori' => $category->slug], absolute: false)]),
            ])],
        );
    }

    /**
     * The blog's list of articles.
     *
     * @return SeoTags
     */
    public static function blog(bool $isSearch, int $page): array
    {
        return self::tags(
            title: self::pageTitle($page > 1 ? 'Blog - Halaman '.$page : 'Blog'),
            description: 'Artikel, tips belajar, dan kabar terbaru seputar dunia teknologi dari '.self::siteName().'.',
            url: self::absolute(route('blog.index', $page > 1 ? ['page' => $page] : [], absolute: false)),
            image: self::homeImageUrl(),
            robots: $isSearch ? self::NO_INDEX : self::INDEX,
            jsonLd: [self::breadcrumbs(['Blog' => route('blog.index', absolute: false)])],
        );
    }

    /**
     * An instructor's public profile.
     *
     * @return SeoTags
     */
    public static function instructor(User $user): array
    {
        $url = self::absolute(route('users.show', $user, absolute: false));

        return self::tags(
            title: self::pageTitle($user->name),
            description: self::text(
                $user->headline === null ? null : $user->name.' - '.$user->headline.'. '.$user->bio,
                $user->bio,
                'Profil dan kursus '.$user->name.' di '.self::siteName().'.',
            ),
            url: $url,
            image: $user->avatar === null ? self::homeImageUrl() : self::absolute($user->avatar),
            type: 'profile',
            // Only instructors have a public profile; a learner's is shown to logged-in users only.
            robots: $user->isInstructor() ? self::INDEX : self::NO_INDEX,
            jsonLd: [['@context' => 'https://schema.org', '@type' => 'ProfilePage', 'mainEntity' => self::person($user, $user->isInstructor())]],
        );
    }

    /**
     * The share picture of the homepage: the one uploaded for SEO, else the hero photo, else the logo.
     */
    public static function homeImageUrl(): ?string
    {
        foreach ([SiteSetting::SEO_IMAGE, SiteSetting::HERO_IMAGE, SiteSetting::LOGO] as $key) {
            $path = SiteSetting::get($key);

            if ($path !== null) {
                return self::absolute(Storage::disk(SiteSetting::LOGO_DISK)->url($path));
            }
        }

        return null;
    }

    /**
     * The site's address from APP_URL, so the site reached by IP or another host still names one home.
     */
    public static function homeUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/';
    }

    /**
     * A full address on this site for a path (crawlers need full addresses).
     */
    public static function absolute(string $pathOrUrl): string
    {
        if (preg_match('~^https?://~', $pathOrUrl) === 1) {
            return $pathOrUrl;
        }

        return rtrim(self::homeUrl(), '/').'/'.ltrim($pathOrUrl, '/');
    }

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     * @param  array{published_time: string|null, modified_time: string|null, author: string|null}|null  $article
     * @return SeoTags
     */
    private static function tags(
        string $title,
        string $description,
        string $url,
        ?string $image,
        string $type = 'website',
        string $robots = self::INDEX,
        ?string $keywords = null,
        ?string $googleVerification = null,
        ?array $article = null,
        array $jsonLd = [],
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'url' => $url,
            'type' => $type,
            'image' => $image,
            'robots' => $robots,
            'site_name' => self::siteName(),
            'google_verification' => $googleVerification,
            'article' => $article,
            'json_ld' => $jsonLd,
        ];
    }

    /**
     * The first text that has words in it, as plain text short enough for a search result.
     */
    private static function text(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $text = Str::squish(html_entity_decode(strip_tags(str_replace('<', ' <', (string) $candidate)), ENT_QUOTES | ENT_HTML5));

            if ($text !== '') {
                return Str::limit($text, self::DESCRIPTION_LIMIT, '…', preserveWords: true);
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private static function organization(): array
    {
        $logo = SiteSetting::logoUrl();

        return array_filter([
            '@type' => 'EducationalOrganization',
            'name' => self::siteName(),
            'url' => self::homeUrl(),
            'logo' => $logo === null ? null : self::absolute($logo),
            'email' => SiteSetting::get('contact_email'),
            'telephone' => SiteSetting::get('contact_phone'),
            'address' => SiteSetting::get('contact_address'),
            'sameAs' => array_values(array_filter(array_map(
                fn (string $network): ?string => SiteSetting::get('social_'.$network),
                SiteSetting::SOCIAL_NETWORKS,
            ))),
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    private static function person(User $user, bool $hasProfile): array
    {
        return array_filter([
            '@type' => 'Person',
            'name' => $user->name,
            'jobTitle' => $user->headline,
            'image' => $user->avatar === null ? null : self::absolute($user->avatar),
            // Only instructors have a public profile page to point to.
            'url' => $hasProfile ? self::absolute(route('users.show', $user, absolute: false)) : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The trail from the homepage down to the page, for the breadcrumb under a search result.
     *
     * @param  array<string, string>  $trail  name => path
     * @return array<string, mixed>
     */
    private static function breadcrumbs(array $trail): array
    {
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => self::homeUrl()]];

        foreach ($trail as $name => $path) {
            $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => (string) $name, 'item' => self::absolute($path)];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    private static function siteName(): string
    {
        return (string) config('app.name');
    }
}
