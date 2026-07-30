<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Location;
use App\Models\PortalSetting;
use App\Models\Post;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Stream;
use App\Support\HomeHeroConfig;
use App\Support\HomeRegionalConfig;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $storedHeroSettings = PortalSetting::value('home.hero_rotator', []);
        $heroSettings = HomeHeroConfig::merge(
            is_array($storedHeroSettings) ? $storedHeroSettings : [],
        );
        $heroNewsLimit = max(1, (int) $heroSettings['news_limit']);
        $selectedCategoryIds = collect($heroSettings['category_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $featuredQuery = Post::query()
            ->with(['category', 'tags', 'media'])
            ->published()
            ->visibleOnHome()
            ->when(
                $heroSettings['category_mode'] === 'selected' && $selectedCategoryIds->isNotEmpty(),
                fn ($query) => $query->whereIn('category_id', $selectedCategoryIds)
            )
            ->when(
                $heroSettings['sort_order'] === 'oldest',
                fn ($query) => $query->oldest('published_at')->oldest('id'),
                fn ($query) => $query->latest('published_at')->latest('id'),
            );

        $manualPostIds = collect($heroSettings['post_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->when(
                $heroSettings['quantity_mode'] === 'specific',
                fn ($ids) => $ids->take($heroNewsLimit)
            );

        if ($heroSettings['selection_mode'] === 'manual' && $manualPostIds->isNotEmpty()) {
            $manualPosts = (clone $featuredQuery)
                ->whereIn('id', $manualPostIds)
                ->get()
                ->keyBy('id');

            $featuredPosts = $manualPostIds
                ->map(fn (int $id) => $manualPosts->get($id))
                ->filter()
                ->values();

            if ($heroSettings['quantity_mode'] === 'specific' && $featuredPosts->count() < $heroNewsLimit) {
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
            $featuredPosts = $featuredQuery
                ->when(
                    $heroSettings['quantity_mode'] === 'specific',
                    fn ($query) => $query->take($heroNewsLimit)
                )
                ->get();
        }

        $storedRegionalSettings = PortalSetting::value('home.regional_news', []);
        $regionalSettings = HomeRegionalConfig::merge(
            is_array($storedRegionalSettings) ? $storedRegionalSettings : [],
        );
        $regionalCategoryIds = collect($regionalSettings['category_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $regionalLocationId = collect(['district_id', 'province_id', 'region_id'])
            ->map(fn (string $key) => $regionalSettings[$key] ?? null)
            ->first(fn ($id) => is_numeric($id));
        $regionalLocation = $regionalLocationId
            ? Location::query()->active()->find((int) $regionalLocationId)
            : null;
        $regionalQuery = Post::query()
            ->with(['category', 'location.parent.parent.parent', 'tags', 'media'])
            ->published()
            ->visibleOnHome()
            ->regional()
            ->when(
                $regionalSettings['category_mode'] === 'selected' && $regionalCategoryIds->isNotEmpty(),
                fn ($query) => $query->whereIn('category_id', $regionalCategoryIds)
            )
            ->when(
                $regionalLocation,
                fn ($query) => $query->whereIn('location_id', $regionalLocation->subtreeIds())
            )
            ->when(
                $regionalSettings['sort_order'] === 'oldest',
                fn ($query) => $query->oldest('published_at')->oldest('id'),
                fn ($query) => $query->latest('published_at')->latest('id'),
            );
        $regionalPaginator = null;

        if ($regionalSettings['enabled'] && $regionalSettings['pagination_enabled']) {
            $regionalPaginator = $regionalQuery
                ->paginate((int) $regionalSettings['per_page'], ['*'], 'regional_page')
                ->withQueryString()
                ->fragment('noticias-regionales');
            $regionalPosts = $regionalPaginator->getCollection();
        } elseif ($regionalSettings['enabled']) {
            $regionalPosts = $regionalQuery
                ->take((int) $regionalSettings['per_page'])
                ->get();
        } else {
            $regionalPosts = collect();
        }

        $nationalDefaults = [
            'enabled' => true,
            'news_limit' => 5,
        ];
        $storedNationalSettings = PortalSetting::value('home.national_news', []);
        $nationalSettings = array_replace(
            $nationalDefaults,
            is_array($storedNationalSettings) ? $storedNationalSettings : [],
        );
        $nationalLimit = min(5, max(2, (int) $nationalSettings['news_limit']));
        $nationalPosts = $nationalSettings['enabled']
            ? Post::query()
                ->with(['category', 'location.parent.parent.parent', 'tags', 'media'])
                ->published()
                ->visibleOnHome()
                ->latest('published_at')
                ->latest('id')
                ->take($nationalLimit)
                ->get()
            : collect();

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
            ->with('media')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->take(4)
            ->get();

        $todaySchedules = Schedule::query()
            ->with('program')
            ->where('is_active', true)
            ->whereHas('program', fn ($query) => $query->where('is_active', true))
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

        $homeAdvertisements = Advertisement::query()
            ->currentlyActive()
            ->where('placement', 'home_news')
            ->orderBy('sort_order')
            ->take(2)
            ->get();

        return view('home', [
            'featuredPosts' => $featuredPosts,
            'heroSettings' => $heroSettings,
            'regionalPosts' => $regionalPosts,
            'regionalSettings' => $regionalSettings,
            'regionalPaginator' => $regionalPaginator,
            'nationalPosts' => $nationalPosts,
            'nationalSettings' => $nationalSettings,
            'mostViewedPosts' => $mostViewedPosts,
            'sliderSettings' => $sliderSettings,
            'advertisements' => $homeAdvertisements->isNotEmpty() ? $homeAdvertisements : collect([
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
            ]),
            'programs' => $programs,
            'currentSchedule' => $currentSchedule ?? $todaySchedules->first(),
            'nextSchedule' => $nextSchedule ?? $todaySchedules->skip(1)->first(),
            'audioStream' => Stream::query()
                ->where('type', 'audio')
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->first(),
        ]);
    }
}
