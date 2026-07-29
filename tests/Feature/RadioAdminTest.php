<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Stream;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        AdminAccess::sync();
        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::query()->where('slug', 'superadmin')->firstOrFail());
    }

    public function test_program_can_be_created_and_assigned_to_a_presenter(): void
    {
        $presenter = User::factory()->create();
        $presenter->roles()->attach(Role::query()->where('slug', 'locutor')->firstOrFail());

        $this->actingAs($this->superadmin)->post(route('admin.programs.store'), [
            'title' => 'Magazine regional',
            'summary' => 'Noticias y entrevistas de toda la región.',
            'description' => 'Un espacio diario con información, invitados y participación ciudadana.',
            'presenter_ids' => [$presenter->id],
            'display_order' => 20,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $program = Program::query()->where('slug', 'magazine-regional')->firstOrFail();
        $this->assertTrue($program->presenters->contains($presenter));
        $this->assertTrue($program->is_active);
    }

    public function test_schedule_rejects_overlaps_and_can_copy_a_slot_to_other_days(): void
    {
        $program = Program::query()->create([
            'title' => 'Primera edición',
            'slug' => 'primera-edicion-test',
            'summary' => 'Resumen informativo.',
            'description' => 'Noticias para comenzar el día.',
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin)->post(route('admin.schedule.store'), [
            'program_id' => $program->id,
            'day_of_week' => 1,
            'copy_to_days' => [2],
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Schedule::query()->count());

        $this->actingAs($this->superadmin)->post(route('admin.schedule.store'), [
            'program_id' => $program->id,
            'day_of_week' => 1,
            'starts_at' => '09:30',
            'ends_at' => '11:00',
            'is_active' => 1,
        ])->assertSessionHasErrors('starts_at');
    }

    public function test_stream_requires_https_and_primary_signal_is_unique(): void
    {
        $this->actingAs($this->superadmin)->post(route('admin.streams.store'), [
            'name' => 'Señal insegura',
            'type' => 'audio',
            'format' => 'mp3',
            'url' => 'http://radio.example/stream',
            'is_active' => 1,
        ])->assertSessionHasErrors('url');

        foreach (['Principal', 'Respaldo'] as $index => $name) {
            $this->actingAs($this->superadmin)->post(route('admin.streams.store'), [
                'name' => $name,
                'type' => 'audio',
                'format' => 'aac',
                'url' => 'https://radio.example/'.$index,
                'is_active' => 1,
                'is_primary' => 1,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(1, Stream::query()->where('type', 'audio')->where('is_primary', true)->count());
        $this->get(route('live'))->assertOk()->assertSee('Respaldo');
    }

    public function test_radio_admin_routes_are_visible_in_sidebar(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('admin.programs.index'))
            ->assertOk()
            ->assertSee('Programación radial')
            ->assertSee('Streaming')
            ->assertDontSee('Programas <small>Próximamente</small>', false);
    }
}
