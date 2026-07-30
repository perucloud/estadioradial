<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Support\HomeHeroConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function edit(): View
    {
        $storedHero = PortalSetting::value('home.hero_rotator', []);
        $storedSlider = PortalSetting::value('home.most_viewed_slider', []);
        $storedNational = PortalSetting::value('home.national_news', []);

        return view('admin.appearance.homepage', [
            'hero' => HomeHeroConfig::merge(is_array($storedHero) ? $storedHero : []),
            'heroPresets' => HomeHeroConfig::presets(),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color']),
            'slider' => array_replace($this->sliderDefaults(), is_array($storedSlider) ? $storedSlider : []),
            'national' => array_replace(
                $this->nationalDefaults(),
                is_array($storedNational) ? $storedNational : [],
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $submittedHero = $request->input('hero', []);
        $submittedHero = is_array($submittedHero) ? $submittedHero : [];
        $request->merge([
            'hero' => array_replace(
                HomeHeroConfig::defaults(),
                [
                    'interval_seconds' => 8,
                    'preset_mode' => array_key_exists('preset_mode', $submittedHero) ? 'elegant' : 'custom',
                ],
                $submittedHero,
            ),
        ]);

        $data = $request->validate([
            'hero.mode' => ['required', Rule::in(['automatic', 'manual'])],
            'hero.interval_seconds' => ['required', 'integer', 'min:3', 'max:20'],
            'hero.effect' => ['required', Rule::in(HomeHeroConfig::effects())],
            'hero.quantity_mode' => ['required', Rule::in(['specific', 'all'])],
            'hero.news_limit' => ['nullable', 'required_if:hero.quantity_mode,specific', 'integer', 'min:1'],
            'hero.selection_mode' => ['required', Rule::in(['automatic', 'manual'])],
            'hero.sort_order' => ['required', Rule::in(['latest', 'oldest'])],
            'hero.category_mode' => ['required', Rule::in(['all', 'selected'])],
            'hero.category_ids' => ['exclude_unless:hero.category_mode,selected', 'required', 'array', 'min:1'],
            'hero.category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'hero.preset_mode' => ['required', Rule::in(['elegant', 'dynamic', 'cinematic', 'minimal', 'custom'])],
            'hero.image_animation' => ['required', Rule::in(['none', 'ken-burns', 'zoom-in', 'zoom-out', 'parallax', 'move-horizontal', 'move-vertical'])],
            'hero.image_intensity' => ['required', Rule::in(['soft', 'medium', 'high', 'soft-slow'])],
            'hero.content_animation' => ['required', Rule::in(['none', 'fade', 'fade-up', 'fade-down', 'slide-left', 'slide-right', 'zoom', 'blur'])],
            'hero.transition_duration' => ['required', 'integer', 'min:300', 'max:1500'],
            'hero.overlay_opacity' => ['required', 'integer', 'min:0', 'max:60'],
            'hero.loop' => ['nullable', 'boolean'],
            'hero.parallax' => ['nullable', 'boolean'],
            'hero.preload_images' => ['nullable', 'boolean'],
            'hero.pause_on_hover' => ['nullable', 'boolean'],
            'hero.swipe' => ['nullable', 'boolean'],
            'hero.lazy_load' => ['nullable', 'boolean'],
            'hero.animate_when_visible' => ['nullable', 'boolean'],
            'hero.show_arrows' => ['nullable', 'boolean'],
            'hero.show_indicators' => ['nullable', 'boolean'],
            'hero.pause_when_hidden' => ['nullable', 'boolean'],
            'hero.reset_after_manual' => ['nullable', 'boolean'],
            'hero.reduce_motion_mobile' => ['nullable', 'boolean'],
            'hero_posts' => ['nullable', 'array'],
            'hero_posts.*.selected' => ['nullable', 'boolean'],
            'hero_posts.*.order' => ['nullable', 'integer', 'min:1', 'max:100'],
            'slider.mode' => ['required', Rule::in(['automatic', 'manual'])],
            'slider.interval_seconds' => ['required', 'integer', 'min:3', 'max:60'],
            'slider.news_limit' => ['required', 'integer', 'min:4', 'max:12'],
            'slider.period_days' => ['required', Rule::in([0, 7, 30, 90, 365])],
            'slider.loop' => ['nullable', 'boolean'],
            'national.enabled' => ['nullable', 'boolean'],
            'national.news_limit' => ['required', 'integer', 'min:2', 'max:5'],
            'posts' => ['nullable', 'array'],
            'posts.*.editorial_priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'posts.*.is_featured' => ['nullable', 'boolean'],
            'posts.*.is_homepage_hidden' => ['nullable', 'boolean'],
            'posts.*.pinned_until' => ['nullable', 'date'],
        ]);

        $manualPostIds = collect($data['hero_posts'] ?? [])
            ->filter(fn (array $item) => filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOL))
            ->sortBy(fn (array $item) => (int) ($item['order'] ?? 100))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->when(
                $data['hero']['quantity_mode'] === 'specific',
                fn ($posts) => $posts->take((int) $data['hero']['news_limit'])
            );
        $validPostIds = Post::query()->published()->whereIn('id', $manualPostIds)->pluck('id');
        $manualPostIds = $manualPostIds->filter(fn (int $id) => $validPostIds->contains($id))->values();

        DB::transaction(function () use ($request, $data, $manualPostIds): void {
            PortalSetting::put('home.hero_rotator', [
                'mode' => $data['hero']['mode'],
                'interval' => $data['hero']['interval_seconds'] * 1000,
                'loop' => $request->boolean('hero.loop'),
                'effect' => $data['hero']['effect'],
                'parallax' => (
                    $data['hero']['effect'] === 'parallax'
                    || $data['hero']['image_animation'] === 'parallax'
                ) && $request->boolean('hero.parallax'),
                'news_limit' => (int) ($data['hero']['news_limit'] ?? 4),
                'quantity_mode' => $data['hero']['quantity_mode'],
                'selection_mode' => $data['hero']['selection_mode'],
                'sort_order' => $data['hero']['sort_order'],
                'post_ids' => $manualPostIds->all(),
                'category_mode' => $data['hero']['category_mode'],
                'category_ids' => collect($data['hero']['category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all(),
                'preset_mode' => $data['hero']['preset_mode'],
                'image_animation' => $data['hero']['image_animation'],
                'image_intensity' => $data['hero']['image_intensity'],
                'content_animation' => $data['hero']['content_animation'],
                'transition_duration' => (int) $data['hero']['transition_duration'],
                'overlay_opacity' => (int) $data['hero']['overlay_opacity'],
                'preload_images' => $request->boolean('hero.preload_images'),
                'pause_on_hover' => $request->boolean('hero.pause_on_hover'),
                'swipe' => $request->boolean('hero.swipe'),
                'lazy_load' => $request->boolean('hero.lazy_load'),
                'animate_when_visible' => $request->boolean('hero.animate_when_visible'),
                'show_arrows' => $request->boolean('hero.show_arrows'),
                'show_indicators' => $request->boolean('hero.show_indicators'),
                'pause_when_hidden' => $request->boolean('hero.pause_when_hidden'),
                'reset_after_manual' => $request->boolean('hero.reset_after_manual'),
                'reduce_motion_mobile' => $request->boolean('hero.reduce_motion_mobile'),
            ], 'home');

            PortalSetting::put('home.most_viewed_slider', [
                'mode' => $data['slider']['mode'],
                'interval' => $data['slider']['interval_seconds'] * 1000,
                'loop' => $request->boolean('slider.loop'),
                'news_limit' => $data['slider']['news_limit'],
                'period_days' => (int) $data['slider']['period_days'],
            ], 'home');

            PortalSetting::put('home.national_news', [
                'enabled' => $request->boolean('national.enabled'),
                'news_limit' => (int) $data['national']['news_limit'],
            ], 'home');

            $allowedPostIds = Post::query()
                ->published()
                ->whereIn('id', array_keys($data['posts'] ?? []))
                ->pluck('id');

            foreach (collect($data['posts'] ?? [])->only($allowedPostIds->all()) as $id => $postData) {
                Post::query()->whereKey((int) $id)->update([
                    'editorial_priority' => $postData['editorial_priority'],
                    'is_featured' => filter_var($postData['is_featured'] ?? false, FILTER_VALIDATE_BOOL),
                    'is_homepage_hidden' => filter_var($postData['is_homepage_hidden'] ?? false, FILTER_VALIDATE_BOOL),
                    'pinned_until' => $postData['pinned_until'] ?: null,
                    'updated_by' => $request->user()->id,
                ]);
            }

            ActivityLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'homepage.updated',
                'properties' => [
                    'hero_selection' => $data['hero']['selection_mode'],
                    'hero_posts' => $manualPostIds->all(),
                    'hero_preset' => $data['hero']['preset_mode'],
                    'hero_categories' => $data['hero']['category_ids'] ?? [],
                    'slider_period_days' => (int) $data['slider']['period_days'],
                    'national_news_enabled' => $request->boolean('national.enabled'),
                    'national_news_limit' => (int) $data['national']['news_limit'],
                ],
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ]);
        });

        return back()->with('status', 'Configuración de portada actualizada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function sliderDefaults(): array
    {
        return [
            'mode' => 'automatic',
            'interval' => 6000,
            'loop' => true,
            'news_limit' => 8,
            'period_days' => 30,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nationalDefaults(): array
    {
        return [
            'enabled' => true,
            'news_limit' => 5,
        ];
    }
}
