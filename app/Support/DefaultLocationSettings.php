<?php

namespace App\Support;

use App\Models\Location;
use App\Models\PortalSetting;
use Illuminate\Support\Collection;

class DefaultLocationSettings
{
    public const SETTING_KEY = 'site.default_location';

    public const BADGE_SETTING_KEY = 'site.editorial_territory';

    private ?array $identityCache = null;

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
     * @return array{enabled: bool, label: string, custom_label: string, automatic_label: string, location: ?Location, region: ?Location}
     */
    public function editorialIdentity(): array
    {
        if ($this->identityCache !== null) {
            return $this->identityCache;
        }

        $selection = $this->selection();
        $locations = Location::query()
            ->active()
            ->whereIn('id', array_values($selection))
            ->get()
            ->keyBy('id');
        $region = isset($selection['region']) ? $locations->get($selection['region']) : null;
        $location = collect(['district', 'province', 'region', 'country'])
            ->map(fn (string $type) => isset($selection[$type]) ? $locations->get($selection[$type]) : null)
            ->first();
        $automaticLabel = collect([$location?->name, $region?->name])
            ->filter()
            ->unique()
            ->implode(' · ');
        $settings = PortalSetting::value(self::BADGE_SETTING_KEY, []);
        $settings = is_array($settings) ? $settings : [];
        $customLabel = trim((string) ($settings['label'] ?? ''));

        return $this->identityCache = [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'label' => $customLabel !== '' ? $customLabel : $automaticLabel,
            'custom_label' => $customLabel,
            'automatic_label' => $automaticLabel,
            'location' => $location,
            'region' => $region,
        ];
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
            ->where('slug', 'puno')
            ->first();

        if (! $region) {
            return $selection;
        }

        $selection['region'] = $region->id;
        $province = Location::query()
            ->active()
            ->where('type', 'province')
            ->where('parent_id', $region->id)
            ->where('slug', 'san-roman')
            ->first();

        if (! $province) {
            return $selection;
        }

        $selection['province'] = $province->id;
        $district = Location::query()
            ->active()
            ->where('type', 'district')
            ->where('parent_id', $province->id)
            ->where('slug', 'juliaca')
            ->first();

        if ($district) {
            $selection['district'] = $district->id;
        }

        return $selection;
    }
}
