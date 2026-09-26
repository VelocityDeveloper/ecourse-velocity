<?php

namespace App\Http\Requests;

use App\Models\CourseReview;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CourseReviewRequest extends FormRequest
{
    /**
     * Only enrolled students may review the course.
     */
    public function authorize(): bool
    {
        return Gate::allows('review', $this->route('course'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:'.CourseReview::MIN_RATING, 'max:'.CourseReview::MAX_RATING],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
