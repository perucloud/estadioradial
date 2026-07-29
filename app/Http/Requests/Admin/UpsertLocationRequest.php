<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpsertLocationRequest extends FormRequest
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
        $location = $this->route('location');
        $parentId = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:140',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('locations')
                    ->where(fn ($query) => $parentId === null
                        ? $query->whereNull('parent_id')
                        : $query->where('parent_id', $parentId))
                    ->ignore($location),
            ],
            'type' => ['required', Rule::in(array_keys(Location::TYPES))],
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'ubigeo' => ['nullable', 'string', 'max:12', 'regex:/^[0-9A-Za-z-]+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:1500'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre de la ubicación.',
            'slug.unique' => 'Ya existe una ubicación con este slug dentro del mismo nivel.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'type.in' => 'Selecciona un tipo de ubicación válido.',
            'parent_id.exists' => 'La ubicación superior seleccionada no está disponible.',
            'country_code.size' => 'El código de país debe tener dos letras.',
            'ubigeo.regex' => 'El UBIGEO solo puede contener letras, números y guiones.',
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
            'country_code' => $this->filled('country_code')
                ? mb_strtoupper(trim((string) $this->input('country_code')))
                : null,
            'ubigeo' => $this->filled('ubigeo') ? trim((string) $this->input('ubigeo')) : null,
            'latitude' => $this->filled('latitude') ? $this->input('latitude') : null,
            'longitude' => $this->filled('longitude') ? $this->input('longitude') : null,
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
