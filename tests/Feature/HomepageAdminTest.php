<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::query()->where('slug', 'superadmin')->firstOrFail());
    }

    public function test_superadmin_can_open_taxonomy_and_homepage_configuration(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Organización editorial')
            ->assertSee('Guardar orden');

        $this->actingAs($this->superadmin)
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('Combinar con otra');

        $this->actingAs($this->superadmin)
            ->get(route('admin.appearance.homepage.edit'))
            ->assertOk()
            ->assertSee('Hero de noticias')
            ->assertSee('Slider de noticias más vistas');
    }

    public function test_categories_can_be_created_reordered_and_hidden(): void
    {
        $this->actingAs($this->superadmin)->post(route('admin.categories.store'), [
            'name' => 'Provincias',
            'slug' => 'provincias',
            'color' => '#315f8a',
            'description' => 'Información provincial',
            'relevance_weight' => 85,
            'homepage_limit' => 5,
            'homepage_layout' => 'featured',
        ])->assertSessionHas('status');

        $category = Category::query()->where('slug', 'provincias')->firstOrFail();
        $first = Category::query()->where('id', '!=', $category->id)->firstOrFail();

        $this->actingAs($this->superadmin)->post(route('admin.categories.reorder'), [
            'order' => [
                $category->id => 10,
                $first->id => 20,
            ],
        ])->assertSessionHas('status');

        $this->assertSame(10, $category->refresh()->display_order);

        $this->actingAs($this->superadmin)->put(route('admin.categories.update', $category->id), [
            'name' => 'Provincias',
            'slug' => 'provincias',
            'color' => '#315f8a',
            'description' => 'Información provincial',
            'relevance_weight' => 85,
            'homepage_limit' => 5,
            'homepage_layout' => 'featured',
        ])->assertSessionHas('status');

        $this->assertFalse($category->refresh()->is_active);
        $this->get(route('posts.category', $category))->assertNotFound();
    }

    public function test_tags_can_be_merged_without_losing_post_relations(): void
    {
        $source = Tag::query()->whereHas('posts')->firstOrFail();
        $target = Tag::query()->where('id', '!=', $source->id)->firstOrFail();
        $sourcePostIds = $source->posts()->pluck('posts.id');

        $this->actingAs($this->superadmin)
            ->post(route('admin.tags.merge', $source->id), ['target_id' => $target->id])
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('tags', ['id' => $source->id]);

        foreach ($sourcePostIds as $postId) {
            $this->assertDatabaseHas('post_tag', [
                'post_id' => $postId,
                'tag_id' => $target->id,
            ]);
        }
    }

    public function test_homepage_configuration_controls_hero_slider_and_post_priority(): void
    {
        $posts = Post::query()->published()->take(4)->get();
        $manualOrder = $posts->pluck('id')->reverse()->values();
        $configuredPost = $posts->first();
        $heroPosts = $manualOrder->mapWithKeys(fn (int $id, int $index) => [
            $id => ['selected' => 1, 'order' => $index + 1],
        ])->all();

        $this->actingAs($this->superadmin)
            ->put(route('admin.appearance.homepage.update'), [
                'hero' => [
                    'mode' => 'manual',
                    'interval_seconds' => 12,
                    'effect' => 'fade',
                    'news_limit' => 4,
                    'selection_mode' => 'manual',
                    'loop' => 1,
                ],
                'hero_posts' => $heroPosts,
                'slider' => [
                    'mode' => 'manual',
                    'interval_seconds' => 9,
                    'news_limit' => 6,
                    'period_days' => 0,
                    'loop' => 1,
                ],
                'posts' => [
                    $configuredPost->id => [
                        'editorial_priority' => 777,
                        'is_featured' => 1,
                        'is_homepage_hidden' => 0,
                        'pinned_until' => now()->addDay()->format('Y-m-d H:i:s'),
                    ],
                ],
            ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $hero = PortalSetting::value('home.hero_rotator');
        $slider = PortalSetting::value('home.most_viewed_slider');

        $this->assertSame(12000, $hero['interval']);
        $this->assertSame('fade', $hero['effect']);
        $this->assertSame($manualOrder->all(), $hero['post_ids']);
        $this->assertSame('manual', $slider['mode']);
        $this->assertSame(9000, $slider['interval']);
        $this->assertSame(6, $slider['news_limit']);
        $this->assertSame(777, $configuredPost->refresh()->editorial_priority);

        $response = $this->get(route('home'))->assertOk();
        $this->assertSame($manualOrder->all(), $response->viewData('featuredPosts')->pluck('id')->all());
        $response
            ->assertSee('data-hero-interval="12000"', false)
            ->assertSee('data-hero-effect="fade"', false)
            ->assertSee('data-slider-mode="manual"', false)
            ->assertSee('data-slider-interval="9000"', false);
    }

    public function test_a_recent_publication_appears_first_in_the_automatic_hero_regardless_of_category_priority(): void
    {
        $category = Category::query()->where('slug', 'politica')->firstOrFail();
        $category->update(['relevance_weight' => 1]);
        $post = Post::query()->create([
            'category_id' => $category->id,
            'title' => 'César Astudillo jura como nuevo ministro del Interior del gobierno de Keiko Fujimori',
            'slug' => 'cesar-astudillo-nuevo-ministro-interior',
            'excerpt' => 'El nuevo titular del Interior juró el cargo durante una ceremonia oficial.',
            'body' => '<p>La publicación reciente debe ocupar la posición principal del hero.</p>',
            'status' => 'published',
            'is_featured' => false,
            'editorial_priority' => 0,
            'is_homepage_hidden' => false,
            'published_at' => now(),
        ]);

        $response = $this->get(route('home'))->assertOk();

        $this->assertSame($post->id, $response->viewData('featuredPosts')->first()->id);
        $response->assertSee($post->title);
    }
}
