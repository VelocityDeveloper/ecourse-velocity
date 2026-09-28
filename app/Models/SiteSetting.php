<?php

namespace App\Models;

use App\Support\BrandPalette;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * A site-wide setting an administrator can change, stored as a key/value row.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class SiteSetting extends Model
{
    /**
     * The key holding the storage path of the uploaded site logo.
     */
    public const string LOGO = 'logo_path';

    /**
     * The disk the site logo is stored on.
     */
    public const string LOGO_DISK = 'public';

    /**
     * The directory the site logo is stored in.
     */
    public const string LOGO_DIRECTORY = 'branding';

    /**
     * The key holding the admin's main colour as "#rrggbb".
     */
    public const string PRIMARY_COLOR = 'primary_color';

    /**
     * Where the homepage testimonials come from; see App\Support\HomeTestimonials.
     */
    public const string TESTIMONIAL_SOURCE = 'testimonial_source';

    /**
     * How many testimonials the homepage shows; see App\Support\HomeTestimonials.
     */
    public const string TESTIMONIAL_LIMIT = 'testimonial_limit';

    /**
     * The contact and footer fields an admin may fill in. Empty ones are hidden on the site.
     *
     * @var list<string>
     */
    public const array CONTACT_FIELDS = [
        'site_description',
        'contact_address',
        'contact_email',
        'contact_phone',
        'contact_hours',
    ];

    /**
     * The social networks shown as icons in the footer, in display order.
     *
     * @var list<string>
     */
    public const array SOCIAL_NETWORKS = ['instagram', 'tiktok', 'youtube', 'facebook', 'linkedin'];

    /**
     * The footer description shown until an admin writes one.
     */
    public const string DEFAULT_SITE_DESCRIPTION = 'Platform belajar online dengan materi terstruktur, kuis, dan instruktur praktisi di bidangnya.';

    /**
     * The key holding the storage path of the homepage hero image.
     */
    public const string HERO_IMAGE = 'hero_image_path';

    /**
     * The homepage banner texts an admin may change, with the text shown when
     * a field is left empty.
     *
     * @var array<string, string>
     */
    public const array BANNER_DEFAULTS = [
        'hero_badge' => 'Belajar dari instruktur praktisi',
        'hero_title' => 'Bangun keterampilan nyata,',
        'hero_highlight' => 'selangkah demi selangkah.',
        'hero_description' => 'Materi video dan artikel yang terstruktur, kuis yang menguji pemahaman Anda, serta instruktur yang mengajarkan apa yang mereka praktikkan.',
        'cta_title' => 'Mulai belajar hari ini',
        'cta_description' => 'Buat akun gratis, daftar ke sebuah kursus, dan ikuti materi pertama Anda dalam hitungan menit.',
    ];

    /**
     * The cache key for every setting, read on each Inertia request.
     */
    private const string CACHE_KEY = 'site_settings';

    /**
     * Get a setting's value, or the default when it has not been set.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::values()[$key] ?? $default;
    }

    /**
     * Store a setting's value; null removes it.
     */
    public static function put(string $key, ?string $value): void
    {
        if ($value === null) {
            self::query()->where('key', $key)->delete();
        } else {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get the public URL of the uploaded logo, or null when the default logo is used.
     */
    public static function logoUrl(): ?string
    {
        $path = self::get(self::LOGO);

        return $path === null ? null : Storage::disk(self::LOGO_DISK)->url($path);
    }

    /**
     * The CSS overriding the brand tokens, or null while the built-in colours are used.
     */
    public static function paletteCss(): ?string
    {
        $color = self::get(self::PRIMARY_COLOR);

        return $color === null ? null : BrandPalette::fromHex($color)->toCss();
    }

    /**
     * The public identity and contact details every public page's footer shows.
     *
     * @return array{description: string, address: string|null, email: string|null, phone: string|null, whatsapp_url: string|null, hours: string|null, socials: list<array{network: string, url: string}>}
     */
    public static function publicSite(): array
    {
        $phone = self::get('contact_phone');
        $digits = $phone === null ? '' : preg_replace('/\D+/', '', $phone) ?? '';

        // WhatsApp wants the number with its country code: 0812… becomes 62812….
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $socials = [];

        foreach (self::SOCIAL_NETWORKS as $network) {
            $url = self::get('social_'.$network);

            if ($url !== null) {
                $socials[] = ['network' => $network, 'url' => $url];
            }
        }

        return [
            'description' => self::get('site_description') ?? self::DEFAULT_SITE_DESCRIPTION,
            'address' => self::get('contact_address'),
            'email' => self::get('contact_email'),
            'phone' => $phone,
            'whatsapp_url' => $digits === '' ? null : 'https://wa.me/'.$digits,
            'hours' => self::get('contact_hours'),
            'socials' => $socials,
        ];
    }

    /**
     * The homepage banner: each text falls back to its default, plus the hero image URL.
     *
     * @return array<string, string|null>
     */
    public static function banner(): array
    {
        $banner = [];

        foreach (self::BANNER_DEFAULTS as $key => $default) {
            $banner[$key] = self::get($key) ?? $default;
        }

        $image = self::get(self::HERO_IMAGE);
        $banner['hero_image_url'] = $image === null ? null : Storage::disk(self::LOGO_DISK)->url($image);

        return $banner;
    }

    /**
     * Every setting as a key => value map, cached until one changes.
     *
     * @return array<string, string|null>
     */
    private static function values(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => self::query()->pluck('value', 'key')->all(),
        );
    }
}
