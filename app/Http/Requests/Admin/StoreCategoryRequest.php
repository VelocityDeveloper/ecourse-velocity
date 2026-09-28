<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\Slug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Derive the slug from the name when it was left blank.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Slug::from(
                $this->filled('slug') ? $this->string('slug')->toString() : $this->string('name')->toString(),
                '',
            ),
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^'.Slug::PATTERN.'$/', Rule::notIn(Slug::RESERVED), Rule::unique(Category::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:max_width=4000,max_height=4000'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.not_in' => __('This slug is already used by another page of the site.'),
            'image.max' => __('The image may not be larger than :size MB.', ['size' => 3]),
            'image.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 3]),
        ];
    }
}
