<?php

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(SiteSetting::LOGO_DISK);
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.settings.edit', 'identitas'))->assertRedirect(route('login'));
});

test('instructors cannot change the site logo', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('admin.settings.edit', 'identitas'))->assertForbidden();
    $this->actingAs($instructor)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->image('logo.png')])
        ->assertForbidden();

    expect(SiteSetting::get(SiteSetting::LOGO))->toBeNull();
});

test('students cannot change the site logo', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.settings.edit', 'identitas'))->assertRedirect(route('home'));
    $this->actingAs($student)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->image('logo.png')])
        ->assertForbidden();
});

test('an admin sees the settings page with the built-in logo by default', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit', 'identitas'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Settings/Identity')
            ->where('logoUrl', null)
            ->where('branding.logoUrl', null));
});

test('an admin can upload a logo and every page shares it', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->image('logo.png', 400, 100)])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'identitas'));

    $path = SiteSetting::get(SiteSetting::LOGO);

    expect($path)->toStartWith(SiteSetting::LOGO_DIRECTORY.'/');
    Storage::disk(SiteSetting::LOGO_DISK)->assertExists($path);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk(SiteSetting::LOGO_DISK)->url($path)));
});

test('uploading a new logo replaces the previous file', function () {
    $admin = User::factory()->admin()->create();
    $old = UploadedFile::fake()->image('old.png')->store(SiteSetting::LOGO_DIRECTORY, SiteSetting::LOGO_DISK);
    SiteSetting::put(SiteSetting::LOGO, $old);

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->image('new.webp')])
        ->assertSessionHasNoErrors();

    Storage::disk(SiteSetting::LOGO_DISK)->assertMissing($old);
    expect(SiteSetting::get(SiteSetting::LOGO))->not->toBe($old);
});

test('an admin can go back to the built-in logo', function () {
    $admin = User::factory()->admin()->create();
    $old = UploadedFile::fake()->image('old.png')->store(SiteSetting::LOGO_DIRECTORY, SiteSetting::LOGO_DISK);
    SiteSetting::put(SiteSetting::LOGO, $old);

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['remove_logo' => '1'])
        ->assertSessionHasNoErrors();

    Storage::disk(SiteSetting::LOGO_DISK)->assertMissing($old);
    expect(SiteSetting::get(SiteSetting::LOGO))->toBeNull()
        ->and(SiteSetting::logoUrl())->toBeNull();
});

test('the logo must be a png, jpg or webp image', function (string $file) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->create($file, 10)])
        ->assertSessionHasErrors('logo');

    expect(SiteSetting::get(SiteSetting::LOGO))->toBeNull();
})->with(['logo.svg', 'logo.pdf', 'logo.gif']);

test('the logo may not be larger than 2 MB', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['logo' => UploadedFile::fake()->image('logo.png')->size(2049)])
        ->assertSessionHasErrors('logo');
});

test('an admin can set the main colour and every page shares its palette', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['primary_color' => '#2563EB'])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::get(SiteSetting::PRIMARY_COLOR))->toBe('#2563eb');

    $this->get(route('home'))
        ->assertSee('<style id="brand-palette">:root{--primary:hsl(221', false)
        ->assertInertia(fn ($page) => $page
            ->where('branding.paletteCss', fn (string $css) => str_contains($css, '.dark{--primary:')));
});

test('an empty colour goes back to the built-in palette', function () {
    $admin = User::factory()->admin()->create();
    SiteSetting::put(SiteSetting::PRIMARY_COLOR, '#2563eb');

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['primary_color' => ''])
        ->assertSessionHasNoErrors();

    expect(SiteSetting::get(SiteSetting::PRIMARY_COLOR))->toBeNull()
        ->and(SiteSetting::paletteCss())->toBeNull();
    $this->get(route('home'))->assertSee('<style id="brand-palette"></style>', false);
});

test('the main colour must be a #rrggbb value', function (string $color) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['primary_color' => $color])
        ->assertSessionHasErrors('primary_color');
})->with(['red', '#fff', '#12345g', '#2563eb;}body{display:none']);

test('saving the colour leaves the logo and banner untouched', function () {
    $admin = User::factory()->admin()->create();
    SiteSetting::put(SiteSetting::LOGO, 'branding/logo.png');
    SiteSetting::put('hero_title', 'Judul sendiri');

    $this->actingAs($admin)->post(route('admin.settings.update'), ['primary_color' => '#16a34a']);

    expect(SiteSetting::get(SiteSetting::LOGO))->toBe('branding/logo.png')
        ->and(SiteSetting::get('hero_title'))->toBe('Judul sendiri');
});

test('the homepage shows the default banner until an admin changes it', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('banner.hero_title', SiteSetting::BANNER_DEFAULTS['hero_title'])
            ->where('banner.cta_title', SiteSetting::BANNER_DEFAULTS['cta_title'])
            ->where('banner.hero_image_url', null));
});

test('an admin can change the banner texts and hero image', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'hero_badge' => 'Kelas baru tiap bulan',
            'hero_title' => '  Belajar coding,  ',
            'hero_highlight' => 'dari nol sampai kerja.',
            'hero_description' => '',
            'cta_title' => 'Gabung sekarang',
            'cta_description' => 'Gratis selamanya.',
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 900),
        ])
        ->assertSessionHasNoErrors();

    $image = SiteSetting::get(SiteSetting::HERO_IMAGE);
    Storage::disk(SiteSetting::LOGO_DISK)->assertExists($image);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('banner.hero_badge', 'Kelas baru tiap bulan')
            ->where('banner.hero_title', 'Belajar coding,')
            ->where('banner.hero_highlight', 'dari nol sampai kerja.')
            ->where('banner.hero_description', SiteSetting::BANNER_DEFAULTS['hero_description'])
            ->where('banner.cta_title', 'Gabung sekarang')
            ->where('banner.hero_image_url', Storage::disk(SiteSetting::LOGO_DISK)->url($image)));
});

test('an admin can remove the hero image', function () {
    $admin = User::factory()->admin()->create();
    $old = UploadedFile::fake()->image('hero.jpg')->store(SiteSetting::LOGO_DIRECTORY, SiteSetting::LOGO_DISK);
    SiteSetting::put(SiteSetting::HERO_IMAGE, $old);

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['remove_hero_image' => '1'])
        ->assertSessionHasNoErrors();

    Storage::disk(SiteSetting::LOGO_DISK)->assertMissing($old);
    expect(SiteSetting::banner()['hero_image_url'])->toBeNull();
});

test('banner texts are limited in length', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['hero_title' => str_repeat('a', 121)])
        ->assertSessionHasErrors('hero_title');
});

test('each settings page opens from the submenu', function (string $section, string $component) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['identitas', 'admin/Settings/Identity'],
    ['warna', 'admin/Settings/Colors'],
    ['hero', 'admin/Settings/Hero'],
    ['kontak', 'admin/Settings/Contact'],
    ['footer', 'admin/Settings/Footer'],
]);

test('the settings root opens the first page and unknown pages are not found', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/dasbor/pengaturan-situs')->assertRedirect('/dasbor/pengaturan-situs/identitas');
    $this->actingAs($admin)->get('/dasbor/pengaturan-situs/lainnya')->assertNotFound();
});

test('saving goes back to the page the form came from', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['section' => 'warna', 'primary_color' => '#16a34a'])
        ->assertRedirect(route('admin.settings.edit', 'warna'));
});

test('the footer shows the contact details and socials an admin fills in', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'section' => 'kontak',
            'site_description' => 'Tempat belajar teknik.',
            'contact_address' => 'Jl. Sukses No. 12, Bandung',
            'contact_email' => 'halo@contoh.id',
            'contact_phone' => '0812-5544-2561',
            'contact_hours' => 'Setiap hari 08.00–17.00',
            'social_instagram' => 'https://instagram.com/contoh',
            'social_youtube' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'kontak'));

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('site.description', 'Tempat belajar teknik.')
            ->where('site.email', 'halo@contoh.id')
            ->where('site.whatsapp_url', 'https://wa.me/6281255442561')
            ->where('site.socials', [['network' => 'instagram', 'url' => 'https://instagram.com/contoh']]));
});

test('the footer falls back to the default description and hides empty contacts', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('site.description', SiteSetting::DEFAULT_SITE_DESCRIPTION)
            ->where('site.address', null)
            ->where('site.whatsapp_url', null)
            ->where('site.socials', []));
});

test('contact fields are validated', function (string $field, string $value) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [$field => $value])
        ->assertSessionHasErrors($field);
})->with([
    ['contact_email', 'bukan-email'],
    ['contact_phone', '0812<script>'],
    ['social_instagram', 'javascript:alert(1)'],
    ['social_tiktok', 'http://tiktok.com/@contoh'],
]);

/**
 * A footer form as the admin page submits it.
 *
 * @return array<string, mixed>
 */
function footerPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'section' => 'footer',
        'footer' => SiteSetting::FOOTER_DEFAULTS,
    ], $overrides);
}

test('the footer uses the default layout until an admin changes it', function () {
    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->where('site.footer.explore.title', 'Jelajahi')
        ->has('site.footer.explore.links', 7)
        ->where('site.footer.categories.limit', 6)
        ->where('site.footer.back_to_top.show', true)
    );
});

test('an admin can change every part of the footer', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), ['section' => 'footer', 'footer' => [
            'categories' => ['show' => false, 'title' => 'Bidang', 'limit' => 3, 'see_all_label' => 'Semua bidang'],
            'explore' => ['show' => true, 'title' => 'Tautan', 'links' => [
                ['label' => ' Tentang Kami ', 'url' => '/blog/tentang'],
                ['label' => 'Komunitas', 'url' => 'https://t.me/contoh'],
            ]],
            'account' => ['show' => false, 'title' => 'Akun'],
            'statistics' => ['show' => true, 'title' => 'Pengunjung', 'online' => false],
            'copyright' => '© {tahun} PT Contoh',
            'whatsapp' => ['show' => true, 'number' => '0812-1111-2222', 'message' => 'Halo admin'],
            'back_to_top' => ['show' => false, 'label' => 'Ke atas'],
            'floating_position' => 'left',
        ]])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.edit', 'footer'));

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->where('site.footer.categories.show', false)
        ->where('site.footer.categories.title', 'Bidang')
        ->where('site.footer.statistics.title', 'Pengunjung')
        ->where('site.footer.statistics.online', false)
        ->where('site.footer.categories.limit', 3)
        ->where('site.footer.explore.title', 'Tautan')
        ->where('site.footer.explore.links', [
            ['label' => 'Tentang Kami', 'url' => '/blog/tentang'],
            ['label' => 'Komunitas', 'url' => 'https://t.me/contoh'],
        ])
        ->where('site.footer.account.show', false)
        ->where('site.footer.copyright', '© {tahun} PT Contoh')
        ->where('site.footer.back_to_top.show', false)
        ->where('site.footer.floating_position', 'left')
        ->where('site.floating_whatsapp_url', 'https://wa.me/6281211112222?text=Halo%20admin')
    );
});

test('the floating whatsapp button falls back to the contact number and can be turned off', function () {
    $admin = User::factory()->admin()->create();

    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('site.floating_whatsapp_url', null));

    SiteSetting::put('contact_phone', '0813 9999 0000');

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->where('site.floating_whatsapp_url', 'https://wa.me/6281399990000')
        ->where('site.footer.floating_position', 'right')
    );

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), footerPayload(['footer' => ['whatsapp' => ['show' => false]]]))
        ->assertSessionHasNoErrors();

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->where('site.floating_whatsapp_url', null)
        ->where('site.whatsapp_url', 'https://wa.me/6281399990000')
    );
});

test('the floating buttons settings are validated', function (array $footer, string $field) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), footerPayload(['footer' => $footer]))
        ->assertSessionHasErrors($field);
})->with([
    'letters in the number' => [['whatsapp' => ['number' => 'nomor saya']], 'footer.whatsapp.number'],
    'unknown corner' => [['floating_position' => 'top'], 'footer.floating_position'],
    'long message' => [['whatsapp' => ['message' => str_repeat('a', 201)]], 'footer.whatsapp.message'],
]);

test('empty footer texts fall back to their defaults', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), footerPayload(['footer' => [
            'explore' => ['title' => ''],
            'copyright' => '  ',
        ]]))
        ->assertSessionHasNoErrors();

    expect(SiteSetting::footer()['explore']['title'])->toBe('Jelajahi')
        ->and(SiteSetting::footer()['copyright'])->toBe(SiteSetting::FOOTER_DEFAULTS['copyright']);
});

test('footer links are validated', function (array $link, string $field) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.update'), [
            ...footerPayload(),
            'footer' => [...SiteSetting::FOOTER_DEFAULTS, 'explore' => ['show' => true, 'title' => 'Jelajahi', 'links' => [$link]]],
        ])
        ->assertSessionHasErrors("footer.explore.links.0.{$field}");
})->with([
    'no label' => [['label' => '', 'url' => '/kursus'], 'label'],
    'script link' => [['label' => 'X', 'url' => 'javascript:alert(1)'], 'url'],
    'protocol-relative' => [['label' => 'X', 'url' => '//evil.test'], 'url'],
    'bare word' => [['label' => 'X', 'url' => 'kursus'], 'url'],
]);

test('the footer holds at most twelve links and one to twelve categories', function () {
    $admin = User::factory()->admin()->create();
    $links = array_fill(0, 13, ['label' => 'Tautan', 'url' => '/kursus']);

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            ...footerPayload(),
            'footer' => [...SiteSetting::FOOTER_DEFAULTS, 'explore' => ['show' => true, 'title' => 'Jelajahi', 'links' => $links]],
        ])
        ->assertSessionHasErrors('footer.explore.links');

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), footerPayload(['footer' => ['categories' => ['limit' => 0]]]))
        ->assertSessionHasErrors('footer.categories.limit');
});

test('only admins can change the footer', function () {
    $this->actingAs(User::factory()->instructor()->create())
        ->post(route('admin.settings.update'), footerPayload())
        ->assertForbidden();

    expect(SiteSetting::get(SiteSetting::FOOTER))->toBeNull();
});
