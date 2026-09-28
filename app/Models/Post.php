<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A blog article, written by an admin and shown on the public /blog pages.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $content
 * @property string|null $cover_path
 * @property string $status
 * @property CarbonImmutable|null $published_at
 * @property-read string|null $cover_url
 * @property-read string $summary
 * @property-read int $reading_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PUBLISHED = 'published';

    /**
     * @var list<string>
     */
    public const array STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED];

    /**
     * The disk and directory cover images are stored in.
     */
    public const string COVER_DISK = 'public';

    public const string COVER_DIRECTORY = 'posts';

    /**
     * How many characters of the article make its summary when no excerpt was written.
     */
    private const int SUMMARY_LENGTH = 160;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Only the articles visitors may read: published, and not scheduled for later.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Get the author of the article.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Determine whether visitors can read the article.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Get the public URL of the cover image, if one was uploaded.
     *
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->attributes['cover_path'] ?? null;

            return is_string($path) ? Storage::disk(self::COVER_DISK)->url($path) : null;
        });
    }

    /**
     * Get the excerpt, or the start of the article when none was written.
     *
     * @return Attribute<string, never>
     */
    protected function summary(): Attribute
    {
        return Attribute::get(function (): string {
            if (filled($this->excerpt)) {
                return (string) $this->excerpt;
            }

            return Str::limit($this->plainText(), self::SUMMARY_LENGTH);
        });
    }

    /**
     * Get the estimated reading time, at 200 words a minute.
     *
     * @return Attribute<positive-int, never>
     */
    protected function readingMinutes(): Attribute
    {
        return Attribute::get(fn (): int => max(1, (int) ceil(str_word_count($this->plainText()) / 200)));
    }

    /**
     * The article's text without its markup.
     */
    private function plainText(): string
    {
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', (string) $this->content)));

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
