<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\SiteSetting;
use App\Providers\FortifyServiceProvider;
use App\Support\Captcha;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    /**
     * The settings pages (Admin → Pengaturan Situs submenu) and the Vue page each renders.
     *
     * @var array<string, string>
     */
    public const array SECTIONS = [
        'identitas' => 'admin/Settings/Identity',
        'warna' => 'admin/Settings/Colors',
        'hero' => 'admin/Settings/Hero',
        'kontak' => 'admin/Settings/Contact',
        'footer' => 'admin/Settings/Footer',
        'seo' => 'admin/Settings/Seo',
        'keamanan' => 'admin/Settings/Security',
    ];

    /**
     * Show one settings page.
     */
    public function edit(string $section = 'identitas'): Response
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        return Inertia::render(self::SECTIONS[$section], match ($section) {
            'identitas' => [
                'logoUrl' => SiteSetting::logoUrl(),
            ],
            'warna' => [
                'primaryColor' => SiteSetting::get(SiteSetting::PRIMARY_COLOR),
            ],
            'hero' => [
                'banner' => [
                    'texts' => $this->values(array_keys(SiteSetting::BANNER_DEFAULTS)),
                    'defaults' => SiteSetting::BANNER_DEFAULTS,
                    'heroImageUrl' => SiteSetting::banner()['hero_image_url'],
                ],
            ],
            'kontak' => [
                'values' => $this->values([
                    ...SiteSetting::CONTACT_FIELDS,
                    ...array_map(fn (string $network) => 'social_'.$network, SiteSetting::SOCIAL_NETWORKS),
                ]),
                'defaultDescription' => SiteSetting::DEFAULT_SITE_DESCRIPTION,
            ],
            'footer' => [
                'footer' => SiteSetting::footer(),
                'defaults' => SiteSetting::FOOTER_DEFAULTS,
                'maxLinks' => SiteSetting::FOOTER_MAX_LINKS,
                'contactPhone' => SiteSetting::get('contact_phone'),
            ],
            'seo' => [
                'values' => $this->values(SiteSetting::SEO_FIELDS),
                'defaults' => [
                    'title' => Seo::defaultHomeTitle(),
                    'description' => SiteSetting::get('site_description') ?? SiteSetting::DEFAULT_SITE_DESCRIPTION,
                ],
                'limits' => ['title' => Seo::TITLE_LIMIT, 'description' => Seo::DESCRIPTION_LIMIT],
                'sitemapUrl' => Seo::absolute(route('sitemap', absolute: false)),
                'imageUrl' => ($path = SiteSetting::get(SiteSetting::SEO_IMAGE)) === null
                    ? null
                    : Storage::disk(SiteSetting::LOGO_DISK)->url($path),
                // What gets shared while no SEO picture is uploaded: the hero photo or the logo.
                'fallbackImageUrl' => SiteSetting::banner()['hero_image_url'] ?? SiteSetting::logoUrl(),
                'siteUrl' => Seo::homeUrl(),
            ],
            'keamanan' => [
                'captcha' => array_combine(Captcha::FORMS, array_map(Captcha::enabled(...), Captcha::FORMS)),
                'loginAttempts' => FortifyServiceProvider::LOGIN_ATTEMPTS_PER_MINUTE,
            ],
        });
    }

    /**
     * Save the fields the submitted form holds, then go back to its page.
     */
    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $this->replaceFile($request, SiteSetting::LOGO, 'logo', 'remove_logo');
        $this->replaceFile($request, SiteSetting::HERO_IMAGE, 'hero_image', 'remove_hero_image');
        $this->replaceFile($request, SiteSetting::SEO_IMAGE, 'seo_image', 'remove_seo_image');

        if ($request->has(SiteSetting::PRIMARY_COLOR)) {
            $color = $request->string(SiteSetting::PRIMARY_COLOR)->lower()->toString();
            SiteSetting::put(SiteSetting::PRIMARY_COLOR, $color === '' ? null : $color);
        }

        $textKeys = [
            ...array_keys(SiteSetting::BANNER_DEFAULTS),
            ...SiteSetting::CONTACT_FIELDS,
            ...SiteSetting::SEO_FIELDS,
            ...array_map(fn (string $network) => 'social_'.$network, SiteSetting::SOCIAL_NETWORKS),
        ];

        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                $text = trim($request->string($key)->toString());
                SiteSetting::put($key, $text === '' ? null : $text);
            }
        }

        foreach (Captcha::FORMS as $form) {
            if ($request->has('captcha.'.$form)) {
                // Stored only while switched off, so a fresh install asks for the code.
                SiteSetting::put(Captcha::settingKey($form), $request->boolean('captcha.'.$form) ? null : '0');
            }
        }

        if ($request->has('footer')) {
            SiteSetting::put(SiteSetting::FOOTER, json_encode($this->footer($request), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site settings updated.')]);

        return to_route('admin.settings.edit', ['section' => $request->string('section', 'identitas')->toString()]);
    }

    /**
     * The submitted footer, trimmed, with an empty text falling back to its default.
     *
     * @return array<string, mixed>
     */
    private function footer(UpdateSiteSettingsRequest $request): array
    {
        $defaults = SiteSetting::FOOTER_DEFAULTS;
        $text = fn (string $key, string $default): string => trim($request->string('footer.'.$key)->toString()) ?: $default;

        return [
            'categories' => [
                'show' => $request->boolean('footer.categories.show'),
                'title' => $text('categories.title', $defaults['categories']['title']),
                'limit' => $request->integer('footer.categories.limit', $defaults['categories']['limit']),
                'see_all_label' => $text('categories.see_all_label', $defaults['categories']['see_all_label']),
            ],
            'explore' => [
                'show' => $request->boolean('footer.explore.show'),
                'title' => $text('explore.title', $defaults['explore']['title']),
                'links' => array_values(array_map(
                    fn (array $link): array => ['label' => trim($link['label']), 'url' => trim($link['url'])],
                    $request->array('footer.explore.links'),
                )),
            ],
            'account' => [
                'show' => $request->boolean('footer.account.show'),
                'title' => $text('account.title', $defaults['account']['title']),
            ],
            'statistics' => [
                'show' => $request->boolean('footer.statistics.show'),
                'title' => $text('statistics.title', $defaults['statistics']['title']),
                'online' => $request->boolean('footer.statistics.online'),
            ],
            'copyright' => $text('copyright', $defaults['copyright']),
            'whatsapp' => [
                'show' => $request->boolean('footer.whatsapp.show'),
                'number' => trim($request->string('footer.whatsapp.number')->toString()),
                'message' => trim($request->string('footer.whatsapp.message')->toString()),
            ],
            'back_to_top' => [
                'show' => $request->boolean('footer.back_to_top.show'),
                'label' => $text('back_to_top.label', $defaults['back_to_top']['label']),
            ],
            'floating_position' => $request->string('footer.floating_position')->toString(),
        ];
    }

    /**
     * The stored value (null when unset) of each setting.
     *
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    private function values(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = SiteSetting::get($key);
        }

        return $values;
    }

    /**
     * Store a newly uploaded file under the setting, or clear it, deleting the old file.
     */
    private function replaceFile(UpdateSiteSettingsRequest $request, string $setting, string $input, string $removeFlag): void
    {
        if (! $request->hasFile($input) && ! $request->boolean($removeFlag)) {
            return;
        }

        $current = SiteSetting::get($setting);

        if ($current !== null) {
            Storage::disk(SiteSetting::LOGO_DISK)->delete($current);
        }

        SiteSetting::put(
            $setting,
            $request->hasFile($input)
                ? ($request->file($input)->store(SiteSetting::LOGO_DIRECTORY, SiteSetting::LOGO_DISK) ?: null)
                : null,
        );
    }
}
