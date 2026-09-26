<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class MoveToSectionRequest extends FormRequest
{
    /**
     * Refuse anyone who may not edit the lesson or quiz being moved.
     *
     * This runs before validation, so a user without access gets a 403 rather
     * than a message about the destination.
     */
    public function authorize(): bool
    {
        $subject = $this->route('lesson') ?? $this->route('quiz');

        if (! $subject instanceof Lesson && ! $subject instanceof Quiz) {
            return false;
        }

        return Gate::allows('update', $subject->section->course);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ];
    }

    /**
     * Refuse a destination the user is not allowed to write to.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('section_id')) {
                    return;
                }

                if (! Gate::allows('update', $this->destination()->course)) {
                    $validator->errors()->add(
                        'section_id',
                        'You cannot move anything into that section.',
                    );
                }
            },
        ];
    }

    /**
     * Get the section the item is being moved into.
     */
    public function destination(): Section
    {
        return Section::query()->findOrFail($this->integer('section_id'));
    }

    /**
     * Get the authenticated user making the request.
     */
    public function actor(): User
    {
        $actor = $this->user();

        assert($actor instanceof User);

        return $actor;
    }
}
