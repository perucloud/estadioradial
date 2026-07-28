<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
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

    public function test_editor_can_open_media_library_and_tiptap_editor(): void
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
            ->assertSee('data-tiptap', false)
            ->assertSee('Copia local activa')
            ->assertSee('Imagen principal');
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

    public function test_editor_can_create_a_sanitized_draft_with_tiptap_html(): void
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
