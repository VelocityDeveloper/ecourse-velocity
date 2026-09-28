<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\SiteSetting;
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
        });
    }

    /**
     * Save the fields the submitted form holds, then go back to its page.
     */
    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $this->replaceFile($request, SiteSetting::LOGO, 'logo', 'remove_logo');
        $this->replaceFile($request, SiteSetting::HERO_IMAGE, 'hero_image', 'remove_hero_image');

        if ($request->has(SiteSetting::PRIMARY_COLOR)) {
            $color = $request->string(SiteSetting::PRIMARY_COLOR)->lower()->toString();
            SiteSetting::put(SiteSetting::PRIMARY_COLOR, $color === '' ? null : $color);
        }

        $textKeys = [
            ...array_keys(SiteSetting::BANNER_DEFAULTS),
            ...SiteSetting::CONTACT_FIELDS,
            ...array_map(fn (string $network) => 'social_'.$network, SiteSetting::SOCIAL_NETWORKS),
        ];

        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                $text = trim($request->string($key)->toString());
                SiteSetting::put($key, $text === '' ? null : $text);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site settings updated.')]);

        return to_route('admin.settings.edit', ['section' => $request->string('section', 'identitas')->toString()]);
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
