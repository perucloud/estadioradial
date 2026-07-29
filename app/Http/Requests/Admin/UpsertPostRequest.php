<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
            'tag_names' => ['nullable', 'string', 'max:1500'],
            'inline_media_ids' => ['nullable', 'string', 'max:2000'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'scheduled_for' => [
                'exclude_unless:intent,schedule',
                'required_if:intent,schedule',
                'date',
                'after:now',
            ],
            'intent' => ['required', Rule::in(['preserve', 'draft', 'review', 'publish', 'schedule'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Escribe el título de la noticia.',
            'excerpt.required' => 'Escribe un resumen para la noticia.',
            'body.required' => 'Escribe el contenido completo de la noticia.',
            'category_id.required' => 'Selecciona una categoría.',
            'category_id.exists' => 'La categoría seleccionada ya no está disponible.',
            'media_id.required' => 'Selecciona una imagen destacada.',
            'media_id.exists' => 'La imagen destacada seleccionada ya no está disponible.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'scheduled_for.required_if' => 'Indica la fecha y hora en que se publicará la noticia.',
            'scheduled_for.date' => 'La fecha de programación no tiene un formato válido.',
            'scheduled_for.after' => 'La fecha de programación debe ser posterior a la hora actual.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scheduled_for' => 'fecha de programación',
        ];
    }

    protected function prepareForValidation(): void
    {
        $bodyWithSpacing = preg_replace(
            '/(?:<br\s*\/?>|<\/(?:p|h[1-6]|li|blockquote|div)>)/i',
            ' ',
            (string) $this->input('body'),
        ) ?? '';
        $excerpt = $this->filled('excerpt')
            ? trim((string) $this->input('excerpt'))
            : Str::of(html_entity_decode(
                strip_tags($bodyWithSpacing),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            ))->squish()->limit(280, '…')->toString();
        $seoTitle = $this->filled('seo_title')
            ? trim((string) $this->input('seo_title'))
            : Str::limit(trim((string) $this->input('title')), 70, '…');
        $seoDescription = $this->filled('seo_description')
            ? trim((string) $this->input('seo_description'))
            : Str::limit($excerpt, 170, '…');

        $this->merge([
            'slug' => $this->filled('slug') ? mb_strtolower(trim((string) $this->input('slug'))) : null,
            'excerpt' => $excerpt,
            'tag_names' => $this->filled('tag_names') ? trim((string) $this->input('tag_names')) : null,
            'source_name' => $this->filled('source_name') ? trim((string) $this->input('source_name')) : null,
            'source_url' => $this->filled('source_url') ? trim((string) $this->input('source_url')) : null,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
        ]);
    }
}
