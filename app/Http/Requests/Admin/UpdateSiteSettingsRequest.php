<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\SiteSettingController;
use App\Models\SiteSetting;
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
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:70'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'seo_keywords' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_google_verification' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'seo_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000'],
            'remove_seo_image' => ['sometimes', 'boolean'],
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
            'footer' => ['sometimes', 'array'],
            'footer.categories.show' => ['required_with:footer', 'boolean'],
            'footer.categories.title' => ['nullable', 'string', 'max:40'],
            'footer.categories.limit' => ['required_with:footer', 'integer', 'between:1,12'],
            'footer.categories.see_all_label' => ['nullable', 'string', 'max:40'],
            'footer.explore.show' => ['required_with:footer', 'boolean'],
            'footer.explore.title' => ['nullable', 'string', 'max:40'],
            'footer.explore.links' => ['present_with:footer', 'array', 'max:'.SiteSetting::FOOTER_MAX_LINKS],
            'footer.explore.links.*.label' => ['required', 'string', 'max:40'],
            // A path inside the site (/kursus, /#categories) or a full https:// address.
            'footer.explore.links.*.url' => ['required', 'string', 'max:255', 'regex:~^(/(?!/)\S*|https?://\S+)$~'],
            'footer.account.show' => ['required_with:footer', 'boolean'],
            'footer.account.title' => ['nullable', 'string', 'max:40'],
            'footer.statistics.show' => ['required_with:footer', 'boolean'],
            'footer.statistics.title' => ['nullable', 'string', 'max:40'],
            'footer.statistics.online' => ['required_with:footer', 'boolean'],
            'footer.copyright' => ['nullable', 'string', 'max:150'],
            'footer.whatsapp.show' => ['required_with:footer', 'boolean'],
            'footer.whatsapp.number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'footer.whatsapp.message' => ['nullable', 'string', 'max:200'],
            'footer.floating_position' => ['required_with:footer', 'in:'.implode(',', SiteSetting::FLOATING_POSITIONS)],
            'footer.back_to_top.show' => ['required_with:footer', 'boolean'],
            'footer.back_to_top.label' => ['nullable', 'string', 'max:40'],
            'captcha' => ['sometimes', 'array'],
            'captcha.login' => ['sometimes', 'boolean'],
            'captcha.register' => ['sometimes', 'boolean'],
            'section' => ['sometimes', 'string', 'in:'.implode(',', array_keys(SiteSettingController::SECTIONS))],
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
            'contact_phone.regex' => __('Use digits, spaces, +, - or brackets only.'),
            'footer.whatsapp.number.regex' => __('Use digits, spaces, +, - or brackets only.'),
            'social_*.url' => __('Enter a full link starting with https://.'),
            'footer.explore.links.*.url.regex' => __('Use a path on this site starting with / or a full link starting with https://.'),
            'seo_image.max' => __('The image may not be larger than :size MB.', ['size' => 3]),
            'seo_image.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 3]),
            'seo_image.dimensions' => __('Use an image of at least 200 × 200 pixels.'),
            'seo_google_verification.regex' => __('Paste the code from Google Search Console, or the whole meta tag.'),
        ];
    }

    /**
     * Accept the whole verification tag Google shows, keeping only its code.
     */
    protected function prepareForValidation(): void
    {
        $verification = $this->input('seo_google_verification');

        if (is_string($verification) && preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $verification, $match) === 1) {
            $this->merge(['seo_google_verification' => $match[1]]);
        }
    }
}
