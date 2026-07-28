<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        $posts = Post::query()
            ->with('category')
            ->published()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('posts.index', [
            'title' => $search === '' ? 'Últimas noticias' : "Resultados para “{$search}”",
            'posts' => $posts,
            'search' => $search,
        ]);
    }

    public function category(Category $category): View
    {
        return view('posts.index', [
            'title' => $category->name,
            'category' => $category,
            'posts' => $category->posts()
                ->with('category')
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(Category $category, Post $post): View
    {
        abort_unless(
            $post->category_id === $category->id
                && $post->status === 'published'
                && $post->published_at?->isPast(),
            404
        );

        $post->increment('views_count');
        $sidebarSettings = PortalSetting::value('article.sidebar', [
            'modules' => ['most_read', 'latest', 'advertisements', 'social', 'categories'],
            'most_read_limit' => 5,
            'latest_limit' => 5,
            'sticky' => true,
        ]);
        $mostReadLimit = min(10, max(1, (int) ($sidebarSettings['most_read_limit'] ?? 5)));
        $latestLimit = min(10, max(1, (int) ($sidebarSettings['latest_limit'] ?? 5)));

        return view('posts.show', [
            'post' => $post->load(['category', 'tags']),
            'relatedPosts' => Post::query()
                ->with('category')
                ->published()
                ->where('category_id', $category->id)
                ->where('id', '!=', $post->id)
                ->latest('published_at')
                ->take(3)
                ->get(),
            'sidebarSettings' => $sidebarSettings,
            'sidebarMostRead' => Post::query()
                ->with('category')
                ->published()
                ->where('id', '!=', $post->id)
                ->orderByDesc('views_count')
                ->latest('published_at')
                ->take($mostReadLimit)
                ->get(),
            'sidebarLatest' => Post::query()
                ->with('category')
                ->published()
                ->where('id', '!=', $post->id)
                ->latest('published_at')
                ->take($latestLimit)
                ->get(),
            'sidebarAdvertisements' => Advertisement::query()
                ->currentlyActive()
                ->where('placement', 'article_sidebar')
                ->orderBy('sort_order')
                ->get(),
            'sidebarCategories' => Category::query()
                ->where('is_active', true)
                ->where('show_in_menu', true)
                ->withCount(['posts' => fn ($query) => $query->published()])
                ->orderBy('display_order')
                ->orderByDesc('relevance_weight')
                ->get(),
            'sidebarSocialLinks' => PortalSetting::value('social.links', [
                'facebook' => 'https://www.facebook.com/',
                'x' => 'https://x.com/',
                'tiktok' => 'https://www.tiktok.com/',
                'instagram' => 'https://www.instagram.com/',
                'youtube' => 'https://www.youtube.com/',
            ]),
        ]);
    }
}
