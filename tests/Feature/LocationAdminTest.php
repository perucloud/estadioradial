<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        AdminAccess::sync();
        $this->seed(LocationSeeder::class);
        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::query()->where('slug', 'superadmin')->firstOrFail());
    }

    public function test_initial_moquegua_hierarchy_is_loaded_correctly(): void
    {
        $peru = Location::query()->where('slug', 'peru')->firstOrFail();
        $moquegua = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();
        $mariscalNieto = Location::query()->where('slug', 'mariscal-nieto')->firstOrFail();
        $carumas = Location::query()->where('slug', 'carumas')->firstOrFail();

        $this->assertSame('country', $peru->type);
        $this->assertSame($peru->id, $moquegua->parent_id);
        $this->assertSame($moquegua->id, $mariscalNieto->parent_id);
        $this->assertSame($mariscalNieto->id, $carumas->parent_id);
        $this->assertSame('district', $carumas->type);
        $this->assertDatabaseHas('locations', ['name' => 'General Sánchez Cerro', 'type' => 'province']);
        $this->assertDatabaseHas('locations', ['name' => 'San Cristóbal de Calacoa', 'type' => 'district']);
    }

    public function test_location_dashboard_displays_tree_filters_and_moquegua_data(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('admin.locations.index', ['type' => 'district', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Cobertura geográfica')
            ->assertSee('Buscar nombre, slug o UBIGEO')
            ->assertSee('10 por página')
            ->assertSee('Carumas')
            ->assertSee('San Cristóbal de Calacoa')
            ->assertSee('Título SEO');
    }

    public function test_location_creation_enforces_geographic_levels_and_generates_seo(): void
    {
        $peru = Location::query()->where('slug', 'peru')->firstOrFail();
        $moquegua = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();

        $this->actingAs($this->superadmin)->post(route('admin.locations.store'), [
            'name' => 'Nueva provincia',
            'type' => 'province',
            'parent_id' => $moquegua->id,
            'description' => 'Cobertura informativa de la nueva provincia.',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $province = Location::query()->where('slug', 'nueva-provincia')->firstOrFail();
        $this->assertSame($moquegua->id, $province->parent_id);
        $this->assertSame('Nueva provincia', $province->seo_title);
        $this->assertSame('Cobertura informativa de la nueva provincia.', $province->seo_description);

        $this->actingAs($this->superadmin)->post(route('admin.locations.store'), [
            'name' => 'Distrito inválido',
            'type' => 'district',
            'parent_id' => $peru->id,
            'is_active' => 1,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('locations', ['slug' => 'distrito-invalido']);
    }

    public function test_parent_locations_are_protected_and_leaf_locations_are_recoverable(): void
    {
        $province = Location::query()->where('slug', 'mariscal-nieto')->firstOrFail();
        $district = Location::query()->where('slug', 'carumas')->firstOrFail();

        $this->actingAs($this->superadmin)
            ->delete(route('admin.locations.destroy', $province))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseHas('locations', ['id' => $province->id, 'deleted_at' => null]);

        $this->actingAs($this->superadmin)
            ->delete(route('admin.locations.destroy', $district))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSoftDeleted('locations', ['id' => $district->id]);

        $this->actingAs($this->superadmin)
            ->post(route('admin.locations.restore', $district->id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('locations', ['id' => $district->id, 'deleted_at' => null]);
    }
}
