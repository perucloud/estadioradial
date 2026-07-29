<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    public function index(): View
    {
        return view('admin.advertisements.index', [
            'advertisements' => Advertisement::query()->with('media')->orderBy('placement')->orderBy('sort_order')->get(),
            'mediaItems' => Media::query()->latest()->limit(100)->get(),
            'placements' => $this->placements(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Advertisement::query()->create($this->validated($request));

        return back()->with('status', 'Publicidad creada.');
    }

    public function update(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $advertisement->update($this->validated($request));

        return back()->with('status', 'Publicidad actualizada.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        $advertisement->delete();

        return back()->with('status', 'Publicidad enviada a la papelera.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'placement' => ['required', Rule::in(array_keys($this->placements()))],
            'media_id' => ['required', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'destination_url' => ['nullable', 'url:http,https', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        $media = Media::query()->findOrFail($data['media_id']);

        return $data + [
            'image' => $media->url('article'),
            'alt_text' => $data['alt_text'] ?: ($media->alt_text ?: $data['name']),
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function placements(): array
    {
        return [
            'home_news' => 'Portada · noticias regionales',
            'article_sidebar' => 'Sidebar de noticia',
            'section_sidebar' => 'Sidebar de secciones',
            'article_inline' => 'Interior de noticia',
            'programs' => 'Programas y programación',
        ];
    }
}
