<?php

namespace App\Http\Requests;

use App\Models\QuizQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuizQuestionRequest extends FormRequest
{
    /**
     * Pin a true or false question to its two fixed options, keeping the chosen answer,
     * and mark every accepted answer of a short answer question as correct.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('answer_mode') === QuizQuestion::MODE_SHORT_ANSWER) {
            $this->merge([
                'options' => array_map(fn (mixed $option): array => [
                    'text' => is_array($option) ? ($option['text'] ?? null) : null,
                    'is_correct' => true,
                ], array_values($this->array('options'))),
            ]);

            return;
        }

        if ($this->input('answer_mode') !== QuizQuestion::MODE_TRUE_FALSE) {
            return;
        }

        $submitted = array_values($this->array('options'));

        $this->merge([
            'options' => array_map(fn (string $text, int $index): array => [
                'text' => $text,
                'is_correct' => is_array($submitted[$index] ?? null)
                    && filter_var($submitted[$index]['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ], QuizQuestion::TRUE_FALSE_OPTIONS, array_keys(QuizQuestion::TRUE_FALSE_OPTIONS)),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:1000'],
            'answer_mode' => ['required', 'string', Rule::in(QuizQuestion::ANSWER_MODES)],
            'points' => ['required_unless:answer_mode,'.QuizQuestion::MODE_MULTIPLE, 'integer', 'min:0', 'max:10000'],
            'options' => ['required', 'array', 'min:'.$this->minimumOptions(), 'max:'.QuizQuestion::MAX_OPTIONS],
            'options.*.text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
            'scores' => ['required_if:answer_mode,'.QuizQuestion::MODE_MULTIPLE, 'array', 'max:'.QuizQuestion::MAX_OPTIONS],
            'scores.*' => ['integer', 'min:0', 'max:10000'],
        ];
    }

    /**
     * Check the answer key against the chosen answer mode.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['options', 'options.*', 'answer_mode'])) {
                    return;
                }

                $correct = $this->correctOptionCount();

                if ($this->input('answer_mode') === QuizQuestion::MODE_SHORT_ANSWER) {
                    foreach (array_values($this->array('options')) as $index => $option) {
                        if (QuizQuestion::normalizeShortAnswer((string) ($option['text'] ?? '')) === '') {
                            $validator->errors()->add(
                                "options.{$index}.text",
                                __('An accepted answer needs at least one letter or number.'),
                            );
                        }
                    }

                    return;
                }

                if ($this->input('answer_mode') === QuizQuestion::MODE_SINGLE) {
                    if ($correct !== 1) {
                        $validator->errors()->add(
                            'options',
                            'A single answer question needs exactly one correct option.',
                        );
                    }

                    return;
                }

                if ($this->input('answer_mode') === QuizQuestion::MODE_TRUE_FALSE) {
                    if ($correct !== 1) {
                        $validator->errors()->add(
                            'options',
                            'Choose whether the statement is true or false.',
                        );
                    }

                    return;
                }

                if ($correct < 2) {
                    $validator->errors()->add(
                        'options',
                        'A multiple answer question needs at least two correct options.',
                    );

                    return;
                }

                if (count($this->array('scores')) !== $correct) {
                    $validator->errors()->add(
                        'scores',
                        "Set one score for each correct answer count, from 1 up to {$correct}.",
                    );
                }
            },
        ];
    }

    /**
     * A short answer question needs one accepted answer; the others need a choice.
     */
    private function minimumOptions(): int
    {
        return $this->input('answer_mode') === QuizQuestion::MODE_SHORT_ANSWER ? 1 : 2;
    }

    /**
     * Count how many submitted options are marked as correct.
     */
    public function correctOptionCount(): int
    {
        $correct = 0;

        foreach ($this->array('options') as $option) {
            if (is_array($option) && filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $correct++;
            }
        }

        return $correct;
    }
}
