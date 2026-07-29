<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
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
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'location_country_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('type', 'country')->where('is_active', true)->whereNull('deleted_at')],
            'location_region_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('type', 'region')->where('is_active', true)->whereNull('deleted_at')],
            'location_province_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('type', 'province')->where('is_active', true)->whereNull('deleted_at')],
            'location_district_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('type', 'district')->where('is_active', true)->whereNull('deleted_at')],
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
            'location_id.exists' => 'La ubicación seleccionada ya no está disponible.',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->hasAny([
                'location_country_id',
                'location_region_id',
                'location_province_id',
                'location_district_id',
            ])) {
                return;
            }

            $ids = [
                'country' => $this->integer('location_country_id') ?: null,
                'region' => $this->integer('location_region_id') ?: null,
                'province' => $this->integer('location_province_id') ?: null,
                'district' => $this->integer('location_district_id') ?: null,
            ];
            $locations = Location::query()
                ->whereIn('id', array_filter($ids))
                ->get()
                ->keyBy('id');
            $levels = [
                'region' => 'country',
                'province' => 'region',
                'district' => 'province',
            ];

            foreach ($levels as $childType => $parentType) {
                $childId = $ids[$childType];

                if ($childId === null) {
                    continue;
                }

                $parentId = $ids[$parentType];
                $child = $locations->get($childId);

                if ($parentId === null || $child?->parent_id !== $parentId) {
                    $validator->errors()->add(
                        "location_{$childType}_id",
                        'La ubicación seleccionada no pertenece a la jerarquía territorial indicada.',
                    );
                }
            }
        });
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
        $locationIds = collect([
            'location_country_id',
            'location_region_id',
            'location_province_id',
            'location_district_id',
        ])->mapWithKeys(fn (string $field) => [
            $field => $this->filled($field) ? (int) $this->input($field) : null,
        ]);

        $this->merge(array_merge([
            'slug' => $this->filled('slug') ? mb_strtolower(trim((string) $this->input('slug'))) : null,
            'excerpt' => $excerpt,
            'tag_names' => $this->filled('tag_names') ? trim((string) $this->input('tag_names')) : null,
            'source_name' => $this->filled('source_name') ? trim((string) $this->input('source_name')) : null,
            'source_url' => $this->filled('source_url') ? trim((string) $this->input('source_url')) : null,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
        ], $locationIds->all(), [
            'location_id' => $locationIds->filter()->last(),
        ]));
    }
}
