<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\PostHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorialAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminAccess::sync();
    }

    public function test_editor_can_upload_an_image_with_accessible_metadata_and_variants(): void
    {
        Storage::fake('public');
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('fiesta-patria.jpg', 1200, 800)],
            'alt_texts' => ['Familias celebrando las Fiestas Patrias en la plaza'],
            'credit' => 'Redacción Estación Radial',
            'license' => 'Uso editorial propio',
        ])->assertSessionHasNoErrors();

        $media = Media::query()->firstOrFail();

        $this->assertSame('Familias celebrando las Fiestas Patrias en la plaza', $media->alt_text);
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->variants['thumb']);
        Storage::disk('public')->assertExists($media->variants['article']);
    }

    public function test_editor_can_open_media_library_and_ckeditor_editor(): void
    {
        $editor = $this->userWithRole('editor');
        $this->category();
        $this->media($editor);

        $this->actingAs($editor)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Biblioteca reutilizable')
            ->assertSee('Imagen referencial de la noticia');

        $this->actingAs($editor)
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertSee('data-ckeditor', false)
            ->assertSee('CKEditor 5')
            ->assertSee('Copia local activa')
            ->assertSee('Biblioteca Media')
            ->assertSee('data-ckeditor-character-count', false)
            ->assertSee('Imagen destacada')
            ->assertSee('Seleccionar imagen destacada')
            ->assertSee('data-media-picker', false)
            ->assertSee(route('admin.media.library'), false);

        $this->actingAs($editor)
            ->getJson(route('admin.media.library'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.alt_text', 'Imagen referencial de la noticia')
            ->assertJsonStructure(['data' => [['id', 'thumb_url', 'article_url', 'alt_text']]]);
    }

    public function test_superadmin_sidebar_has_flyout_navigation_and_planned_modules(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('data-admin-nav-group', false)
            ->assertSee('Crear noticia')
            ->assertSee('Todas las noticias')
            ->assertSee('Categorías')
            ->assertSee('Media')
            ->assertSee('Programación radial')
            ->assertSee('Programas')
            ->assertSee('Locutores')
            ->assertSee('Publicidad')
            ->assertSee('Banners Pub')
            ->assertSee('Apariencia')
            ->assertSee('Configurar')
            ->assertSee('Ajustes')
            ->assertSee('admin-nav-flyout--columns', false);
    }

    public function test_upload_rejects_an_image_without_alt_text(): void
    {
        Storage::fake('public');
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('sin-descripcion.jpg')],
            'alt_texts' => [''],
        ])->assertSessionHasErrors('alt_texts.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_editor_can_create_a_sanitized_draft_with_ckeditor_html(): void
    {
        $editor = $this->userWithRole('editor');
        $category = $this->category();
        $media = $this->media($editor);

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Nueva agenda cultural para la provincia',
            'slug' => '',
            'excerpt' => 'La programación reúne actividades culturales para toda la familia.',
            'body' => '<h2>Agenda cultural</h2><p>La municipalidad presentó una programación completa para este mes.</p><script>alert("xss")</script>',
            'category_id' => $category->id,
            'media_id' => $media->id,
            'intent' => 'draft',
        ])->assertRedirect();

        $post = Post::query()->firstOrFail();

        $this->assertSame('draft', $post->status);
        $this->assertSame($editor->id, $post->created_by);
        $this->assertStringContainsString('<h2>Agenda cultural</h2>', $post->body);
        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertSame($media->id, $post->media_id);
    }

    public function test_editor_without_publish_permission_cannot_publish(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Reporte regional de servicios públicos',
            'excerpt' => 'Autoridades informaron sobre el avance de los servicios públicos.',
            'body' => '<p>El reporte regional presenta información detallada y verificable para la ciudadanía.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($editor)->id,
            'intent' => 'publish',
        ])->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_superadmin_can_publish_archive_and_restore_a_post(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Informe nacional de infraestructura pública',
            'excerpt' => 'El informe presenta avances y próximos proyectos de infraestructura.',
            'body' => '<p>Las autoridades presentaron información completa sobre los proyectos y sus próximos plazos.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'intent' => 'publish',
        ])->assertRedirect();

        $post = Post::query()->firstOrFail();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);

        $this->actingAs($superadmin)
            ->post(route('admin.posts.archive', $post))
            ->assertSessionHas('status');
        $this->assertSame('archived', $post->refresh()->status);

        $this->actingAs($superadmin)
            ->post(route('admin.posts.restore', $post))
            ->assertSessionHas('status');
        $this->assertSame('draft', $post->refresh()->status);
        $this->assertNull($post->published_at);
    }

    public function test_publish_now_ignores_a_programming_date_in_the_past(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Publicación inmediata sin conflicto de programación',
            'excerpt' => 'La noticia debe publicarse ahora aunque el control de programación conserve una fecha anterior.',
            'body' => '<p>Este contenido editorial verifica que la fecha solo se valide al utilizar la acción Programar.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'scheduled_for' => now()->subHour()->format('Y-m-d\TH:i'),
            'intent' => 'publish',
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();

        $this->assertSame('published', $post->status);
        $this->assertNull($post->scheduled_for);
    }

    public function test_scheduling_uses_a_clear_spanish_message_for_a_past_date(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Noticia pendiente de programación',
            'excerpt' => 'La validación debe explicar claramente cuándo la fecha de programación no es válida.',
            'body' => '<p>Contenido editorial suficiente para comprobar el mensaje de validación de la fecha programada.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'scheduled_for' => now()->subMinute()->format('Y-m-d\TH:i'),
            'intent' => 'schedule',
        ])->assertSessionHasErrors([
            'scheduled_for' => 'La fecha de programación debe ser posterior a la hora actual.',
        ]);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_news_list_supports_page_sizes_and_soft_delete_actions(): void
    {
        $editor = $this->userWithRole('editor');
        $category = $this->category();

        foreach (range(1, 24) as $number) {
            Post::query()->create([
                'category_id' => $category->id,
                'title' => "Noticia de prueba {$number}",
                'slug' => "noticia-de-prueba-{$number}",
                'excerpt' => 'Resumen para comprobar la paginación administrativa.',
                'body' => '<p>Contenido editorial suficiente para la noticia de prueba.</p>',
                'status' => 'draft',
            ]);
        }

        $response = $this->actingAs($editor)
            ->get(route('admin.posts.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('10 por página')
            ->assertSee('images/admin/icons/vista.png', false)
            ->assertSee('images/admin/icons/editar.png', false)
            ->assertSee('images/admin/icons/eliminar2.png', false);

        $this->assertSame(10, $response->viewData('posts')->perPage());
        $this->assertSame(24, $response->viewData('posts')->total());

        $post = Post::query()->firstOrFail();
        $this->actingAs($editor)
            ->delete(route('admin.posts.destroy', $post))
            ->assertSessionHas('status', 'Noticia enviada a la papelera.');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        $this->actingAs($editor)
            ->get(route('admin.posts.index', ['status' => 'trash']))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('Restaurar');

        $this->actingAs($editor)
            ->post(route('admin.posts.restore-deleted', $post))
            ->assertSessionHas('status', 'Noticia restaurada desde la papelera.');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_professional_editor_html_keeps_safe_formatting_and_rejects_unsafe_embeds(): void
    {
        $sanitizer = app(PostHtmlSanitizer::class);
        $html = <<<'HTML'
            <h2 style="text-align: center; position: fixed">Titular</h2>
            <p style="margin-left: 40px"><span style="color: hsl(357, 73%, 45%); background-image: url(javascript:alert(1))">Texto</span></p>
            <mark style="background-color: #fff2a8">Dato</mark>
            <pre><code>echo "seguro";</code></pre>
            <figure class="image image_resized clase-insegura" style="width: 75%; position: fixed"><img src="/storage/media/noticia.webp" alt="Noticia"><figcaption>Crédito</figcaption></figure>
            <figure class="media"><div data-oembed-url="https://vimeo.com/123"><iframe src="https://player.vimeo.com/video/123" width="800" height="450"></iframe></div></figure>
            <figure class="media"><div data-oembed-url="https://example.com/unsafe"><iframe src="https://example.com/embed/unsafe"></iframe></div></figure>
            HTML;

        $clean = $sanitizer->sanitize($html);

        $this->assertStringContainsString('text-align: center', $clean);
        $this->assertStringContainsString('color: hsl(357, 73%, 45%)', $clean);
        $this->assertStringContainsString('background-color: #fff2a8', $clean);
        $this->assertStringContainsString('margin-left: 40px', $clean);
        $this->assertStringContainsString('class="image image_resized"', $clean);
        $this->assertStringContainsString('width: 75%', $clean);
        $this->assertStringContainsString(
            '<pre><code>echo "seguro";</code></pre>',
            html_entity_decode($clean, ENT_QUOTES | ENT_HTML5),
        );
        $this->assertStringContainsString('player.vimeo.com/video/123', $clean);
        $this->assertStringNotContainsString('position:', $clean);
        $this->assertStringNotContainsString('background-image', $clean);
        $this->assertStringNotContainsString('clase-insegura', $clean);
        $this->assertStringNotContainsString('example.com', $clean);
    }

    public function test_scheduled_posts_are_published_when_their_time_arrives(): void
    {
        $post = Post::query()->create([
            'category_id' => $this->category()->id,
            'title' => 'Noticia programada',
            'slug' => 'noticia-programada',
            'excerpt' => 'Resumen de una noticia programada.',
            'body' => '<p>Contenido completo de la noticia que será publicada automáticamente.</p>',
            'status' => 'scheduled',
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNull($post->scheduled_for);
    }

    public function test_media_in_use_cannot_be_deleted(): void
    {
        $editor = $this->userWithRole('editor');
        $media = $this->media($editor);

        Post::query()->create([
            'category_id' => $this->category()->id,
            'media_id' => $media->id,
            'title' => 'Noticia con imagen',
            'slug' => 'noticia-con-imagen',
            'excerpt' => 'Resumen de la noticia con imagen.',
            'body' => '<p>Contenido suficiente para comprobar la protección de la biblioteca.</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($editor)
            ->delete(route('admin.media.destroy', $media))
            ->assertSessionHasErrors('media');

        $this->assertDatabaseHas('media', ['id' => $media->id, 'deleted_at' => null]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }

    private function category(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'cultura'],
            ['name' => 'Cultura', 'description' => 'Noticias culturales'],
        );
    }

    private function media(User $user): Media
    {
        return Media::query()->create([
            'disk' => 'public',
            'path' => 'media/tests/original.jpg',
            'variants' => ['thumb' => 'media/tests/thumb.webp', 'article' => 'media/tests/article.webp'],
            'original_name' => 'referencial.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'width' => 1200,
            'height' => 800,
            'alt_text' => 'Imagen referencial de la noticia',
            'checksum' => str_repeat('a', 64),
            'uploaded_by' => $user->id,
        ]);
    }
}
