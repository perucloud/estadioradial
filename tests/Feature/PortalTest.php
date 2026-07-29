<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Program;
use Database\Seeders\PortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PortalSeeder::class);
    }

    public function test_home_page_displays_portal_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Noticias Regionales')
            ->assertSee('Programas')
            ->assertSee('Ahora en vivo')
            ->assertSee('Festival reúne música, memoria y tradiciones de distintas regiones')
            ->assertSee('Las noticias más vistas')
            ->assertSee('Conecta tu marca con nuestra audiencia')
            ->assertSee('data-hero-rotator', false)
            ->assertSee('data-hero-interval="8000"', false)
            ->assertSee('data-hero-effect="parallax"', false)
            ->assertSee('data-hero-pause', false)
            ->assertSee('data-news-slider', false)
            ->assertSee('data-slider-mode="automatic"', false)
            ->assertSee('data-slider-interval="6000"', false)
            ->assertSee('data-slider-autoplay-toggle', false)
            ->assertSeeInOrder([
                'aria-label="Facebook"',
                'aria-label="X"',
                'aria-label="TikTok"',
                'aria-label="Instagram"',
                'aria-label="YouTube"',
                'aria-label="Abrir búsqueda"',
                'aria-label="Abrir menú"',
            ], false);
    }

    public function test_most_viewed_news_are_ordered_by_view_count(): void
    {
        $mostViewed = Post::query()->orderByDesc('views_count')->firstOrFail();

        $this->assertSame(
            'mercados-regionales-impulsan-oportunidades-para-productores',
            $mostViewed->slug
        );
        $this->assertSame(5570, $mostViewed->views_count);
    }

    public function test_regional_news_section_only_contains_recent_regional_publications(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $regionalPosts = $response->viewData('regionalPosts');

        $this->assertNotEmpty($regionalPosts);
        $this->assertTrue($regionalPosts->every(
            fn (Post $post) => $post->category->slug === 'regionales'
        ));
        $this->assertSame(
            $regionalPosts->sortByDesc('published_at')->pluck('id')->values()->all(),
            $regionalPosts->pluck('id')->all(),
        );
        $response
            ->assertSee('Información de nuestra región')
            ->assertSee('Ver todas las noticias regionales');
    }

    public function test_menu_and_footer_show_contact_and_dashboard_accesses(): void
    {
        PortalSetting::put('site.contact', [
            'email' => 'redaccion@estacionradial.test',
        ], 'site');

        $response = $this->get('/')->assertOk();
        $content = $response->getContent();

        $response
            ->assertDontSee('<span>Explorar</span>', false)
            ->assertSee('Correo electrónico')
            ->assertSee('Acceder al dashboard')
            ->assertSee('mailto:redaccion@estacionradial.test', false)
            ->assertSee('href="'.route('admin.dashboard').'"', false);

        $this->assertSame(2, substr_count($content, 'mailto:redaccion@estacionradial.test'));
        $this->assertSame(2, substr_count($content, 'href="'.route('admin.dashboard').'"'));
    }

    public function test_news_can_be_searched_from_the_header(): void
    {
        $this->get(route('posts.index', ['q' => 'cuidado responsable']))
            ->assertOk()
            ->assertSee('Resultados para “cuidado responsable”')
            ->assertSee('Campaña ciudadana promueve el cuidado responsable del agua')
            ->assertSee('data-adaptive-sidebar', false);
    }

    public function test_news_can_be_opened_from_category_and_post_slugs(): void
    {
        $post = Post::query()->with('category')->firstOrFail();
        $initialViews = $post->views_count;

        $this->get(route('posts.show', [$post->category, $post]))
            ->assertOk()
            ->assertSee($post->title);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'views_count' => $initialViews + 1,
        ]);
    }

    public function test_post_with_wrong_category_returns_not_found(): void
    {
        $post = Post::query()->firstOrFail();
        $otherCategory = Category::query()->where('id', '!=', $post->category_id)->firstOrFail();

        $this->get(route('posts.show', [$otherCategory, $post]))
            ->assertNotFound();
    }

    public function test_program_schedule_and_live_pages_are_available(): void
    {
        $program = Program::query()->firstOrFail();

        $this->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee($program->title);

        $this->get(route('schedule'))
            ->assertOk()
            ->assertSee('Programación semanal');

        $this->get(route('live'))
            ->assertOk()
            ->assertSee('Transmisión pendiente de configuración');
    }
}
