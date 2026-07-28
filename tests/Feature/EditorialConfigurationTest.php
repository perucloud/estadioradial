<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_categories_keep_an_administrable_editorial_order(): void
    {
        $orderedSlugs = Category::query()
            ->where('is_active', true)
            ->where('show_in_menu', true)
            ->orderBy('display_order')
            ->pluck('slug')
            ->take(4)
            ->all();

        $this->assertSame(['regionales', 'locales', 'politica', 'economia'], $orderedSlugs);
    }

    public function test_homepage_selection_uses_post_and_category_priority(): void
    {
        $regional = Category::query()->where('slug', 'regionales')->firstOrFail();
        $currentTopPost = Post::query()
            ->published()
            ->visibleOnHome()
            ->orderByDesc('is_featured')
            ->editorialOrder()
            ->firstOrFail();

        $priorityPost = Post::query()->create([
            'category_id' => $regional->id,
            'title' => 'Prioridad editorial regional',
            'slug' => 'prioridad-editorial-regional',
            'excerpt' => 'Noticia preparada para comprobar el orden editorial.',
            'body' => '<p>Contenido de prueba.</p>',
            'status' => 'published',
            'is_featured' => true,
            'editorial_priority' => 500,
            'pinned_until' => now()->addDays(2),
            'published_at' => now()->subDay(),
        ]);

        $selected = Post::query()
            ->published()
            ->visibleOnHome()
            ->orderByDesc('is_featured')
            ->editorialOrder()
            ->firstOrFail();

        $this->assertNotSame($currentTopPost->id, $selected->id);
        $this->assertSame($priorityPost->id, $selected->id);
    }

    public function test_curated_news_exposes_tags_source_and_image_rights(): void
    {
        $post = Post::query()
            ->where('slug', 'keiko-fujimori-asume-presidencia-cambio-de-mando-2026')
            ->firstOrFail();

        $this->get(route('posts.show', [$post->category, $post]))
            ->assertOk()
            ->assertSee('#Keiko Fujimori')
            ->assertSee('Fuente consultada')
            ->assertSee('El País')
            ->assertSee('Recurso gráfico propio');

        $this->assertTrue($post->tags()->where('slug', 'senado')->doesntExist());
        $this->assertTrue($post->tags()->where('slug', 'keiko-fujimori')->exists());
    }

    public function test_administrator_can_change_category_priority_with_command(): void
    {
        $this->artisan('editorial:prioritize', [
            'categories' => ['locales', 'deportes', 'regionales'],
            '--hide-missing' => true,
        ])->assertSuccessful();

        $this->assertSame(
            ['locales', 'deportes', 'regionales'],
            Category::query()
                ->where('show_on_home', true)
                ->orderBy('display_order')
                ->pluck('slug')
                ->all(),
        );
    }

    public function test_article_displays_configurable_editorial_sidebar(): void
    {
        $post = Post::query()
            ->where('slug', 'keiko-fujimori-asume-presidencia-cambio-de-mando-2026')
            ->firstOrFail();

        PortalSetting::query()->where('key', 'social.links')->update([
            'value' => json_encode([
                'facebook' => 'https://social.example/facebook',
                'x' => 'https://social.example/x',
                'tiktok' => 'https://social.example/tiktok',
                'instagram' => 'https://social.example/instagram',
                'youtube' => 'https://social.example/youtube',
            ]),
        ]);

        $this->get(route('posts.show', [$post->category, $post]))
            ->assertOk()
            ->assertSeeInOrder([
                'Las más leídas',
                'Últimas noticias',
                'Publicidad',
                'Síguenos',
                'Categorías',
            ])
            ->assertSee('https://social.example/facebook', false)
            ->assertSee('/images/demo/ad-business.svg', false)
            ->assertSee('article-sidebar--sticky', false);
    }

    public function test_inactive_advertisements_are_not_rendered(): void
    {
        Advertisement::query()->where('name', 'Campaña comercial principal')->update([
            'is_active' => false,
        ]);

        $post = Post::query()->with('category')->firstOrFail();

        $this->get(route('posts.show', [$post->category, $post]))
            ->assertOk()
            ->assertDontSee('/images/demo/ad-business.svg', false)
            ->assertSee('/images/demo/ad-community.svg', false);
    }
}
