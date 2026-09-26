<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
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
 * An alumni story for the homepage, managed at Admin → Pengaturan Situs → Testimoni.
 *
 * @property int $id
 * @property string $name
 * @property string|null $subtitle
 * @property string $quote
 * @property int $rating
 * @property string|null $photo_path
 * @property bool $mask_name
 * @property bool $is_active
 * @property int $sort_order
 * @property-read string|null $photo_url
 * @property-read string $display_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['photo_url', 'display_name'])]
#[Fillable(['name', 'subtitle', 'quote', 'rating', 'photo_path', 'mask_name', 'is_active', 'sort_order'])]
#[Hidden(['photo_path'])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    /**
     * The disk and directory testimonial photos are stored in.
     */
    public const string PHOTO_DISK = 'public';

    public const string PHOTO_DIRECTORY = 'testimonials';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'mask_name' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the public URL of the photo, if one was uploaded.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path === null
            ? null
            : Storage::disk(self::PHOTO_DISK)->url($this->photo_path));
    }

    /**
     * The name as the homepage shows it: with "Samarkan nama" on, each word keeps
     * its first and last letter ("Nadia Putri" → "N***a P***i").
     *
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->mask_name ? self::mask($this->name) : $this->name);
    }

    /**
     * Hide the middle letters of every word in a name.
     */
    public static function mask(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->map(function (string $word): string {
                $length = mb_strlen($word);

                return $length <= 2
                    ? $word
                    : mb_substr($word, 0, 1).str_repeat('*', $length - 2).mb_substr($word, -1);
            })
            ->implode(' ');
    }

    /**
     * Order testimonials the way the homepage shows them.
     *
     * @param  Builder<Testimonial>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Limit the query to testimonials shown on the homepage.
     *
     * @param  Builder<Testimonial>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
