<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The directions live on the HasPosition trait, which Section and Lesson
     * both use; a trait constant can only be read through a using class.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', 'string', Rule::in(Section::MOVE_DIRECTIONS)],
        ];
    }
}
