<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(SiteSetting::LOGO_DISK);
});

test('the homepage renders default search and sharing tags from the server', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)
        ->toContain('<title>'.e(Seo::defaultHomeTitle()).'</title>')
        ->toContain('<meta name="description" content="'.e(SiteSetting::DEFAULT_SITE_DESCRIPTION).'">')
        ->toContain('<link rel="canonical" href="'.rtrim(config('app.url'), '/').'/">')
        ->toContain('<meta property="og:title" content="'.e(Seo::defaultHomeTitle()).'">')
        ->toContain('"@type":"WebSite"')
        ->toContain('"@type":"EducationalOrganization"')
        ->not->toContain('google-site-verification')
        ->not->toContain('name="keywords"');
});

test('the dashboard gets no search tags', function () {
    expect($this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->getContent())
        ->not->toContain('og:title')
        ->not->toContain('application/ld+json');
});

test('an admin can set the homepage SEO', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'section' => 'seo',
            'seo_title' => 'Kursus Online Terbaik di Indonesia',
            'seo_description' => 'Belajar dari praktisi, sertifikat resmi.',
            'seo_keywords' => 'kursus online, belajar coding',
            'seo_google_verification' => '<meta name="google-site-verification" content="abc_DEF-123" />',
            'seo_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'seo'));

    expect(SiteSetting::get('seo_google_verification'))->toBe('abc_DEF-123');
    Storage::disk(SiteSetting::LOGO_DISK)->assertExists(SiteSetting::get(SiteSetting::SEO_IMAGE));

    $this->post(route('logout'));

    $html = $this->get(route('home'))->getContent();

    expect($html)
        ->toContain('<title>Kursus Online Terbaik di Indonesia</title>')
        ->toContain('<meta name="description" content="Belajar dari praktisi, sertifikat resmi.">')
        ->toContain('<meta name="keywords" content="kursus online, belajar coding">')
        ->toContain('<meta name="google-site-verification" content="abc_DEF-123">')
        ->toContain('og:image" content="http');

    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('seoTitle', 'Kursus Online Terbaik di Indonesia'));
});

test('the share picture falls back to the hero photo, then the logo', function () {
    expect(Seo::homeImageUrl())->toBeNull();

    SiteSetting::put(SiteSetting::LOGO, 'branding/logo.png');
    expect(Seo::homeImageUrl())->toEndWith('/storage/branding/logo.png');

    SiteSetting::put(SiteSetting::HERO_IMAGE, 'branding/hero.jpg');
    expect(Seo::homeImageUrl())->toEndWith('/storage/branding/hero.jpg');

    SiteSetting::put(SiteSetting::SEO_IMAGE, 'branding/share.jpg');
    expect(Seo::homeImageUrl())->toEndWith('/storage/branding/share.jpg');
});

test('an invalid verification code is refused', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), ['section' => 'seo', 'seo_google_verification' => 'kode <script>'])
        ->assertSessionHasErrors('seo_google_verification');
});

test('instructors cannot open the SEO settings', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.settings.edit', 'seo'))
        ->assertForbidden();
});

test('a course page carries its own tags and Course data', function () {
    $category = Category::factory()->create(['name' => 'Web Development', 'slug' => 'web-development']);
    $course = Course::factory()->published()->create([
        'title' => 'Laravel Dari Nol',
        'slug' => 'laravel-dari-nol',
        'category_id' => $category->id,
        'description' => '<p>Belajar <strong>Laravel</strong> sampai deploy.</p>',
        'price' => 150000,
    ]);

    $html = $this->get(route('catalog.show', $course->permalinkParameters()))->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Laravel Dari Nol - '.e(config('app.name')).'</title>')
        ->toContain('<meta name="description" content="Belajar Laravel sampai deploy.">')
        ->toContain('<link rel="canonical" href="'.Seo::absolute('/web-development/laravel-dari-nol').'">')
        ->toContain('"@type":"Course"')
        ->toContain('"priceCurrency":"IDR"')
        ->toContain('"@type":"BreadcrumbList"')
        ->toContain('content="index, follow');

    $course->update(['seo_title' => 'Kursus Laravel Terlengkap', 'seo_description' => 'Dari nol sampai mahir.']);

    $this->get(route('catalog.show', $course->permalinkParameters()))
        ->assertSee('<title>Kursus Laravel Terlengkap</title>', false)
        ->assertSee('<meta name="description" content="Dari nol sampai mahir.">', false);
    $this->get(route('catalog.show', $course->permalinkParameters()))
        ->assertInertia(fn ($page) => $page->where('seoTitle', 'Kursus Laravel Terlengkap'));
});

test('an instructor can set the SEO texts of their course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create(['instructor_id' => $instructor->id]);

    $this->actingAs($instructor)
        ->put(route('courses.update', $course), [
            'title' => $course->title,
            'description' => 'Isi',
            'category_id' => null,
            'price' => '0',
            'level' => 'beginner',
            'status' => Course::STATUS_DRAFT,
            'seo_title' => 'Judul untuk Google',
            'seo_description' => 'Deskripsi untuk Google.',
        ])
        ->assertSessionHasNoErrors();

    expect($course->refresh())
        ->seo_title->toBe('Judul untuk Google')
        ->seo_description->toBe('Deskripsi untuk Google.');
});

test('a blog article is shared as an article, and a draft preview is not indexed', function () {
    $post = Post::factory()->create(['title' => 'Tips Belajar', 'slug' => 'tips-belajar', 'excerpt' => 'Lima kebiasaan kecil.']);

    expect($this->get(route('blog.show', $post))->getContent())
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('article:published_time')
        ->toContain('"@type":"BlogPosting"')
        ->toContain('<meta name="description" content="Lima kebiasaan kecil.">');

    $draft = Post::factory()->draft()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('blog.show', $draft))
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('an admin can set the SEO texts of an article', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.posts.update', $post->slug), [
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => Post::STATUS_PUBLISHED,
            'seo_title' => 'Artikel untuk Google',
            'seo_description' => 'Ringkas dan jelas.',
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('logout'));

    $this->get(route('blog.show', $post->refresh()))
        ->assertSee('<title>Artikel untuk Google</title>', false)
        ->assertSee('<meta name="description" content="Ringkas dan jelas.">', false);
});

test('catalogue searches are kept out of the index', function () {
    $this->get(route('catalog.index'))->assertSee('<meta name="robots" content="index, follow', false);
    $this->get(route('catalog.index', ['search' => 'laravel']))->assertSee('<meta name="robots" content="noindex, follow">', false);
    $this->get(route('blog.index', ['search' => 'tips']))->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('the sitemap lists public pages only', function () {
    $category = Category::factory()->create(['slug' => 'desain']);
    $published = Course::factory()->published()->create(['category_id' => $category->id, 'slug' => 'kursus-terbit']);
    Course::factory()->create(['slug' => 'kursus-draf']);
    $post = Post::factory()->create(['slug' => 'artikel-terbit']);
    Post::factory()->draft()->create(['slug' => 'artikel-draf']);

    $response = $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect($response->getContent())
        ->toContain('<loc>'.Seo::homeUrl().'</loc>')
        ->toContain(Seo::absolute($published->permalink()))
        ->toContain(Seo::absolute('/kursus?kategori=desain'))
        ->toContain(Seo::absolute('/blog/artikel-terbit'))
        ->toContain(Seo::absolute(route('users.show', $published->instructor, absolute: false)))
        ->not->toContain('kursus-draf')
        ->not->toContain('artikel-draf');

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
});

test('robots.txt points to the sitemap and closes private areas', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.Seo::absolute('/sitemap.xml'), false)
        ->assertSee('Disallow: /dasbor/', false);
});

test('the course editor previews the real public address', function () {
    $category = Category::factory()->create(['slug' => 'web-development']);
    $course = Course::factory()->create(['category_id' => $category->id, 'slug' => 'laravel-dari-nol']);

    $this->actingAs($course->instructor)
        ->get(route('courses.show', $course))
        ->assertInertia(fn ($page) => $page->where('form.permalink', '/web-development/laravel-dari-nol'));
});
