<?php

namespace App\Support;

use App\Models\PortalSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PortalSettings
{
    public const CACHE_KEY = 'portal.settings.all';

    public static function defaults(): array
    {
        return [
            'site.identity' => [
                'name' => 'Estación Radial',
                'slogan' => 'Voces que conectan',
                'frequency' => '',
                'logo_media_id' => null,
                'favicon_media_id' => null,
            ],
            'site.contact' => [
                'address' => '',
                'phone' => '',
                'whatsapp' => '',
                'email' => 'contacto@estacionradial.test',
            ],
            'social.links' => [
                'facebook' => 'https://www.facebook.com/',
                'x' => 'https://x.com/',
                'tiktok' => 'https://www.tiktok.com/',
                'instagram' => 'https://www.instagram.com/',
                'youtube' => 'https://www.youtube.com/',
            ],
            'site.theme' => [
                'primary' => '#c91725',
                'secondary' => '#15181d',
                'accent' => '#ef3340',
                'surface' => '#ffffff',
                'text' => '#17191d',
            ],
            'site.seo' => [
                'title' => 'Estación Radial',
                'description' => 'Noticias, programas y radio en vivo desde Estación Radial.',
                'keywords' => '',
                'canonical_url' => '',
                'robots_index' => true,
                'og_media_id' => null,
            ],
            'system.regional' => [
                'locale' => 'es',
                'timezone' => 'America/Lima',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
            ],
            'system.smtp' => [
                'enabled' => false,
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'username' => '',
                'password' => '',
                'from_address' => '',
                'from_name' => 'Estación Radial',
            ],
            'system.maintenance' => [
                'enabled' => false,
                'message' => 'Estamos realizando mejoras. Volveremos pronto.',
                'return_at' => null,
            ],
            'system.backups' => [
                'retention' => 10,
                'include_media' => true,
            ],
            'system.security' => [
                'captcha_enabled' => true,
                'max_attempts' => 5,
                'lock_minutes' => 15,
                'session_lifetime' => 120,
                'password_min' => 8,
                'password_mixed_case' => true,
                'password_numbers' => true,
                'password_symbols' => false,
            ],
        ];
    }

    public static function get(string $key): array
    {
        $all = self::all();

        return array_replace(self::defaults()[$key] ?? [], $all[$key] ?? []);
    }

    public static function all(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 300, fn () => PortalSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (PortalSetting $setting) => [$setting->key => $setting->value ?? []])
                ->all());
        } catch (Throwable) {
            return [];
        }
    }

    public static function put(string $key, array $value, string $group, bool $public = true): void
    {
        PortalSetting::put($key, $value, $group, $public);
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function decryptSecret(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return '';
        }
    }
}
