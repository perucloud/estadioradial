<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDefaultLocationRequest extends FormRequest
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
        return [
            'default_location_country_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'country')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'default_location_region_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'region')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'default_location_province_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'province')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'default_location_district_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'district')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            $ids = [
                'country' => $this->integer('default_location_country_id'),
                'region' => $this->integer('default_location_region_id') ?: null,
                'province' => $this->integer('default_location_province_id') ?: null,
                'district' => $this->integer('default_location_district_id') ?: null,
            ];
            $parentId = null;

            foreach ($ids as $type => $id) {
                if (! $id) {
                    if (collect($ids)->skipUntil(fn ($value, $key) => $key === $type)->skip(1)->filter()->isNotEmpty()) {
                        $validator->errors()->add(
                            'default_location_'.$type.'_id',
                            'Completa la jerarquía geográfica en orden.',
                        );
                    }

                    break;
                }

                $location = Location::query()->find($id);
                if ($type !== 'country' && $location?->parent_id !== $parentId) {
                    $validator->errors()->add(
                        'default_location_'.$type.'_id',
                        'La ubicación seleccionada no pertenece al nivel superior.',
                    );

                    break;
                }

                $parentId = $location?->id;
            }
        });
    }

    /**
     * @return array<string, int|null>
     */
    public function selection(): array
    {
        return [
            'country' => $this->integer('default_location_country_id'),
            'region' => $this->integer('default_location_region_id') ?: null,
            'province' => $this->integer('default_location_province_id') ?: null,
            'district' => $this->integer('default_location_district_id') ?: null,
        ];
    }
}
