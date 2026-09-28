<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use App\Support\Slug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    /**
     * Derive the slug from the title when it was left blank.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Slug::from(
                $this->filled('slug') ? $this->string('slug')->toString() : $this->string('title')->toString(),
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
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^'.Slug::PATTERN.'$/',
                Rule::unique(Post::class)->ignore($post instanceof Post ? $post->id : null),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::in(Post::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072', 'dimensions:max_width=4000,max_height=4000'],
            'remove_cover' => ['sometimes', 'boolean'],
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
            'cover.max' => __('The image may not be larger than :size MB.', ['size' => 3]),
            'cover.uploaded' => __('The image could not be uploaded. Make sure it is no larger than :size MB.', ['size' => 3]),
        ];
    }
}
