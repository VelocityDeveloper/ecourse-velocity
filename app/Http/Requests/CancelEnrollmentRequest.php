<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CancelEnrollmentRequest extends FormRequest
{
    /**
     * Refuse anyone who may not cancel this enrollment before validating the reason.
     */
    public function authorize(): bool
    {
        return Gate::allows('cancel', $this->route('enrollment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
