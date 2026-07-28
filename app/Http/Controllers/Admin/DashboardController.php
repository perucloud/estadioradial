<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Post;
use App\Models\Program;
use App\Models\Stream;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
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
        ]);
    }
}
