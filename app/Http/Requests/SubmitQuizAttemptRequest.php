<?php

namespace App\Http\Requests;

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
     * Answers are keyed by question id; each value lists the chosen option ids.
     * Unknown ids are ignored by the grader rather than rejected here.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer'],
        ];
    }
}
