<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Stream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function index(): View
    {
        return view('admin.streams.index', [
            'streams' => Stream::query()->with('media')->orderBy('type')->orderBy('sort_order')->get(),
            'mediaItems' => Media::query()->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data): void {
            $stream = Stream::query()->create($this->attributes($request, $data));
            $this->normalizePrimary($stream);
            $this->log($request, 'stream.created', $stream);
        });

        return back()->with('status', 'Señal creada correctamente.');
    }

    public function update(Request $request, Stream $stream): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data, $stream): void {
            $stream->update($this->attributes($request, $data));
            $this->normalizePrimary($stream);
            $this->log($request, 'stream.updated', $stream);
        });

        return back()->with('status', 'Configuración de señal actualizada.');
    }

    public function destroy(Request $request, Stream $stream): RedirectResponse
    {
        $this->log($request, 'stream.deleted', $stream);
        $stream->delete();

        return back()->with('status', 'Señal enviada a la papelera.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['audio', 'video'])],
            'format' => [
                'required',
                Rule::in($request->input('type') === 'audio'
                    ? ['mp3', 'aac', 'hls']
                    : ['hls', 'youtube', 'iframe']),
            ],
            'url' => ['nullable', 'url:https', 'max:2000'],
            'media_id' => ['nullable', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'fallback_message' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
        ], [
            'url.url' => 'La señal debe utilizar una URL HTTPS válida.',
        ]);
    }

    private function attributes(Request $request, array $data): array
    {
        $media = isset($data['media_id']) ? Media::query()->find($data['media_id']) : null;

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'format' => $data['format'],
            'url' => $data['url'] ?? null,
            'media_id' => $media?->id,
            'cover' => $media?->url('article'),
            'fallback_message' => $data['fallback_message'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 100),
            'is_active' => $request->boolean('is_active'),
            'is_primary' => $request->boolean('is_primary'),
        ];
    }

    private function normalizePrimary(Stream $stream): void
    {
        if ($stream->is_primary) {
            Stream::query()->where('type', $stream->type)->whereKeyNot($stream->id)->update(['is_primary' => false]);
        }
    }

    private function log(Request $request, string $action, Stream $stream): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $stream->getMorphClass(),
            'subject_id' => $stream->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
