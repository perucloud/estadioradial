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
            ->latest('published_at')
            ->take(6)
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
