<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $selection = $this->selection('puno', 'san-roman', 'juliaca');

        if (count($selection) === 4) {
            $this->putSetting('site.default_location', $selection, false);
        }

        $this->putSetting('site.editorial_territory', [
            'enabled' => true,
            'label' => null,
        ], true);
    }

    public function down(): void
    {
        $selection = $this->selection('moquegua');

        if (count($selection) === 2) {
            $this->putSetting('site.default_location', $selection, false);
        }

        DB::table('portal_settings')
            ->where('key', 'site.editorial_territory')
            ->delete();
    }

    /**
     * @return array<string, int>
     */
    private function selection(
        string $regionSlug,
        ?string $provinceSlug = null,
        ?string $districtSlug = null,
    ): array {
        $country = DB::table('locations')
            ->where('type', 'country')
            ->where('slug', 'peru')
            ->whereNull('deleted_at')
            ->first();

        if (! $country) {
            return [];
        }

        $selection = ['country' => $country->id];
        $region = DB::table('locations')
            ->where('type', 'region')
            ->where('parent_id', $country->id)
            ->where('slug', $regionSlug)
            ->whereNull('deleted_at')
            ->first();

        if (! $region) {
            return $selection;
        }

        $selection['region'] = $region->id;

        if (! $provinceSlug) {
            return $selection;
        }

        $province = DB::table('locations')
            ->where('type', 'province')
            ->where('parent_id', $region->id)
            ->where('slug', $provinceSlug)
            ->whereNull('deleted_at')
            ->first();

        if (! $province) {
            return $selection;
        }

        $selection['province'] = $province->id;

        if (! $districtSlug) {
            return $selection;
        }

        $district = DB::table('locations')
            ->where('type', 'district')
            ->where('parent_id', $province->id)
            ->where('slug', $districtSlug)
            ->whereNull('deleted_at')
            ->first();

        if ($district) {
            $selection['district'] = $district->id;
        }

        return $selection;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function putSetting(string $key, array $value, bool $isPublic): void
    {
        DB::table('portal_settings')->updateOrInsert(
            ['key' => $key],
            [
                'group' => 'site',
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_public' => $isPublic,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
