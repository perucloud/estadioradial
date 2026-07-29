<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Stream;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $heroDefaults = [
            'mode' => 'automatic',
            'interval' => 8000,
            'loop' => true,
            'effect' => 'parallax',
            'parallax' => true,
            'news_limit' => 4,
            'selection_mode' => 'automatic',
            'post_ids' => [],
        ];
        $storedHeroSettings = PortalSetting::value('home.hero_rotator', []);
        $heroSettings = array_replace(
            $heroDefaults,
            is_array($storedHeroSettings) ? $storedHeroSettings : [],
        );
        $heroNewsLimit = min(8, max(4, (int) $heroSettings['news_limit']));

        $featuredQuery = Post::query()
            ->with(['category', 'tags', 'media'])
            ->published()
            ->visibleOnHome()
            ->latest('published_at')
            ->latest('id');

        $manualPostIds = collect($heroSettings['post_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take($heroNewsLimit);

        if ($heroSettings['selection_mode'] === 'manual' && $manualPostIds->isNotEmpty()) {
            $manualPosts = (clone $featuredQuery)
                ->whereIn('id', $manualPostIds)
                ->get()
                ->keyBy('id');

            $featuredPosts = $manualPostIds
                ->map(fn (int $id) => $manualPosts->get($id))
                ->filter()
                ->values();

            if ($featuredPosts->count() < $heroNewsLimit) {
                $featuredPosts = $featuredPosts
                    ->concat(
                        (clone $featuredQuery)
                            ->whereNotIn('id', $featuredPosts->pluck('id'))
                            ->take($heroNewsLimit - $featuredPosts->count())
                            ->get()
                    )
                    ->values();
            }
        } else {
            $featuredPosts = $featuredQuery->take($heroNewsLimit)->get();
        }

        // This list can later be expanded with province and district category
        // slugs managed from the dashboard.
        $regionalCategorySlugs = ['regionales'];
        $regionalCategory = Category::query()
            ->whereIn('slug', $regionalCategorySlugs)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();
        $regionalPosts = Post::query()
            ->with(['category', 'tags', 'media'])
            ->published()
            ->visibleOnHome()
            ->whereHas('category', fn ($query) => $query->whereIn('slug', $regionalCategorySlugs))
            ->latest('published_at')
            ->latest('id')
            ->take(5)
            ->get();

        $sliderDefaults = [
            'mode' => 'automatic',
            'interval' => 6000,
            'loop' => true,
            'news_limit' => 8,
            'period_days' => 30,
        ];
        $storedSliderSettings = PortalSetting::value('home.most_viewed_slider', []);
        $sliderSettings = array_replace(
            $sliderDefaults,
            is_array($storedSliderSettings) ? $storedSliderSettings : [],
        );
        $sliderLimit = min(12, max(4, (int) $sliderSettings['news_limit']));
        $sliderPeriod = max(0, (int) $sliderSettings['period_days']);

        $mostViewedPosts = Post::query()
            ->with(['category', 'tags', 'media'])
            ->published()
            ->visibleOnHome()
            ->when($sliderPeriod > 0, fn ($query) => $query
                ->where('published_at', '>=', now()->subDays($sliderPeriod)))
            ->orderByDesc('views_count')
            ->latest('published_at')
            ->take($sliderLimit)
            ->get();

        $programs = Program::query()
            ->where('is_active', true)
            ->take(4)
            ->get();

        $todaySchedules = Schedule::query()
            ->with('program')
            ->where('day_of_week', now()->dayOfWeekIso)
            ->orderBy('starts_at')
            ->get();

        $currentTime = now()->format('H:i:s');
        $currentSchedule = $todaySchedules->first(
            fn (Schedule $schedule) => $schedule->starts_at <= $currentTime
                && $schedule->ends_at > $currentTime
        );

        $nextSchedule = $todaySchedules->first(
            fn (Schedule $schedule) => $schedule->starts_at > $currentTime
        );

        return view('home', [
            'featuredPosts' => $featuredPosts,
            'heroSettings' => $heroSettings,
            'regionalCategory' => $regionalCategory,
            'regionalPosts' => $regionalPosts,
            'mostViewedPosts' => $mostViewedPosts,
            'sliderSettings' => $sliderSettings,
            'advertisements' => [
                [
                    'eyebrow' => 'Espacio disponible',
                    'title' => 'Conecta tu marca con nuestra audiencia',
                    'description' => 'Publicidad visible en noticias, radio y programación.',
                    'tone' => 'dark',
                ],
                [
                    'eyebrow' => 'Anuncia aquí',
                    'title' => 'Tu campaña puede ocupar este espacio',
                    'description' => 'Formato adaptable para escritorio y dispositivos móviles.',
                    'tone' => 'red',
                ],
            ],
            'programs' => $programs,
            'currentSchedule' => $currentSchedule ?? $todaySchedules->first(),
            'nextSchedule' => $nextSchedule ?? $todaySchedules->skip(1)->first(),
            'audioStream' => Stream::query()
                ->where('type', 'audio')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first(),
        ]);
    }
}
