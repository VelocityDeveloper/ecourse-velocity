<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A promo slide in the homepage banner slider, managed at Admin → Pengaturan Situs → Banner Promo.
 *
 * @property int $id
 * @property string $title
 * @property string $image_path
 * @property string|null $link_url
 * @property bool $is_active
 * @property int $sort_order
 * @property-read string $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['image_url'])]
#[Fillable(['title', 'image_path', 'link_url', 'is_active', 'sort_order'])]
#[Hidden(['image_path'])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    /**
     * The disk and directory banner images are stored in.
     */
    public const string IMAGE_DISK = 'public';

    public const string IMAGE_DIRECTORY = 'banners';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the public URL of the banner image.
     *
     * @return Attribute<string, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(self::IMAGE_DISK)->url($this->image_path));
    }

    /**
     * Order banners the way the slider shows them.
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Limit the query to banners shown on the homepage.
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
