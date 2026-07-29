<?php

namespace App\Support;

use App\Models\Location;
use App\Models\PortalSetting;
use Illuminate\Support\Collection;

class DefaultLocationSettings
{
    public const SETTING_KEY = 'site.default_location';

    /**
     * @return array{country?: int, region?: int, province?: int, district?: int}
     */
    public function selection(): array
    {
        $stored = PortalSetting::value(self::SETTING_KEY);

        return $this->normalize(is_array($stored) ? $stored : $this->catalogDefaults());
    }

    /**
     * @param  array<string, int|null>  $selection
     * @return Collection<string, Collection<int, Location>>
     */
    public function options(array $selection): Collection
    {
        $options = collect([
            'country' => Location::query()
                ->active()
                ->where('type', 'country')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
        ]);

        foreach (['region' => 'country', 'province' => 'region', 'district' => 'province'] as $type => $parentType) {
            $parentId = $selection[$parentType] ?? null;
            $options->put($type, $parentId
                ? Location::query()
                    ->active()
                    ->where('type', $type)
                    ->where('parent_id', $parentId)
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->get()
                : collect());
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $selection
     * @return array{country?: int, region?: int, province?: int, district?: int}
     */
    public function normalize(array $selection): array
    {
        $normalized = [];
        $parentId = null;

        foreach (['country', 'region', 'province', 'district'] as $type) {
            $id = filter_var($selection[$type] ?? null, FILTER_VALIDATE_INT);
            if (! $id) {
                break;
            }

            $location = Location::query()
                ->active()
                ->where('type', $type)
                ->find($id);

            if (! $location || ($type !== 'country' && $location->parent_id !== $parentId)) {
                break;
            }

            $normalized[$type] = $location->id;
            $parentId = $location->id;
        }

        return $normalized;
    }

    /**
     * @return array<string, int>
     */
    private function catalogDefaults(): array
    {
        $country = Location::query()
            ->active()
            ->where('type', 'country')
            ->where(fn ($query) => $query
                ->where('country_code', 'PE')
                ->orWhere('slug', 'peru'))
            ->first();

        if (! $country) {
            return [];
        }

        $selection = ['country' => $country->id];
        $region = Location::query()
            ->active()
            ->where('type', 'region')
            ->where('parent_id', $country->id)
            ->where('slug', 'moquegua')
            ->first();

        if ($region) {
            $selection['region'] = $region->id;
        }

        return $selection;
    }
}
