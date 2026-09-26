<?php

namespace App\Http\Requests;

use App\Models\LessonNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonNoteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * An empty body clears the note.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:'.LessonNote::MAX_LENGTH],
        ];
    }
}
