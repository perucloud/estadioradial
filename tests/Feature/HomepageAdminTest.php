<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Support\HomeHeroConfig;
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
            ->assertSee('Slider de noticias más vistas')
            ->assertSee('data-appearance-tab="hero"', false)
            ->assertSee('Configuración avanzada')
            ->assertSee('Todas las noticias disponibles')
            ->assertSee('Categorías seleccionadas')
            ->assertSee('Cinematográfico')
            ->assertSee('Orden de publicación')
            ->assertSee('Más recientes primero')
            ->assertSee('Regionales')
            ->assertSee('data-regional-location', false)
            ->assertDontSee('data-appearance-tab="editorial"', false);
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

    public function test_categories_support_hierarchy_seo_and_cycle_protection(): void
    {
        $parent = Category::query()->where('slug', 'deportes')->firstOrFail();

        $this->actingAs($this->superadmin)->post(route('admin.categories.store'), [
            'name' => 'Fútbol regional',
            'parent_id' => $parent->id,
            'color' => '#176442',
            'description' => 'Noticias y resultados del fútbol de la región.',
            'relevance_weight' => 70,
            'homepage_limit' => 4,
            'homepage_layout' => 'standard',
            'is_active' => 1,
            'show_in_menu' => 1,
            'show_on_home' => 1,
        ])->assertSessionHasNoErrors();

        $child = Category::query()->where('slug', 'futbol-regional')->firstOrFail();

        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('Fútbol regional', $child->seo_title);
        $this->assertSame('Noticias y resultados del fútbol de la región.', $child->seo_description);

        $this->actingAs($this->superadmin)->put(route('admin.categories.update', $parent), [
            'name' => $parent->name,
            'slug' => $parent->slug,
            'parent_id' => $child->id,
            'color' => $parent->color,
            'description' => $parent->description,
            'relevance_weight' => $parent->relevance_weight,
            'homepage_limit' => $parent->homepage_limit,
            'homepage_layout' => $parent->homepage_layout,
            'is_active' => 1,
            'show_in_menu' => 1,
            'show_on_home' => 1,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->refresh()->parent_id);
    }

    public function test_deleting_a_category_reassigns_posts_and_supports_restore(): void
    {
        $source = Category::query()->where('slug', 'actualidad')->firstOrFail();
        $replacement = Category::query()->where('slug', 'politica')->firstOrFail();
        $postIds = $source->posts()->pluck('posts.id');

        $this->actingAs($this->superadmin)
            ->delete(route('admin.categories.destroy', $source), [
                'replacement_category_id' => $replacement->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSoftDeleted('categories', ['id' => $source->id]);
        $this->assertDatabaseMissing('posts', ['category_id' => $source->id]);
        foreach ($postIds as $postId) {
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
                'category_id' => $replacement->id,
            ]);
        }

        $this->actingAs($this->superadmin)
            ->post(route('admin.categories.restore', $source->id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'id' => $source->id,
            'deleted_at' => null,
        ]);
    }

    public function test_category_administration_has_filters_tree_and_pagination(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('admin.categories.index', [
                'q' => 'política',
                'status' => 'active',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Buscar por nombre, slug o descripción')
            ->assertSee('Categoría superior')
            ->assertSee('10 por página')
            ->assertSee('Título SEO')
            ->assertSee('Papelera')
            ->assertSee('data-category-create-dialog', false)
            ->assertSee('data-category-edit-dialog', false)
            ->assertSee('data-category-edit', false)
            ->assertSee('data-confirm-delete', false)
            ->assertSee('Nueva categoría')
            ->assertDontSee('row-editor__panel--category', false);
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
        $category = Category::query()->where('slug', 'politica')->firstOrFail();
        $region = Location::query()->where('type', 'region')->where('slug', 'puno')->firstOrFail();
        $province = Location::query()->where('type', 'province')->where('slug', 'san-roman')->firstOrFail();
        $district = Location::query()->where('type', 'district')->where('slug', 'juliaca')->firstOrFail();
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
                'national' => [
                    'enabled' => 1,
                    'news_limit' => 4,
                ],
                'regional' => [
                    'enabled' => 1,
                    'category_mode' => 'selected',
                    'category_ids' => [$category->id],
                    'sort_order' => 'oldest',
                    'pagination_enabled' => 1,
                    'show_page_numbers' => 1,
                    'per_page' => 6,
                    'region_id' => $region->id,
                    'province_id' => $province->id,
                    'district_id' => $district->id,
                    'highlight_province' => 1,
                    'highlight_district' => 1,
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
        $national = PortalSetting::value('home.national_news');
        $regional = PortalSetting::value('home.regional_news');

        $this->assertSame(12000, $hero['interval']);
        $this->assertSame('fade', $hero['effect']);
        $this->assertSame($manualOrder->all(), $hero['post_ids']);
        $this->assertSame('manual', $slider['mode']);
        $this->assertSame(9000, $slider['interval']);
        $this->assertSame(6, $slider['news_limit']);
        $this->assertTrue($national['enabled']);
        $this->assertSame(4, $national['news_limit']);
        $this->assertSame([$category->id], $regional['category_ids']);
        $this->assertSame('oldest', $regional['sort_order']);
        $this->assertSame($district->id, $regional['district_id']);
        $this->assertTrue($regional['pagination_enabled']);
        $this->assertTrue($regional['highlight_province']);
        $this->assertTrue($regional['highlight_district']);
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

    public function test_professional_hero_configuration_is_persisted_and_rendered(): void
    {
        $category = Category::query()->where('slug', 'politica')->firstOrFail();

        $this->actingAs($this->superadmin)
            ->put(route('admin.appearance.homepage.update'), [
                'hero' => [
                    'mode' => 'automatic',
                    'interval_seconds' => 10,
                    'effect' => 'scale-fade',
                    'quantity_mode' => 'all',
                    'news_limit' => 4,
                    'selection_mode' => 'automatic',
                    'category_mode' => 'selected',
                    'category_ids' => [$category->id],
                    'preset_mode' => 'cinematic',
                    'image_animation' => 'ken-burns',
                    'image_intensity' => 'soft-slow',
                    'content_animation' => 'fade-up',
                    'transition_duration' => 1200,
                    'overlay_opacity' => 30,
                    'preload_images' => 1,
                    'pause_on_hover' => 1,
                    'swipe' => 1,
                    'lazy_load' => 1,
                    'animate_when_visible' => 1,
                    'show_arrows' => 1,
                    'show_indicators' => 0,
                    'loop' => 1,
                    'pause_when_hidden' => 1,
                    'reset_after_manual' => 1,
                    'reduce_motion_mobile' => 1,
                ],
                'slider' => [
                    'mode' => 'automatic',
                    'interval_seconds' => 6,
                    'news_limit' => 8,
                    'period_days' => 30,
                    'loop' => 1,
                ],
                'national' => [
                    'enabled' => 1,
                    'news_limit' => 5,
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $hero = PortalSetting::value('home.hero_rotator');

        $this->assertSame('all', $hero['quantity_mode']);
        $this->assertSame([$category->id], $hero['category_ids']);
        $this->assertSame('cinematic', $hero['preset_mode']);
        $this->assertSame('scale-fade', $hero['effect']);
        $this->assertSame(1200, $hero['transition_duration']);
        $this->assertFalse($hero['show_indicators']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-hero-effect="scale-fade"', false)
            ->assertSee('data-hero-image-animation="ken-burns"', false)
            ->assertSee('data-hero-transition-duration="1200"', false)
            ->assertDontSee('data-hero-dot', false);
    }

    public function test_hero_category_filter_and_all_quantity_are_applied_to_public_homepage(): void
    {
        $included = Category::query()->create([
            'name' => 'Hero exclusivo',
            'slug' => 'hero-exclusivo',
            'color' => '#315f8a',
            'is_active' => true,
            'show_in_menu' => true,
            'show_on_home' => true,
            'display_order' => 900,
        ]);
        $excluded = Category::query()->where('id', '!=', $included->id)->firstOrFail();

        foreach ([1, 2, 3] as $index) {
            Post::query()->create([
                'category_id' => $included->id,
                'title' => "Noticia exclusiva {$index}",
                'slug' => "noticia-exclusiva-{$index}",
                'excerpt' => 'Resumen editorial.',
                'body' => '<p>Contenido.</p>',
                'status' => 'published',
                'is_homepage_hidden' => false,
                'published_at' => now()->subMinutes($index),
            ]);
        }

        Post::query()->create([
            'category_id' => $excluded->id,
            'title' => 'Noticia fuera del filtro',
            'slug' => 'noticia-fuera-del-filtro',
            'excerpt' => 'No debe aparecer.',
            'body' => '<p>Contenido.</p>',
            'status' => 'published',
            'is_homepage_hidden' => false,
            'published_at' => now(),
        ]);

        PortalSetting::put('home.hero_rotator', array_replace(
            HomeHeroConfig::defaults(),
            [
                'quantity_mode' => 'all',
                'category_mode' => 'selected',
                'category_ids' => [$included->id],
                'sort_order' => 'oldest',
            ],
        ), 'home');

        $response = $this->get(route('home'))->assertOk();
        $featuredPosts = $response->viewData('featuredPosts');

        $this->assertCount(3, $featuredPosts);
        $this->assertTrue($featuredPosts->every(fn (Post $post) => $post->category_id === $included->id));
        $this->assertSame(
            ['Noticia exclusiva 3', 'Noticia exclusiva 2', 'Noticia exclusiva 1'],
            $featuredPosts->pluck('title')->all(),
        );
    }

    public function test_hero_rejects_zero_as_a_specific_news_quantity(): void
    {
        $this->actingAs($this->superadmin)
            ->from(route('admin.appearance.homepage.edit'))
            ->put(route('admin.appearance.homepage.update'), [
                'hero' => [
                    'mode' => 'automatic',
                    'interval_seconds' => 8,
                    'effect' => 'fade',
                    'quantity_mode' => 'specific',
                    'news_limit' => 0,
                    'selection_mode' => 'automatic',
                    'category_mode' => 'all',
                    'preset_mode' => 'custom',
                    'image_animation' => 'none',
                    'image_intensity' => 'soft',
                    'content_animation' => 'fade',
                    'transition_duration' => 800,
                    'overlay_opacity' => 0,
                ],
                'slider' => [
                    'mode' => 'automatic',
                    'interval_seconds' => 6,
                    'news_limit' => 8,
                    'period_days' => 30,
                ],
                'national' => [
                    'enabled' => 1,
                    'news_limit' => 5,
                ],
            ])
            ->assertSessionHasErrors('hero.news_limit');
    }
}
