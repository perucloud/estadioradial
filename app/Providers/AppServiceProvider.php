<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Stream;
use App\Support\DefaultLocationSettings;
use App\Support\PortalSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(DefaultLocationSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $regional = PortalSettings::get('system.regional');
        Config::set('app.locale', $regional['locale']);
        Config::set('app.timezone', $regional['timezone']);
        date_default_timezone_set($regional['timezone']);
        App::setLocale($regional['locale']);

        $security = PortalSettings::get('system.security');
        Config::set('admin.captcha.enabled', (bool) $security['captcha_enabled']);
        Config::set('admin.login.max_attempts', (int) $security['max_attempts']);
        Config::set('admin.login.lock_minutes', (int) $security['lock_minutes']);
        Config::set('session.lifetime', (int) $security['session_lifetime']);

        $smtp = PortalSettings::get('system.smtp');
        if ($smtp['enabled'] ?? false) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $smtp['host']);
            Config::set('mail.mailers.smtp.port', $smtp['port']);
            Config::set('mail.mailers.smtp.encryption', $smtp['encryption'] === 'none' ? null : $smtp['encryption']);
            Config::set('mail.mailers.smtp.username', $smtp['username']);
            Config::set('mail.mailers.smtp.password', PortalSettings::decryptSecret($smtp['password'] ?? ''));
            Config::set('mail.from.address', $smtp['from_address']);
            Config::set('mail.from.name', $smtp['from_name']);
        }

        Paginator::defaultView('pagination.portal');
        Paginator::defaultSimpleView('pagination.simple');

        View::composer('layouts.app', function ($view) {
            $socialLinks = PortalSettings::get('social.links');
            $contact = PortalSettings::get('site.contact');
            $identity = PortalSettings::get('site.identity');
            $theme = PortalSettings::get('site.theme');
            $seo = PortalSettings::get('site.seo');
            $contactEmail = filter_var($contact['email'] ?? null, FILTER_VALIDATE_EMAIL)
                ?: 'contacto@estacionradial.test';
            $logo = filled($identity['logo_media_id'] ?? null)
                ? Media::query()->find($identity['logo_media_id'])
                : null;
            $ogImage = filled($seo['og_media_id'] ?? null)
                ? Media::query()->find($seo['og_media_id'])
                : null;

            $view->with([
                'navigationCategories' => Category::query()
                    ->where('is_active', true)
                    ->where('show_in_menu', true)
                    ->orderBy('display_order')
                    ->orderByDesc('relevance_weight')
                    ->get(),
                'globalAudioStream' => Stream::query()
                    ->where('type', 'audio')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first(),
                'socialLinks' => $socialLinks,
                'contactEmail' => $contactEmail,
                'siteContact' => $contact,
                'siteIdentity' => $identity,
                'siteTheme' => $theme,
                'siteSeo' => $seo,
                'siteLogoUrl' => $logo?->url('thumb'),
                'siteOgImageUrl' => $ogImage?->url('article'),
            ]);
        });
    }
}
