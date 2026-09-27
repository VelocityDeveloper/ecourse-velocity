<?php

namespace App\Http\Requests;

use App\Models\QuizQuestion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitQuizAttemptRequest extends FormRequest
{
    /**
     * Only the student who owns the open attempt may hand it in.
     */
    public function authorize(): bool
    {
        return Gate::allows('submit', $this->route('attempt'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Answers are keyed by question id; each value lists the chosen option ids,
     * or holds the typed text of a short answer question. Unknown ids are
     * ignored by the grader rather than rejected here.
     *
     * @return array<string, array<int, ValidationRule|Closure|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => [function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === null) {
                    return;
                }

                if (is_string($value)) {
                    if (mb_strlen($value) > QuizQuestion::MAX_SHORT_ANSWER_LENGTH) {
                        $fail(__('validation.max.string', ['attribute' => __('answer'), 'max' => QuizQuestion::MAX_SHORT_ANSWER_LENGTH]));
                    }

                    return;
                }

                if (! is_array($value) || array_filter($value, fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) === false) !== []) {
                    $fail(__('validation.array', ['attribute' => __('answer')]));
                }
            }],
        ];
    }
}
