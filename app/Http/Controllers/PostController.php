<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('posts.index', [
            'title' => 'Últimas noticias',
            'posts' => Post::query()
                ->with('category')
                ->published()
                ->latest('published_at')
                ->paginate(9),
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

        return view('posts.show', [
            'post' => $post->load('category'),
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
