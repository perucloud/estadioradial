<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Stream;
use App\Support\DefaultLocationSettings;
use Illuminate\Pagination\Paginator;
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
        Paginator::defaultView('pagination.portal');
        Paginator::defaultSimpleView('pagination.simple');

        View::composer('layouts.app', function ($view) {
            $socialLinks = PortalSetting::value('social.links', [
                'facebook' => 'https://www.facebook.com/',
                'x' => 'https://x.com/',
                'tiktok' => 'https://www.tiktok.com/',
                'instagram' => 'https://www.instagram.com/',
                'youtube' => 'https://www.youtube.com/',
            ]);
            $contact = PortalSetting::value('site.contact', []);
            $contactEmail = filter_var($contact['email'] ?? null, FILTER_VALIDATE_EMAIL)
                ?: 'contacto@estacionradial.test';

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
            ]);
        });
    }
}
