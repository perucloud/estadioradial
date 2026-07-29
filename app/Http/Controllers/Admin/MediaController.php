<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Support\MediaProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function library(Request $request): JsonResponse
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $perPage = min(60, max(12, $request->integer('per_page', 48)));
        $mediaItems = Media::query()
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('original_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('credit', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $mediaItems->getCollection()
                ->map(fn (Media $media): array => [
                    'id' => $media->id,
                    'name' => $media->original_name,
                    'alt_text' => $media->alt_text,
                    'caption' => $media->caption,
                    'thumb_url' => $media->url('thumb'),
                    'article_url' => $media->url('article'),
                    'created_at' => $media->created_at?->toIso8601String(),
                ])
                ->values(),
            'meta' => [
                'current_page' => $mediaItems->currentPage(),
                'last_page' => $mediaItems->lastPage(),
                'total' => $mediaItems->total(),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        return view('admin.media.index', [
            'mediaItems' => Media::query()
                ->with('uploader')
                ->when($search !== '', fn ($query) => $query
                    ->where(fn ($query) => $query
                        ->where('original_name', 'like', "%{$search}%")
                        ->orWhere('alt_text', 'like', "%{$search}%")
                        ->orWhere('credit', 'like', "%{$search}%")))
                ->latest()
                ->paginate(24)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function store(Request $request, MediaProcessor $processor): RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:8192',
                'dimensions:max_width=6000,max_height=6000',
            ],
            'alt_texts' => ['required', 'array'],
            'alt_texts.*' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
        ]);

        if (count($data['files']) !== count($data['alt_texts'])) {
            throw ValidationException::withMessages([
                'alt_texts' => 'Cada imagen necesita su propio texto alternativo.',
            ]);
        }

        foreach ($data['files'] as $index => $file) {
            $media = $processor->store($file, [
                'alt_text' => $data['alt_texts'][$index],
                'caption' => $data['caption'] ?? null,
                'credit' => $data['credit'] ?? null,
                'license' => $data['license'] ?? null,
            ], $request->user()->id);

            $this->log($request, 'media.created', $media);
        }

        return back()->with('status', count($data['files']).' archivo(s) añadidos a la biblioteca.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'alt_text' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($data);
        $this->log($request, 'media.updated', $media);

        return back()->with('status', 'Metadatos actualizados.');
    }

    public function destroy(Request $request, Media $media, MediaProcessor $processor): RedirectResponse
    {
        if ($media->isInUse()) {
            return back()->withErrors([
                'media' => 'La imagen está siendo utilizada y no puede eliminarse.',
            ]);
        }

        $this->log($request, 'media.deleted', $media);
        $processor->delete($media);

        return back()->with('status', 'Imagen retirada de la biblioteca.');
    }

    private function log(Request $request, string $action, Media $media): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $media->getMorphClass(),
            'subject_id' => $media->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
