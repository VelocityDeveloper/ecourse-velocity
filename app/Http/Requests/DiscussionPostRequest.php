<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DiscussionPostRequest extends FormRequest
{
    /**
     * The longest question or reply that may be posted.
     */
    public const int MAX_LENGTH = 5000;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:'.self::MAX_LENGTH],
        ];
    }
}
