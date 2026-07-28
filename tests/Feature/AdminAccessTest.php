<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminAccess::sync();
    }

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_login_page_renders_in_utf8_with_math_challenge(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('Verificación')
            ->assertSee('Estación Radial');
    }

    public function test_login_requires_a_valid_one_time_math_captcha(): void
    {
        $user = $this->userWithRole('superadmin', ['password' => Hash::make('Temporal123')]);

        $this->withSession([
            'admin_login_captcha' => [
                'question' => '4 + 5',
                'answer' => 9,
                'expires_at' => now()->addMinute()->timestamp,
            ],
        ])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Temporal123',
            'captcha' => 8,
        ])->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }

    public function test_new_user_must_change_password_after_login(): void
    {
        $user = $this->userWithRole('superadmin', [
            'password' => Hash::make('Temporal123'),
            'must_change_password' => true,
        ]);

        $this->withSession([
            'admin_login_captcha' => [
                'question' => '4 + 5',
                'answer' => 9,
                'expires_at' => now()->addMinute()->timestamp,
            ],
        ])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Temporal123',
            'captcha' => 9,
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.password.change'));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->userWithRole('superadmin', [
            'password' => Hash::make('Temporal123'),
            'is_active' => false,
        ]);

        $this->withSession([
            'admin_login_captcha' => [
                'question' => '4 + 5',
                'answer' => 9,
                'expires_at' => now()->addMinute()->timestamp,
            ],
        ])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Temporal123',
            'captcha' => 9,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_editor_cannot_open_user_management(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_open_the_dashboard(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Resumen del portal')
            ->assertSee('Señales activas');
    }

    public function test_admin_can_create_editor_but_not_another_admin(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Editora Regional',
            'email' => 'editora@example.com',
            'role' => 'editor',
            'password' => 'Temporal123',
            'password_confirmation' => 'Temporal123',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'editora@example.com']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Administrador no autorizado',
            'email' => 'admin2@example.com',
            'role' => 'admin',
            'password' => 'Temporal123',
            'password_confirmation' => 'Temporal123',
        ])->assertForbidden();
    }

    public function test_password_recovery_response_does_not_disclose_accounts(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertSessionHas('status');

        $this->assertStringNotContainsString(
            'unknown@example.com',
            (string) session('status'),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
