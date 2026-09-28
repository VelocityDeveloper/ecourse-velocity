<?php

namespace App\Concerns;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

trait CourseValidationRules
{
    /**
     * Get the validation rules shared by course creation and updates.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function courseRules(User $actor, ?Course $course = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^'.Slug::PATTERN.'$/',
                $course === null
                    ? Rule::unique(Course::class)
                    : Rule::unique(Course::class)->ignore($course->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'instructor_id' => $this->instructorRules($actor),
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'level' => ['required', 'string', Rule::in(Course::LEVELS)],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'status' => $this->statusRules($actor, $course),
        ];
    }

    /**
     * Get the validation rules used to validate the course owner.
     *
     * Only an admin may assign a course to another instructor; for everyone
     * else the field is ignored and the actor owns the course instead.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function instructorRules(User $actor): array
    {
        if (! $actor->isAdmin()) {
            return ['nullable'];
        }

        return [
            'required',
            'integer',
            Rule::exists(User::class, 'id')->where('role', User::ROLE_INSTRUCTOR),
        ];
    }

    /**
     * Get the validation rules used to validate a course status.
     *
     * Admins may select any status. Instructors may only keep a course as a
     * draft or submit it for approval, or leave the status it already has, so
     * they can still edit a course an admin has published.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function statusRules(User $actor, ?Course $course = null): array
    {
        return [
            'required',
            'string',
            Rule::in(Course::statusesFor($actor, $course)),
        ];
    }

    /**
     * Build a slug that is unique among courses, appending a counter when needed.
     */
    protected function uniqueCourseSlug(string $title, ?Course $course = null): string
    {
        return Slug::unique($title, 'kursus-baru', fn (string $slug): bool => $this->courseSlugExists($slug, $course));
    }

    /**
     * Determine whether a slug is already taken by another course.
     */
    protected function courseSlugExists(string $slug, ?Course $course): bool
    {
        return Course::query()
            ->where('slug', $slug)
            ->when($course !== null, fn (Builder $query): Builder => $query->whereKeyNot($course?->id))
            ->exists();
    }

    /**
     * Get the validation messages shared by course requests.
     *
     * @return array<string, string>
     */
    protected function courseMessages(): array
    {
        return [
            'status.in' => __('Only an administrator can publish or archive a course.'),
            'instructor_id.exists' => __('The selected owner is not an instructor.'),
        ];
    }
}
