<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Stream;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredPosts = Post::query()
            ->with('category')
            ->published()
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->take(4)
            ->get();

        $latestPosts = Post::query()
            ->with('category')
            ->published()
            ->whereNotIn('id', $featuredPosts->pluck('id'))
            ->latest('published_at')
            ->take(5)
            ->get();

        $mostViewedPosts = Post::query()
            ->with('category')
            ->published()
            ->orderByDesc('views_count')
            ->latest('published_at')
            ->take(8)
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
            'latestPosts' => $latestPosts,
            'mostViewedPosts' => $mostViewedPosts,
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
