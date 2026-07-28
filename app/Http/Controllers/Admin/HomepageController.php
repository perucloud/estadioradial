<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PortalSetting;
use App\Models\Post;
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

        return view('admin.appearance.homepage', [
            'hero' => array_replace($this->heroDefaults(), is_array($storedHero) ? $storedHero : []),
            'slider' => array_replace($this->sliderDefaults(), is_array($storedSlider) ? $storedSlider : []),
            'posts' => Post::query()
                ->with(['category', 'media'])
                ->published()
                ->orderByDesc('editorial_priority')
                ->latest('published_at')
                ->limit(60)
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero.mode' => ['required', Rule::in(['automatic', 'manual'])],
            'hero.interval_seconds' => ['required', 'integer', 'min:4', 'max:60'],
            'hero.effect' => ['required', Rule::in(['slide', 'fade', 'parallax'])],
            'hero.news_limit' => ['required', 'integer', 'min:4', 'max:8'],
            'hero.selection_mode' => ['required', Rule::in(['automatic', 'manual'])],
            'hero.loop' => ['nullable', 'boolean'],
            'hero.parallax' => ['nullable', 'boolean'],
            'hero_posts' => ['nullable', 'array'],
            'hero_posts.*.selected' => ['nullable', 'boolean'],
            'hero_posts.*.order' => ['nullable', 'integer', 'min:1', 'max:100'],
            'slider.mode' => ['required', Rule::in(['automatic', 'manual'])],
            'slider.interval_seconds' => ['required', 'integer', 'min:3', 'max:60'],
            'slider.news_limit' => ['required', 'integer', 'min:4', 'max:12'],
            'slider.period_days' => ['required', Rule::in([0, 7, 30, 90, 365])],
            'slider.loop' => ['nullable', 'boolean'],
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
            ->take((int) $data['hero']['news_limit']);
        $validPostIds = Post::query()->published()->whereIn('id', $manualPostIds)->pluck('id');
        $manualPostIds = $manualPostIds->filter(fn (int $id) => $validPostIds->contains($id))->values();

        DB::transaction(function () use ($request, $data, $manualPostIds): void {
            PortalSetting::put('home.hero_rotator', [
                'mode' => $data['hero']['mode'],
                'interval' => $data['hero']['interval_seconds'] * 1000,
                'loop' => $request->boolean('hero.loop'),
                'effect' => $data['hero']['effect'],
                'parallax' => $request->boolean('hero.parallax'),
                'news_limit' => $data['hero']['news_limit'],
                'selection_mode' => $data['hero']['selection_mode'],
                'post_ids' => $manualPostIds->all(),
            ], 'home');

            PortalSetting::put('home.most_viewed_slider', [
                'mode' => $data['slider']['mode'],
                'interval' => $data['slider']['interval_seconds'] * 1000,
                'loop' => $request->boolean('slider.loop'),
                'news_limit' => $data['slider']['news_limit'],
                'period_days' => (int) $data['slider']['period_days'],
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
                    'slider_period_days' => (int) $data['slider']['period_days'],
                ],
                'ip_hash' => hash('sha256', (string) $request->ip()),
            ]);
        });

        return back()->with('status', 'Configuración de portada actualizada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function heroDefaults(): array
    {
        return [
            'mode' => 'automatic',
            'interval' => 8000,
            'loop' => true,
            'effect' => 'parallax',
            'parallax' => true,
            'news_limit' => 4,
            'selection_mode' => 'automatic',
            'post_ids' => [],
        ];
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
}
