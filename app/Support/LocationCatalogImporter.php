<?php

namespace App\Support;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class LocationCatalogImporter
{
    public const COUNTRIES_SOURCE = 'millan2993/countries';

    public const PERU_SOURCE = 'RitchieRD/ubigeos-peru-data';

    /**
     * @return array<string, int>
     */
    public function import(): array
    {
        return DB::transaction(function (): array {
            $summary = [
                'countries' => 0,
                'regions' => 0,
                'provinces' => 0,
                'districts' => 0,
            ];

            foreach ($this->records('countries/countries.json', 'countries') as $index => $record) {
                $name = trim((string) ($record['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $country = $this->reconcile(
                    parent: null,
                    type: 'country',
                    name: $name,
                    source: self::COUNTRIES_SOURCE,
                    sourceKey: (string) ($record['id'] ?? $index + 1),
                    order: ($index + 1) * 10,
                );

                if ($country->slug === 'peru' && blank($country->country_code)) {
                    $country->update(['country_code' => 'PE']);
                }

                $summary['countries']++;
            }

            $peru = Location::query()
                ->where('type', 'country')
                ->where(fn ($query) => $query->where('country_code', 'PE')->orWhere('slug', 'peru'))
                ->firstOrFail();

            $departments = [];
            foreach ($this->records('ubigeos-peru/json/1_ubigeo_departamentos.json', 'ubigeo_departamentos') as $index => $record) {
                $sourceId = (string) $record['id'];
                $departments[$sourceId] = $this->reconcile(
                    parent: $peru,
                    type: 'region',
                    name: (string) $record['departamento'],
                    source: self::PERU_SOURCE,
                    sourceKey: 'department:'.$sourceId,
                    ubigeo: (string) $record['ubigeo'],
                    order: ($index + 1) * 10,
                );
                $summary['regions']++;
            }

            $provinces = [];
            foreach ($this->records('ubigeos-peru/json/2_ubigeo_provincias.json', 'ubigeo_provincias') as $index => $record) {
                $sourceId = (string) $record['id'];
                $parent = $departments[(string) $record['departamento_id']] ?? null;

                if (! $parent) {
                    throw new RuntimeException('El catálogo contiene una provincia sin departamento válido.');
                }

                $provinces[$sourceId] = $this->reconcile(
                    parent: $parent,
                    type: 'province',
                    name: (string) $record['provincia'],
                    source: self::PERU_SOURCE,
                    sourceKey: 'province:'.$sourceId,
                    ubigeo: (string) $record['ubigeo'],
                    order: ($index + 1) * 10,
                );
                $summary['provinces']++;
            }

            foreach ($this->records('ubigeos-peru/json/3_ubigeo_distritos.json', 'ubigeo_distritos') as $index => $record) {
                $parent = $provinces[(string) $record['provincia_id']] ?? null;

                if (! $parent) {
                    throw new RuntimeException('El catálogo contiene un distrito sin provincia válida.');
                }

                $aliases = (string) $record['ubigeo'] === '180105'
                    ? ['San Cristóbal de Calacoa']
                    : [];

                $this->reconcile(
                    parent: $parent,
                    type: 'district',
                    name: (string) $record['distrito'],
                    source: self::PERU_SOURCE,
                    sourceKey: 'district:'.(string) $record['id'],
                    ubigeo: (string) $record['ubigeo'],
                    order: ($index + 1) * 10,
                    aliases: $aliases,
                );
                $summary['districts']++;
            }

            return $summary;
        });
    }

    /**
     * @param  list<string>  $aliases
     */
    private function reconcile(
        ?Location $parent,
        string $type,
        string $name,
        string $source,
        string $sourceKey,
        int $order,
        ?string $ubigeo = null,
        array $aliases = [],
    ): Location {
        $displayName = $this->displayName($name);
        $slugs = collect([$displayName, ...$aliases])->map(fn (string $value) => Str::slug($value))->unique();

        $location = Location::withTrashed()
            ->where('source', $source)
            ->where('source_key', $sourceKey)
            ->first();

        if (! $location && $ubigeo !== null) {
            $location = Location::withTrashed()
                ->where('type', $type)
                ->where('ubigeo', $ubigeo)
                ->first();
        }

        if (! $location) {
            $location = Location::withTrashed()
                ->where('parent_id', $parent?->id)
                ->where('type', $type)
                ->whereIn('slug', $slugs)
                ->first();
        }

        $attributes = [
            'parent_id' => $parent?->id,
            'type' => $type,
            'ubigeo' => $ubigeo,
            'source' => $source,
            'source_key' => $sourceKey,
            'is_active' => true,
            'display_order' => min($order, 65535),
        ];

        if ($location) {
            $location->fill($attributes);
            $location->seo_title ??= Str::limit($location->name, 70, '…');
            $location->save();
            if ($location->trashed()) {
                $location->restore();
            }

            return $location;
        }

        return Location::query()->create($attributes + [
            'name' => $displayName,
            'slug' => Str::slug($displayName),
            'seo_title' => Str::limit($displayName, 70, '…'),
        ]);
    }

    private function displayName(string $name): string
    {
        $name = mb_convert_case(mb_strtolower(trim($name)), MB_CASE_TITLE, 'UTF-8');

        return preg_replace_callback(
            '/\b(De|Del|La|Las|Los|Y)\b/u',
            fn (array $match) => mb_strtolower($match[1], 'UTF-8'),
            $name,
        ) ?? $name;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws JsonException
     */
    private function records(string $relativePath, string $key): array
    {
        $path = database_path('data/'.$relativePath);

        if (! is_file($path)) {
            throw new RuntimeException('No se encontró el catálogo local: '.$relativePath);
        }

        $contents = (string) file_get_contents($path);
        $contents = str_starts_with($contents, "\xEF\xBB\xBF") ? substr($contents, 3) : $contents;
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $records = $data[$key] ?? null;

        if (! is_array($records)) {
            throw new RuntimeException('El catálogo local tiene una estructura inválida: '.$relativePath);
        }

        return array_values($records);
    }
}
