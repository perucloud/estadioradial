<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Post;
use App\Models\Program;
use App\Models\Stream;
use App\Models\User;
use App\Support\SchedulerHealth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SchedulerHealth $schedulerHealth): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'Noticias' => Post::query()->count(),
                'Publicadas' => Post::query()->where('status', 'published')->count(),
                'Programas' => Program::query()->count(),
                'Usuarios' => User::query()->where('is_active', true)->count(),
            ],
            'streams' => Stream::query()->where('is_active', true)->count(),
            'advertisements' => Advertisement::query()->where('is_active', true)->count(),
            'recentPosts' => Post::query()->latest()->limit(5)->get(),
            'scheduler' => $schedulerHealth->snapshot(),
            'overdueScheduledPosts' => Post::query()
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '<=', now())
                ->count(),
        ]);
    }
}
