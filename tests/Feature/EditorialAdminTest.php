<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Media;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\PostHtmlSanitizer;
use App\Support\SchedulerHealth;
use Database\Seeders\LocationSeeder;
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

    public function test_editor_can_upload_and_receive_a_media_item_inside_the_picker(): void
    {
        Storage::fake('public');
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->image('portada-ministerio.jpg', 1200, 800)],
                'caption' => 'Ceremonia oficial',
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.name', 'portada-ministerio.jpg')
            ->assertJsonPath('data.0.alt_text', 'Portada ministerio')
            ->assertJsonStructure(['data' => [['id', 'thumb_url', 'article_url']]]);

        $this->assertDatabaseCount('media', 1);
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

        $formResponse = $this->actingAs($editor)
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
            ->assertSee('Añadir nueva imagen')
            ->assertSee('data-media-picker-url', false)
            ->assertSee('data-media-picker-upload', false)
            ->assertSee('La carga comenzará automáticamente')
            ->assertDontSee('Subir y seleccionar')
            ->assertSee('data-excerpt-input', false)
            ->assertSee('data-publication-datetime', false)
            ->assertSee('data-auto-datetime="true"', false)
            ->assertSee(route('admin.media.library'), false);

        $this->assertLessThan(
            strpos($formResponse->getContent(), 'data-excerpt-input'),
            strpos($formResponse->getContent(), 'data-ckeditor'),
        );

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
            ->assertSee('Streaming')
            ->assertSee('Configuración del portal')
            ->assertSee('Apariencia')
            ->assertSee('Configurar')
            ->assertSee('Ajustes')
            ->assertSee('admin-nav-flyout--columns', false);
    }

    public function test_upload_generates_alt_text_from_filename_when_it_is_omitted(): void
    {
        Storage::fake('public');
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('sin-descripcion.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('media', [
            'original_name' => 'sin-descripcion.jpg',
            'alt_text' => 'Sin descripcion',
            'credit' => null,
        ]);
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

    public function test_excerpt_is_generated_from_editor_content_when_omitted(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Reporte ministerial con resumen automático',
            'body' => '<p>El Ministerio del Interior presentó las nuevas acciones de seguridad ciudadana.</p><p>El plan incorpora coordinación regional y seguimiento permanente.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($editor)->id,
            'intent' => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'El Ministerio del Interior presentó las nuevas acciones de seguridad ciudadana. El plan incorpora coordinación regional y seguimiento permanente.',
            Post::query()->firstOrFail()->excerpt,
        );
    }

    public function test_post_form_uses_free_tags_and_automatic_seo_controls(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertSee('name="tag_names"', false)
            ->assertDontSee('name="tag_ids[]"', false)
            ->assertSee('data-tag-suggestions', false)
            ->assertSee('data-seo-title-input', false)
            ->assertSee('data-seo-description-input', false);
    }

    public function test_tags_and_seo_are_generated_when_a_post_is_published(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $category = $this->category();
        $media = $this->media($superadmin);
        $title = 'Ministerio del Interior presenta una estrategia nacional';
        $excerpt = 'El nuevo plan articula medidas de seguridad ciudadana en distintas regiones del país.';

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => $title,
            'excerpt' => $excerpt,
            'body' => '<p>El Ministerio del Interior presentó una estrategia integral con participación de autoridades regionales.</p>',
            'category_id' => $category->id,
            'media_id' => $media->id,
            'tag_names' => 'Seguridad ciudadana, Ministerio del Interior, seguridad ciudadana',
            'intent' => 'publish',
        ])->assertSessionHasNoErrors();

        $post = Post::query()->with('tags')->firstOrFail();

        $this->assertSame($title, $post->seo_title);
        $this->assertSame($excerpt, $post->seo_description);
        $this->assertCount(2, $post->tags);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.published',
            'subject_id' => $post->id,
        ]);
        $this->assertEqualsCanonicalizing(
            ['Seguridad ciudadana', 'Ministerio del Interior'],
            $post->tags->pluck('name')->all(),
        );

        $this->get(route('posts.show', [$category, $post]))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="'.$title.'">', false)
            ->assertSee('<meta property="og:description" content="'.$excerpt.'">', false)
            ->assertSee('<meta property="og:image" content="'.url($media->url('article')).'">', false);
    }

    public function test_news_can_store_an_optional_geographic_hierarchy(): void
    {
        $this->seed(LocationSeeder::class);
        $editor = $this->userWithRole('editor');
        $country = Location::query()->where('slug', 'peru')->firstOrFail();
        $region = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();
        $province = Location::query()->where('slug', 'mariscal-nieto')->firstOrFail();
        $district = Location::query()->where('slug', 'carumas')->firstOrFail();

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Carumas presenta una nueva agenda cultural',
            'excerpt' => 'La programación reúne actividades dirigidas a las familias del distrito.',
            'body' => '<p>Las autoridades y organizaciones locales presentaron una agenda cultural para las próximas semanas.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($editor)->id,
            'location_country_id' => $country->id,
            'location_region_id' => $region->id,
            'location_province_id' => $province->id,
            'location_district_id' => $district->id,
            'intent' => 'draft',
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();
        $this->assertSame($district->id, $post->location_id);
        $this->assertSame('Perú → Moquegua → Mariscal Nieto → Carumas', $post->location->fullName());

        $this->actingAs($editor)
            ->get(route('admin.posts.edit', $post))
            ->assertOk()
            ->assertSee('Alcance geográfico')
            ->assertSee('Perú → Moquegua → Mariscal Nieto → Carumas')
            ->assertSee('name="location_district_id"', false);
    }

    public function test_dashboard_configures_the_default_geographic_scope_for_new_news(): void
    {
        $this->seed(LocationSeeder::class);
        $superadmin = $this->userWithRole('superadmin');
        $country = Location::query()->where('slug', 'peru')->where('type', 'country')->firstOrFail();
        $region = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();
        $province = Location::query()->where('slug', 'mariscal-nieto')->where('type', 'province')->firstOrFail();
        $district = Location::query()->where('slug', 'carumas')->where('type', 'district')->firstOrFail();

        $dashboard = $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Alcance geográfico predeterminado')
            ->assertSee('Perú')
            ->assertSee('Moquegua')
            ->assertSee('Guardar ubicación predeterminada');

        $this->assertSame([
            'country' => $country->id,
            'region' => $region->id,
        ], $dashboard->viewData('defaultLocationSelection'));

        $newPost = $this->actingAs($superadmin)
            ->get(route('admin.posts.create'))
            ->assertOk();
        $this->assertSame([
            'country' => $country->id,
            'region' => $region->id,
        ], $newPost->viewData('locationSelection'));

        $this->actingAs($superadmin)
            ->put(route('admin.dashboard.default-location.update'), [
                'default_location_country_id' => $country->id,
                'default_location_region_id' => $region->id,
                'default_location_province_id' => $province->id,
                'default_location_district_id' => $district->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Ubicación predeterminada actualizada.');

        $this->assertSame([
            'country' => $country->id,
            'region' => $region->id,
            'province' => $province->id,
            'district' => $district->id,
        ], PortalSetting::value('site.default_location'));

        $newPost = $this->actingAs($superadmin)->get(route('admin.posts.create'));
        $this->assertSame($district->id, $newPost->viewData('locationSelection')['district']);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'settings.default_location_updated',
        ]);

        $otherCountry = Location::query()
            ->where('type', 'country')
            ->where('slug', 'argentina')
            ->firstOrFail();
        $this->actingAs($superadmin)
            ->put(route('admin.dashboard.default-location.update'), [
                'default_location_country_id' => $otherCountry->id,
                'default_location_region_id' => $region->id,
            ])
            ->assertSessionHasErrors('default_location_region_id');
    }

    public function test_news_can_stop_at_country_and_rejects_an_inconsistent_location_path(): void
    {
        $this->seed(LocationSeeder::class);
        $editor = $this->userWithRole('editor');
        $country = Location::query()->where('slug', 'peru')->firstOrFail();
        $region = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();
        $wrongProvince = Location::query()->where('slug', 'ilo')->where('type', 'province')->firstOrFail();
        $district = Location::query()->where('slug', 'carumas')->firstOrFail();

        $base = [
            'title' => 'Reporte territorial de servicios públicos',
            'excerpt' => 'El reporte presenta información territorial para la ciudadanía.',
            'body' => '<p>El documento reúne información suficiente sobre servicios y atención a la población.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($editor)->id,
            'location_country_id' => $country->id,
            'intent' => 'draft',
        ];

        $this->actingAs($editor)
            ->post(route('admin.posts.store'), $base)
            ->assertSessionHasNoErrors();

        $this->assertSame($country->id, Post::query()->firstOrFail()->location_id);

        $this->actingAs($editor)->post(route('admin.posts.store'), array_replace($base, [
            'title' => 'Ruta territorial inválida',
            'location_region_id' => $region->id,
            'location_province_id' => $wrongProvince->id,
            'location_district_id' => $district->id,
        ]))->assertSessionHasErrors('location_district_id');
    }

    public function test_a_location_used_by_news_cannot_be_deleted(): void
    {
        $this->seed(LocationSeeder::class);
        $superadmin = $this->userWithRole('superadmin');
        $district = Location::query()->where('slug', 'carumas')->firstOrFail();

        Post::query()->create([
            'category_id' => $this->category()->id,
            'location_id' => $district->id,
            'title' => 'Noticia territorial protegida',
            'slug' => 'noticia-territorial-protegida',
            'excerpt' => 'Resumen de una noticia vinculada con Carumas.',
            'body' => '<p>Contenido editorial suficiente para proteger la ubicación relacionada.</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($superadmin)
            ->delete(route('admin.locations.destroy', $district))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseHas('locations', [
            'id' => $district->id,
            'deleted_at' => null,
        ]);
    }

    public function test_news_list_can_filter_publications_without_location(): void
    {
        $editor = $this->userWithRole('editor');
        $category = $this->category();
        $region = Location::query()->where('slug', 'moquegua')->where('type', 'region')->firstOrFail();

        Post::query()->create([
            'category_id' => $category->id,
            'title' => 'Noticia pendiente de ubicación',
            'slug' => 'noticia-pendiente-ubicacion',
            'excerpt' => 'Contenido que todavía requiere clasificación territorial.',
            'body' => '<p>Contenido editorial.</p>',
            'status' => 'draft',
        ]);
        Post::query()->create([
            'category_id' => $category->id,
            'location_id' => $region->id,
            'title' => 'Noticia ubicada en Moquegua',
            'slug' => 'noticia-ubicada-moquegua',
            'excerpt' => 'Contenido con ubicación territorial confirmada.',
            'body' => '<p>Contenido editorial.</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($editor)
            ->get(route('admin.posts.index', ['location' => 'none']))
            ->assertOk()
            ->assertSee('Sin ubicación')
            ->assertSee('Noticia pendiente de ubicación')
            ->assertDontSee('Noticia ubicada en Moquegua');
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

    public function test_published_news_has_clear_update_visibility_and_unpublish_actions(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $category = $this->category();
        $media = $this->media($superadmin);
        $payload = [
            'title' => 'Noticia para administrar su publicación',
            'excerpt' => 'Resumen suficiente para comprobar el flujo editorial de una noticia publicada.',
            'body' => '<p>Contenido editorial completo para comprobar actualización, portada y despublicación.</p>',
            'category_id' => $category->id,
            'media_id' => $media->id,
        ];

        $this->actingAs($superadmin)
            ->post(route('admin.posts.store'), $payload + ['intent' => 'publish'])
            ->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();
        $originalPublishedAt = $post->published_at?->copy();

        $this->actingAs($superadmin)
            ->get(route('admin.posts.edit', $post))
            ->assertOk()
            ->assertSee('Publicada')
            ->assertSee('Actualizar noticia')
            ->assertSee('button--update', false)
            ->assertSee('Ocultar de portada')
            ->assertSee('Despublicar')
            ->assertSee('data-open-schedule-modal', false)
            ->assertSee('data-schedule-modal', false)
            ->assertSee('Confirmar programación')
            ->assertDontSee('Enviar a revisión')
            ->assertDontSee('name="intent" value="publish"', false);

        $this->actingAs($superadmin)
            ->put(route('admin.posts.update', $post), $payload + ['intent' => 'preserve'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Noticia actualizada.');

        $this->assertSame(
            $originalPublishedAt?->toDateTimeString(),
            $post->refresh()->published_at?->toDateTimeString(),
        );

        $this->actingAs($superadmin)
            ->put(route('admin.posts.update', $post), $payload + ['intent' => 'hide_home'])
            ->assertSessionHas('status', 'Noticia ocultada de la portada.');
        $this->assertTrue($post->refresh()->is_homepage_hidden);
        $this->assertSame('published', $post->status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.hidden_from_home',
            'subject_id' => $post->id,
        ]);

        $this->actingAs($superadmin)
            ->put(route('admin.posts.update', $post), $payload + ['intent' => 'show_home'])
            ->assertSessionHas('status', 'Noticia mostrada en la portada.');
        $this->assertFalse($post->refresh()->is_homepage_hidden);

        $this->actingAs($superadmin)
            ->put(route('admin.posts.update', $post), $payload + ['intent' => 'unpublish'])
            ->assertSessionHas('status', 'Noticia despublicada y guardada como borrador.');

        $this->assertSame('draft', $post->refresh()->status);
        $this->assertNull($post->published_at);
        $this->assertNull($post->scheduled_for);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.unpublished',
            'subject_id' => $post->id,
        ]);
    }

    public function test_publish_now_accepts_a_present_or_past_editorial_date(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $publicationDate = now()->subDay()->startOfMinute();

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Publicación inmediata sin conflicto de programación',
            'excerpt' => 'La noticia debe publicarse ahora utilizando la fecha editorial elegida por el administrador.',
            'body' => '<p>Este contenido verifica que una publicación manual pueda registrar una fecha presente o anterior.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'published_at' => $publicationDate->format('Y-m-d\TH:i'),
            'intent' => 'publish',
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();

        $this->assertSame('published', $post->status);
        $this->assertNull($post->scheduled_for);
        $this->assertSame(
            $publicationDate->toDateTimeString(),
            $post->published_at?->toDateTimeString(),
        );
    }

    public function test_programming_uses_a_future_date_independent_from_the_editorial_date(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $scheduledFor = now()->addDay()->startOfMinute();

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Noticia programada desde el calendario editorial',
            'excerpt' => 'La noticia queda pendiente hasta la fecha futura confirmada desde el modal.',
            'body' => '<p>Contenido suficiente para validar la programación futura independiente de la fecha editorial.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'published_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'scheduled_for' => $scheduledFor->format('Y-m-d\TH:i'),
            'intent' => 'schedule',
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();
        $this->assertSame('scheduled', $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame(
            $scheduledFor->toDateTimeString(),
            $post->scheduled_for?->toDateTimeString(),
        );
    }

    public function test_publish_now_rejects_a_future_editorial_date(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)->post(route('admin.posts.store'), [
            'title' => 'Noticia con fecha editorial futura inválida',
            'excerpt' => 'La fecha futura debe seleccionarse exclusivamente mediante la acción Programar.',
            'body' => '<p>Contenido editorial suficiente para comprobar la separación de los dos controles de fecha.</p>',
            'category_id' => $this->category()->id,
            'media_id' => $this->media($superadmin)->id,
            'published_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'intent' => 'publish',
        ])->assertSessionHasErrors([
            'published_at' => 'La fecha de publicación debe ser la hora actual o una fecha anterior.',
        ]);

        $this->assertDatabaseCount('posts', 0);
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
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.published_scheduled',
            'subject_id' => $post->id,
        ]);

        $scheduler = app(SchedulerHealth::class)->snapshot();
        $this->assertTrue($scheduler['active']);
        $this->assertSame(1, $scheduler['published_count']);

        $superadmin = $this->userWithRole('superadmin');
        $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Publicaciones programadas')
            ->assertSee('Activo')
            ->assertSee('Noticias vencidas pendientes');
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
