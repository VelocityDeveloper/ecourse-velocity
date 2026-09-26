<?php

namespace App\Http\Requests;

use App\Concerns\CourseValidationRules;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    use CourseValidationRules;

    /**
     * Fall back to the current slug when the field was cleared.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug')
                ? $this->string('slug')->toString()
                : $this->uniqueCourseSlug($this->string('title')->toString(), $this->course()),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->courseRules($this->actor(), $this->course());
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
     * Get the attributes used to update the course.
     *
     * A non-admin can never move a course to another instructor, so the owner
     * is left untouched for them.
     *
     * @return array<string, mixed>
     */
    public function courseAttributes(): array
    {
        $attributes = $this->safe()->except(['thumbnail', 'instructor_id']);

        if ($this->actor()->isAdmin()) {
            $attributes['instructor_id'] = (int) $this->validated('instructor_id');
        }

        return $attributes;
    }

    /**
     * Get the course being updated.
     */
    private function course(): Course
    {
        $course = $this->route('course');

        assert($course instanceof Course);

        return $course;
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
