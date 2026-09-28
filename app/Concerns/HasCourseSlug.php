<?php

namespace App\Concerns;

use App\Models\Section;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;

/**
 * A lesson or quiz slug, unique among its kind within one course, so it can
 * appear in /belajar/{course}/materi/{lesson} and /belajar/{course}/kuis/{quiz}.
 *
 * The slug is made from the title once and then kept, so links stay valid
 * when the title is edited. It is only renewed when the record moves to a
 * course where the slug is already taken, for example when curriculum is reused.
 *
 * @property int $section_id
 * @property string $title
 * @property string $slug
 *
 * @phpstan-require-extends Model
 */
trait HasCourseSlug
{
    /**
     * The slug used when a title has no letters or digits.
     */
    abstract protected function slugFallback(): string;

    protected static function bootHasCourseSlug(): void
    {
        static::saving(function (Model $record): void {
            /** @var static $record */
            $slug = (string) $record->getAttribute('slug');

            if ($slug === '' || ($record->isDirty('section_id') && $record->slugTakenInCourse($slug))) {
                $record->setAttribute('slug', Slug::unique(
                    $record->title,
                    $record->slugFallback(),
                    fn (string $candidate): bool => $record->slugTakenInCourse($candidate),
                ));
            }
        });
    }

    /**
     * Determine whether another record of this kind in the same course already uses the slug.
     */
    public function slugTakenInCourse(string $slug): bool
    {
        $courseId = Section::query()->whereKey($this->section_id)->value('course_id');

        return static::query()
            ->where('slug', $slug)
            ->whereIn('section_id', Section::query()->select('id')->where('course_id', $courseId))
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }
}
