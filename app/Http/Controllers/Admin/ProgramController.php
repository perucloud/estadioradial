<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.programs.index', [
            'programs' => Program::query()
                ->with(['media', 'presenters'])
                ->withCount('schedules')
                ->orderBy('display_order')
                ->orderBy('title')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $program = DB::transaction(function () use ($request, $data): Program {
            $program = Program::query()->create($this->attributes($data, $request));
            $program->presenters()->sync($data['presenter_ids'] ?? []);
            $this->log($request, 'program.created', $program);

            return $program;
        });

        return redirect()->route('admin.programs.edit', $program)->with('status', 'Programa creado correctamente.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.form', $this->formData($program->load('presenters')));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $data = $this->validated($request, $program);
        DB::transaction(function () use ($request, $data, $program): void {
            $program->update($this->attributes($data, $request));
            $program->presenters()->sync($data['presenter_ids'] ?? []);
            $this->log($request, 'program.updated', $program);
        });

        return back()->with('status', 'Programa actualizado.');
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        abort_if($program->schedules()->exists(), 422, 'Retira primero los horarios de este programa.');
        $this->log($request, 'program.deleted', $program);
        $program->delete();

        return back()->with('status', 'Programa enviado a la papelera.');
    }

    private function formData(?Program $program = null): array
    {
        return [
            'program' => $program,
            'mediaItems' => Media::query()->latest()->limit(100)->get(),
            'presenters' => User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('slug', 'locutor'))
                ->orderBy('name')
                ->get(),
        ];
    }

    private function validated(Request $request, ?Program $program = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('title')),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('programs')->ignore($program)],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:10000'],
            'hosts' => ['nullable', 'string', 'max:500'],
            'media_id' => ['nullable', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'presenter_ids' => ['nullable', 'array', 'max:20'],
            'presenter_ids.*' => ['integer', Rule::exists('users', 'id')->where('is_active', true)],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Escribe el nombre del programa.',
            'summary.required' => 'Escribe un resumen del programa.',
            'description.required' => 'Escribe la descripción del programa.',
        ]);
    }

    private function attributes(array $data, Request $request): array
    {
        $media = isset($data['media_id']) ? Media::query()->find($data['media_id']) : null;

        return [
            'title' => $data['title'],
            'slug' => Str::slug($data['slug'] ?: $data['title']),
            'summary' => $data['summary'],
            'description' => $data['description'],
            'hosts' => $data['hosts'] ?? null,
            'media_id' => $media?->id,
            'image' => $media?->url('article'),
            'display_order' => (int) ($data['display_order'] ?? 100),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function log(Request $request, string $action, Program $program): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $program->getMorphClass(),
            'subject_id' => $program->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
