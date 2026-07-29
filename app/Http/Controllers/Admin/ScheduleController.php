<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Program;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        return view('admin.schedule.index', [
            'schedules' => Schedule::query()->with('program')->orderBy('day_of_week')->orderBy('starts_at')->get()->groupBy('day_of_week'),
            'programs' => Program::query()->where('is_active', true)->orderBy('display_order')->orderBy('title')->get(),
            'days' => $this->days(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $days = collect($data['copy_to_days'] ?? [])->push((int) $data['day_of_week'])->unique();

        DB::transaction(function () use ($request, $data, $days): void {
            foreach ($days as $day) {
                $this->ensureAvailable((int) $day, $data['starts_at'], $data['ends_at']);
                $schedule = Schedule::query()->create([
                    'program_id' => $data['program_id'],
                    'day_of_week' => $day,
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'is_active' => $request->boolean('is_active', true),
                ]);
                $this->log($request, 'schedule.created', $schedule);
            }
        });

        return back()->with('status', 'Horario guardado en la programación.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureAvailable((int) $data['day_of_week'], $data['starts_at'], $data['ends_at'], $schedule);
        $schedule->update([
            'program_id' => $data['program_id'],
            'day_of_week' => $data['day_of_week'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->log($request, 'schedule.updated', $schedule);

        return back()->with('status', 'Horario actualizado.');
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $this->log($request, 'schedule.deleted', $schedule);
        $schedule->delete();

        return back()->with('status', 'Horario eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'copy_to_days' => ['nullable', 'array'],
            'copy_to_days.*' => ['integer', 'between:1,7'],
            'is_active' => ['nullable', 'boolean'],
        ], ['ends_at.after' => 'La hora final debe ser posterior a la hora inicial.']);
    }

    private function ensureAvailable(int $day, string $start, string $end, ?Schedule $except = null): void
    {
        $overlap = Schedule::query()
            ->where('day_of_week', $day)
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'Este horario se cruza con otro espacio activo del mismo día.',
            ]);
        }
    }

    private function days(): array
    {
        return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    }

    private function log(Request $request, string $action, Schedule $schedule): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $schedule->getMorphClass(),
            'subject_id' => $schedule->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
