<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts')->ignore($post)],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:250000'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'media_id' => ['required', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
            'inline_media_ids' => ['nullable', 'string', 'max:2000'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'intent' => ['required', Rule::in(['preserve', 'draft', 'review', 'publish', 'schedule'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? mb_strtolower(trim((string) $this->input('slug'))) : null,
            'source_name' => $this->filled('source_name') ? trim((string) $this->input('source_name')) : null,
            'source_url' => $this->filled('source_url') ? trim((string) $this->input('source_url')) : null,
            'seo_title' => $this->filled('seo_title') ? trim((string) $this->input('seo_title')) : null,
            'seo_description' => $this->filled('seo_description') ? trim((string) $this->input('seo_description')) : null,
        ]);
    }
}
