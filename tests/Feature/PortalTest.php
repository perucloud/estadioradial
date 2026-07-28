<?php

namespace Tests\Feature;

use App\Models\Category;
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
            ->assertSee('Últimas noticias')
            ->assertSee('Programas')
            ->assertSee('Ahora en vivo');
    }

    public function test_news_can_be_opened_from_category_and_post_slugs(): void
    {
        $post = Post::query()->with('category')->firstOrFail();

        $this->get(route('posts.show', [$post->category, $post]))
            ->assertOk()
            ->assertSee($post->title);
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
