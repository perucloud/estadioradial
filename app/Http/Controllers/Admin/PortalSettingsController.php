<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.portal', [
            'social' => PortalSetting::value('social.links', []),
            'contact' => PortalSetting::value('site.contact', []),
            'article' => PortalSetting::value('article.sidebar', []),
            'section' => PortalSetting::value('section.sidebar', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'social.*' => ['nullable', 'url:https', 'max:500'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:50'],
            'contact.whatsapp' => ['nullable', 'string', 'max:50'],
            'contact.address' => ['nullable', 'string', 'max:500'],
            'article.most_read_limit' => ['required', 'integer', 'between:1,10'],
            'article.latest_limit' => ['required', 'integer', 'between:1,10'],
            'section.most_read_limit' => ['required', 'integer', 'between:1,10'],
            'section.latest_limit' => ['required', 'integer', 'between:1,10'],
        ]);

        PortalSetting::put('social.links', array_filter($data['social'] ?? []), 'social');
        PortalSetting::put('site.contact', array_filter($data['contact'] ?? []), 'site');
        PortalSetting::put('article.sidebar', $this->sidebar($request, 'article', $data), 'sidebar');
        PortalSetting::put('section.sidebar', $this->sidebar($request, 'section', $data) + ['adaptive' => $request->boolean('section.adaptive')], 'sidebar');

        return back()->with('status', 'Configuración del portal actualizada.');
    }

    private function sidebar(Request $request, string $key, array $data): array
    {
        return [
            'modules' => $request->input($key.'.modules', []),
            'most_read_limit' => $data[$key]['most_read_limit'],
            'latest_limit' => $data[$key]['latest_limit'],
            'sticky' => $request->boolean($key.'.sticky'),
        ];
    }
}
