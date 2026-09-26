<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The image is required when a banner is created and optional when one is edited.
     * Links may be a full https URL or a path on this site ("/catalog").
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'image' => [$this->route('banner') === null ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:max_width=4000,max_height=4000'],
            'link_url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#'],
            'is_active' => ['sometimes', 'boolean'],
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
            'link_url.regex' => __('The link must start with https:// or /.'),
            'image.max' => __('The image may not be larger than :size MB.', ['size' => 3]),
            'image.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 3]),
        ];
    }
}
