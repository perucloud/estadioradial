<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Stream;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with([
                'navigationCategories' => Category::query()->orderBy('name')->get(),
                'globalAudioStream' => Stream::query()
                    ->where('type', 'audio')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first(),
            ]);
        });
    }
}
