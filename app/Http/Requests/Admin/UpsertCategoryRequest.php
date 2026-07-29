<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpsertCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories')->ignore($category),
            ],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'relevance_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'homepage_limit' => ['required', 'integer', 'min:1', 'max:12'],
            'homepage_layout' => ['required', Rule::in(['standard', 'featured', 'grid'])],
            'is_active' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre de la categoría.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe una categoría con este slug.',
            'parent_id.exists' => 'La categoría superior seleccionada no está disponible.',
            'color.regex' => 'Selecciona un color hexadecimal válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $description = trim((string) $this->input('description'));

        $this->merge([
            'name' => $name,
            'slug' => Str::slug($this->input('slug') ?: $name),
            'parent_id' => $this->filled('parent_id') ? (int) $this->input('parent_id') : null,
            'icon' => $this->filled('icon') ? trim((string) $this->input('icon')) : null,
            'description' => $description !== '' ? $description : null,
            'seo_title' => $this->filled('seo_title')
                ? trim((string) $this->input('seo_title'))
                : Str::limit($name, 70, '…'),
            'seo_description' => $this->filled('seo_description')
                ? trim((string) $this->input('seo_description'))
                : ($description !== '' ? Str::limit($description, 170, '…') : null),
        ]);
    }
}
