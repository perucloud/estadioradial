<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\LocationCatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_catalogs_load_country_names_and_the_complete_peru_hierarchy(): void
    {
        $this->assertSame(214, Location::query()->where('type', 'country')->count());
        $this->assertSame(25, Location::query()->where('type', 'region')->count());
        $this->assertSame(196, Location::query()->where('type', 'province')->count());
        $this->assertSame(1892, Location::query()->where('type', 'district')->count());

        $peru = Location::query()->where('country_code', 'PE')->firstOrFail();
        $moquegua = Location::query()->where('ubigeo', '18')->firstOrFail();
        $mariscalNieto = Location::query()->where('ubigeo', '1801')->firstOrFail();
        $carumas = Location::query()->where('ubigeo', '180102')->firstOrFail();

        $this->assertSame($peru->id, $moquegua->parent_id);
        $this->assertSame($moquegua->id, $mariscalNieto->parent_id);
        $this->assertSame($mariscalNieto->id, $carumas->parent_id);
        $this->assertSame(LocationCatalogImporter::PERU_SOURCE, $carumas->source);
        $this->assertDatabaseHas('locations', [
            'name' => 'San Cristóbal de Calacoa',
            'ubigeo' => '180105',
        ]);
        $this->assertDatabaseHas('locations', [
            'name' => 'Ecuador',
            'type' => 'country',
            'source' => LocationCatalogImporter::COUNTRIES_SOURCE,
        ]);
    }

    public function test_catalog_import_is_idempotent(): void
    {
        $before = Location::query()->count();

        $this->artisan('locations:import-catalogs')->assertSuccessful();
        $this->artisan('locations:import-catalogs')->assertSuccessful();

        $this->assertSame($before, Location::query()->count());
    }

    public function test_dependent_options_only_return_direct_children(): void
    {
        AdminAccess::sync();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'superadmin')->firstOrFail());
        $peru = Location::query()->where('country_code', 'PE')->firstOrFail();
        $moquegua = Location::query()->where('ubigeo', '18')->firstOrFail();

        $regions = $this->actingAs($user)->getJson(route('admin.locations.options', [
            'type' => 'region',
            'parent_id' => $peru->id,
        ]));

        $regions->assertOk()->assertJsonCount(25, 'data');
        $regions->assertJsonFragment(['id' => $moquegua->id, 'name' => $moquegua->name]);

        $this->actingAs($user)->getJson(route('admin.locations.options', [
            'type' => 'province',
            'parent_id' => $moquegua->id,
        ]))->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_news_form_uses_lazy_dependent_location_catalogs(): void
    {
        AdminAccess::sync();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'superadmin')->firstOrFail());

        $this->actingAs($user)
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertSee('data-location-options-url', false)
            ->assertSee('Ecuador')
            ->assertDontSee('Carumas');
    }
}
