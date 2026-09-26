<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $lesson_id
 * @property string $name
 * @property string $path
 * @property string|null $mime_type
 * @property int $size
 * @property-read string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['url'])]
#[Fillable(['lesson_id', 'name', 'path', 'mime_type', 'size'])]
class LessonAttachment extends Model
{
    /**
     * The disk that lesson attachments are stored on.
     */
    public const string DISK = 'public';

    /**
     * The directory that lesson attachments are stored in.
     */
    public const string DIRECTORY = 'lesson-attachments';

    /**
     * The largest attachment accepted, in kilobytes.
     */
    public const int MAX_KILOBYTES = 10240;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_id' => 'integer',
            'size' => 'integer',
        ];
    }

    /**
     * Get the lesson this attachment belongs to.
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the public URL students download the file from.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(self::DISK)->url($this->path));
    }

    /**
     * Remove the stored file whenever the attachment record goes away.
     */
    protected static function booted(): void
    {
        static::deleting(function (LessonAttachment $attachment): void {
            Storage::disk(self::DISK)->delete($attachment->path);
        });
    }
}
