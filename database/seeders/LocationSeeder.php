<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $peru = $this->location(null, 'Perú', 'country', 10, ['country_code' => 'PE']);
        $moquegua = $this->location($peru, 'Moquegua', 'region', 10);

        $mariscalNieto = $this->location($moquegua, 'Mariscal Nieto', 'province', 10);
        $this->location($moquegua, 'General Sánchez Cerro', 'province', 20);
        $this->location($moquegua, 'Ilo', 'province', 30);

        collect([
            'Moquegua',
            'Carumas',
            'Cuchumbaya',
            'Samegua',
            'San Cristóbal de Calacoa',
            'Torata',
        ])->each(fn (string $name, int $index) => $this->location(
            $mariscalNieto,
            $name,
            'district',
            ($index + 1) * 10,
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function location(
        ?Location $parent,
        string $name,
        string $type,
        int $displayOrder,
        array $attributes = [],
    ): Location {
        return Location::withTrashed()->updateOrCreate(
            [
                'parent_id' => $parent?->id,
                'slug' => Str::slug($name),
            ],
            $attributes + [
                'name' => $name,
                'type' => $type,
                'is_active' => true,
                'display_order' => $displayOrder,
                'deleted_at' => null,
                'seo_title' => $name,
            ],
        );
    }
}
