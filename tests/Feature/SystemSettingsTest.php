<?php

namespace Tests\Feature;

use App\Models\PortalSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\PortalSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
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

    public function test_configuration_and_system_sections_are_available(): void
    {
        foreach (['identity', 'contact', 'social', 'colors', 'seo'] as $section) {
            $this->actingAs($this->superadmin)
                ->get(route('admin.settings.configure', $section))
                ->assertOk()
                ->assertSee('Guardar configuración');
        }

        foreach (['regional', 'smtp', 'cache', 'maintenance', 'backups', 'security'] as $section) {
            $this->actingAs($this->superadmin)
                ->get(route('admin.settings.system', $section))
                ->assertOk();
        }
    }

    public function test_sidebar_uses_grouped_configuration_options(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.settings.configure', 'identity'))
            ->assertOk()
            ->assertSee('Identidad')
            ->assertSee('Contacto')
            ->assertSee('Redes sociales')
            ->assertSee('SEO');

        $response->assertDontSee(route('admin.settings.configure', 'colors'), false);

        $this->actingAs($this->superadmin)
            ->get(route('admin.settings.system', 'regional'))
            ->assertOk()
            ->assertSee('Regionalización')
            ->assertSee('SMTP')
            ->assertSee('Caché')
            ->assertSee('Mantenimiento')
            ->assertSee('Respaldos')
            ->assertSee('Seguridad')
            ->assertDontSee(route('admin.settings.system', 'regional').'#idioma', false)
            ->assertDontSee(route('admin.settings.system', 'regional').'#zona-horaria', false)
            ->assertDontSee(route('admin.settings.system', 'regional').'#formato-fecha', false);
    }

    public function test_identity_and_security_settings_are_persisted(): void
    {
        $this->actingAs($this->superadmin)
            ->put(route('admin.settings.configure.update', 'identity'), [
                'name' => 'Radio Juliaca',
                'slogan' => 'La voz del altiplano',
                'frequency' => '99.3 FM',
            ])
            ->assertSessionHas('status');

        $this->assertSame('Radio Juliaca', PortalSetting::value('site.identity')['name']);

        $this->actingAs($this->superadmin)
            ->put(route('admin.settings.system.update', 'security'), [
                'captcha_enabled' => '1',
                'max_attempts' => 6,
                'lock_minutes' => 20,
                'session_lifetime' => 180,
                'password_min' => 10,
                'password_mixed_case' => '1',
                'password_numbers' => '1',
            ])
            ->assertSessionHas('status');

        $this->assertSame(10, PortalSetting::value('system.security')['password_min']);
    }

    public function test_public_layout_uses_configured_identity_and_seo(): void
    {
        PortalSetting::put('site.identity', [
            'name' => 'Radio del Sur',
            'slogan' => 'Información que conecta',
            'frequency' => '',
            'logo_media_id' => null,
        ], 'site');
        PortalSetting::put('site.seo', [
            'title' => 'Noticias del Sur',
            'description' => 'Información regional y nacional.',
            'keywords' => 'radio, noticias',
            'canonical_url' => '',
            'robots_index' => true,
            'og_media_id' => null,
        ], 'site');

        PortalSettings::flush();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Radio del Sur')
            ->assertSee('Información que conecta')
            ->assertSee('radio, noticias');
    }
}
