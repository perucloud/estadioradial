<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
        ]);
    }
}
