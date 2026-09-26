<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * SVG is left out on purpose: an uploaded SVG is served from the app's own
     * origin and could carry script.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_logo' => ['sometimes', 'boolean'],
            'primary_color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hero_badge' => ['sometimes', 'nullable', 'string', 'max:60'],
            'hero_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'hero_highlight' => ['sometimes', 'nullable', 'string', 'max:80'],
            'hero_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'cta_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cta_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'hero_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:max_width=4000,max_height=4000'],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'surface_color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'contact_address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:120'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'contact_hours' => ['sometimes', 'nullable', 'string', 'max:100'],
            'social_instagram' => ['sometimes', 'nullable', 'url:https', 'max:255'],
            'social_tiktok' => ['sometimes', 'nullable', 'url:https', 'max:255'],
            'social_youtube' => ['sometimes', 'nullable', 'url:https', 'max:255'],
            'social_facebook' => ['sometimes', 'nullable', 'url:https', 'max:255'],
            'social_linkedin' => ['sometimes', 'nullable', 'url:https', 'max:255'],
            'section' => ['sometimes', 'string', 'in:identitas,warna,hero,kontak'],
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
            'primary_color.regex' => __('Choose a colour in #rrggbb format.'),
            'logo.max' => __('The image may not be larger than :size MB.', ['size' => 2]),
            'logo.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 2]),
            'hero_image.max' => __('The image may not be larger than :size MB.', ['size' => 3]),
            'hero_image.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 3]),
            'surface_color.regex' => __('Choose a colour in #rrggbb format.'),
            'contact_phone.regex' => __('Use digits, spaces, +, - or brackets only.'),
            'social_*.url' => __('Enter a full link starting with https://.'),
        ];
    }
}
