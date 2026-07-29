<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Post;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyLocationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_location_migration_only_applies_unambiguous_matches(): void
    {
        $this->seed(LocationSeeder::class);
        $category = Category::query()->create([
            'name' => 'Regionales',
            'slug' => 'regionales',
            'color' => '#a61b1b',
            'is_active' => true,
        ]);
        $carumasPost = Post::query()->create([
            'category_id' => $category->id,
            'title' => 'Carumas fortalece sus servicios municipales',
            'slug' => 'carumas-fortalece-servicios-municipales',
            'excerpt' => 'La municipalidad distrital presentó su balance.',
            'body' => '<p>La información corresponde al distrito de Carumas.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $unresolvedPost = Post::query()->create([
            'category_id' => $category->id,
            'title' => 'Comunidades inauguran una nueva vía',
            'slug' => 'comunidades-inauguran-nueva-via',
            'excerpt' => 'La obra fue presentada sin precisar una ubicación.',
            'body' => '<p>El territorio todavía debe ser confirmado por un editor.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->artisan('editorial:migrate-locations', ['--apply' => true])
            ->assertSuccessful();

        $this->assertSame(
            Location::query()->where('slug', 'carumas')->value('id'),
            $carumasPost->fresh()->location_id,
        );
        $this->assertNull($unresolvedPost->fresh()->location_id);
    }
}
