<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property-read string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['image_url'])]
#[Fillable(['name', 'slug', 'description', 'image_path'])]
#[Hidden(['image_path'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * The disk and directory category images (learning-path card backgrounds) are stored in.
     */
    public const string IMAGE_DISK = 'public';

    public const string IMAGE_DIRECTORY = 'categories';

    /**
     * Get the public URL of the category image, if one was uploaded.
     *
     * Reads the raw attribute so partially selected categories still serialize.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->attributes['image_path'] ?? null;

            return is_string($path) ? Storage::disk(self::IMAGE_DISK)->url($path) : null;
        });
    }

    /**
     * Get the courses that belong to this category.
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
