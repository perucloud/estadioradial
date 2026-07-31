<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Services\SystemBackupService;
use App\Support\PortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SystemSettingsController extends Controller
{
    private const CONFIGURE_SECTIONS = ['identity', 'contact', 'social', 'colors', 'seo'];

    private const SYSTEM_SECTIONS = ['regional', 'smtp', 'cache', 'maintenance', 'backups', 'security'];

    public function configure(string $section = 'identity'): View
    {
        abort_unless(in_array($section, self::CONFIGURE_SECTIONS, true), 404);

        return view('admin.settings.configure', [
            'section' => $section,
            'identity' => PortalSettings::get('site.identity'),
            'contact' => PortalSettings::get('site.contact'),
            'social' => PortalSettings::get('social.links'),
            'theme' => PortalSettings::get('site.theme'),
            'seo' => PortalSettings::get('site.seo'),
            'mediaItems' => Media::query()->latest()->limit(24)->get(),
        ]);
    }

    public function updateConfigure(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, self::CONFIGURE_SECTIONS, true), 404);

        if ($section === 'identity') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'slogan' => ['nullable', 'string', 'max:160'],
                'frequency' => ['nullable', 'string', 'max:50'],
                'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            ]);
            PortalSettings::put('site.identity', $data, 'site');
        } elseif ($section === 'contact') {
            $data = $request->validate([
                'address' => ['nullable', 'string', 'max:500'],
                'phone' => ['nullable', 'string', 'max:50'],
                'whatsapp' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
            ]);
            PortalSettings::put('site.contact', $data, 'site');
        } elseif ($section === 'social') {
            $data = $request->validate([
                'facebook' => ['nullable', 'url:http,https', 'max:500'],
                'x' => ['nullable', 'url:http,https', 'max:500'],
                'tiktok' => ['nullable', 'url:http,https', 'max:500'],
                'instagram' => ['nullable', 'url:http,https', 'max:500'],
                'youtube' => ['nullable', 'url:http,https', 'max:500'],
            ]);
            PortalSettings::put('social.links', array_filter($data), 'social');
        } elseif ($section === 'colors') {
            $data = $request->validate([
                'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'surface' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]);
            PortalSettings::put('site.theme', $data, 'site');
        } else {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:70'],
                'description' => ['required', 'string', 'max:170'],
                'keywords' => ['nullable', 'string', 'max:500'],
                'canonical_url' => ['nullable', 'url:http,https', 'max:500'],
                'og_media_id' => ['nullable', 'integer', 'exists:media,id'],
            ]);
            $data['robots_index'] = $request->boolean('robots_index');
            PortalSettings::put('site.seo', $data, 'site');
        }

        $this->log('settings.configure.updated', ['section' => $section]);

        return back()->with('status', 'Configuración actualizada correctamente.');
    }

    public function system(SystemBackupService $backups, string $section = 'regional'): View
    {
        abort_unless(in_array($section, self::SYSTEM_SECTIONS, true), 404);

        return view('admin.settings.system', [
            'section' => $section,
            'regional' => PortalSettings::get('system.regional'),
            'smtp' => PortalSettings::get('system.smtp'),
            'maintenance' => PortalSettings::get('system.maintenance'),
            'backupSettings' => PortalSettings::get('system.backups'),
            'security' => PortalSettings::get('system.security'),
            'backupFiles' => $section === 'backups' ? $backups->files() : [],
        ]);
    }

    public function updateSystem(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, ['regional', 'smtp', 'maintenance', 'backups', 'security'], true), 404);

        if ($section === 'regional') {
            $data = $request->validate([
                'locale' => ['required', Rule::in(['es', 'en'])],
                'timezone' => ['required', 'timezone'],
                'date_format' => ['required', Rule::in(['d/m/Y', 'Y-m-d', 'd-m-Y'])],
                'time_format' => ['required', Rule::in(['H:i', 'h:i A'])],
            ]);
            PortalSettings::put('system.regional', $data, 'system', false);
        } elseif ($section === 'smtp') {
            $data = $request->validate([
                'host' => ['required_if:enabled,1', 'nullable', 'string', 'max:255'],
                'port' => ['required', 'integer', 'between:1,65535'],
                'encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:500'],
                'from_address' => ['required_if:enabled,1', 'nullable', 'email'],
                'from_name' => ['required', 'string', 'max:100'],
            ]);
            $current = PortalSettings::get('system.smtp');
            $data['enabled'] = $request->boolean('enabled');
            $data['password'] = filled($data['password'] ?? null)
                ? Crypt::encryptString($data['password'])
                : ($current['password'] ?? '');
            PortalSettings::put('system.smtp', $data, 'system', false);
        } elseif ($section === 'maintenance') {
            $data = $request->validate([
                'message' => ['required', 'string', 'max:500'],
                'return_at' => ['nullable', 'date'],
            ]);
            $data['enabled'] = $request->boolean('enabled');
            PortalSettings::put('system.maintenance', $data, 'system', false);
        } elseif ($section === 'backups') {
            $data = $request->validate(['retention' => ['required', 'integer', 'between:1,50']]);
            $data['include_media'] = $request->boolean('include_media');
            PortalSettings::put('system.backups', $data, 'system', false);
        } else {
            $data = $request->validate([
                'max_attempts' => ['required', 'integer', 'between:3,20'],
                'lock_minutes' => ['required', 'integer', 'between:1,1440'],
                'session_lifetime' => ['required', 'integer', 'between:15,1440'],
                'password_min' => ['required', 'integer', 'between:8,64'],
            ]);
            foreach (['captcha_enabled', 'password_mixed_case', 'password_numbers', 'password_symbols'] as $boolean) {
                $data[$boolean] = $request->boolean($boolean);
            }
            PortalSettings::put('system.security', $data, 'system', false);
        }

        $this->log('settings.system.updated', ['section' => $section]);

        return back()->with('status', 'Ajustes guardados correctamente.');
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');
        PortalSettings::flush();
        $this->log('cache.cleared');

        return back()->with('status', 'Las cachés de la aplicación fueron limpiadas.');
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => ['required', 'email']]);

        try {
            Mail::raw('Este es un correo de prueba enviado desde Estación Radial.', function ($message) use ($request) {
                $message->to($request->string('test_email')->toString())->subject('Prueba SMTP · Estación Radial');
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('warning', 'No fue posible enviar el correo. Revisa el servidor, puerto y credenciales.');
        }

        return back()->with('status', 'Correo de prueba enviado correctamente.');
    }

    public function createBackup(SystemBackupService $backups): RedirectResponse
    {
        $settings = PortalSettings::get('system.backups');

        try {
            $filename = $backups->create((bool) $settings['include_media']);
            $backups->enforceRetention((int) $settings['retention']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('warning', $exception->getMessage());
        }

        $this->log('backup.created', ['filename' => $filename]);

        return back()->with('status', 'Respaldo creado: '.$filename);
    }

    public function downloadBackup(string $filename, SystemBackupService $backups)
    {
        return response()->download($backups->path($filename));
    }

    public function deleteBackup(string $filename, SystemBackupService $backups): RedirectResponse
    {
        $backups->delete($filename);
        $this->log('backup.deleted', ['filename' => $filename]);

        return back()->with('status', 'Respaldo eliminado.');
    }

    private function log(string $action, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'properties' => $properties,
            'ip_hash' => hash('sha256', (string) request()->ip()),
        ]);
    }
}
