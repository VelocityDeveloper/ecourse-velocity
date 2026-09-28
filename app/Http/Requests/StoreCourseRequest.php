<?php

namespace App\Http\Requests;

use App\Concerns\CourseValidationRules;
use App\Models\Course;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    use CourseValidationRules;

    /**
     * Fill in the values a course always starts with when they were omitted.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug')
                ? Slug::from($this->string('slug')->toString(), '')
                : $this->uniqueCourseSlug($this->string('title')->toString()),
            'status' => $this->filled('status')
                ? $this->string('status')->toString()
                : Course::STATUS_DRAFT,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->courseRules($this->actor());
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->courseMessages();
    }

    /**
     * Get the attributes used to create the course.
     *
     * An instructor always owns the courses they create; only an admin may
     * hand a course to somebody else.
     *
     * @return array<string, mixed>
     */
    public function courseAttributes(): array
    {
        $actor = $this->actor();

        return [
            ...$this->safe()->except(['thumbnail', 'instructor_id']),
            'instructor_id' => $actor->isAdmin()
                ? (int) $this->validated('instructor_id')
                : $actor->id,
        ];
    }

    /**
     * Get the authenticated user making the request.
     */
    private function actor(): User
    {
        $actor = $this->user();

        assert($actor instanceof User);

        return $actor;
    }
}
