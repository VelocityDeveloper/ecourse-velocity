<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEnrollmentRequest extends FormRequest
{
    /**
     * Only staff may add students to a course by hand.
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Enrollment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists(Course::class, 'id')],
            'user_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where('role', User::ROLE_STUDENT),
            ],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => __('Only students can be enrolled in a course.'),
        ];
    }

    /**
     * Refuse courses the user does not manage, archived courses and duplicate enrollments.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['course_id', 'user_id'])) {
                    return;
                }

                $course = $this->course();

                if (! Gate::allows('update', $course)) {
                    $validator->errors()->add('course_id', 'You can only enroll students in courses you manage.');

                    return;
                }

                if ($course->status === Course::STATUS_ARCHIVED) {
                    $validator->errors()->add('course_id', 'Students cannot be enrolled in an archived course.');

                    return;
                }

                if ($course->enrollments()->active()->where('user_id', $this->integer('user_id'))->exists()) {
                    $validator->errors()->add('user_id', 'This student is already enrolled in the course.');
                }
            },
        ];
    }

    /**
     * Get the course the student is being enrolled in.
     */
    public function course(): Course
    {
        return Course::query()->findOrFail($this->integer('course_id'));
    }

    /**
     * Get the student being enrolled.
     */
    public function student(): User
    {
        return User::query()->findOrFail($this->integer('user_id'));
    }
}
