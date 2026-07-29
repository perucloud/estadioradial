<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Post;
use App\Support\SidebarData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(private readonly SidebarData $sidebarData) {}

    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);

        $posts = Post::query()
            ->with(['category', 'location.parent.parent.parent', 'media'])
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
        ] + $this->sidebarData->section());
    }

    public function regional(): View
    {
        return view('posts.index', [
            'title' => 'Noticias regionales',
            'description' => 'Información de las regiones, provincias y distritos.',
            'posts' => Post::query()
                ->with(['category', 'location.parent.parent.parent', 'media'])
                ->published()
                ->regional()
                ->latest('published_at')
                ->paginate(9),
            'regionalIndex' => true,
            'territories' => Location::query()
                ->active()
                ->where('type', 'region')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
        ] + $this->sidebarData->section());
    }

    public function location(string $path): View
    {
        $location = $this->resolveLocation($path);

        return view('posts.index', [
            'title' => "Noticias de {$location->name}",
            'description' => $location->description
                ?: "Actualidad de {$location->fullName()} y sus divisiones territoriales.",
            'location' => $location,
            'locationTrail' => $location->lineage(),
            'territories' => $location->children()
                ->active()
                ->get(),
            'posts' => Post::query()
                ->with(['category', 'location.parent.parent.parent', 'media'])
                ->published()
                ->withinLocation($location)
                ->latest('published_at')
                ->paginate(9),
        ] + $this->sidebarData->section());
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('posts.index', [
            'title' => $category->name,
            'category' => $category,
            'posts' => $category->posts()
                ->with(['category', 'location.parent.parent.parent', 'media'])
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ] + $this->sidebarData->section());
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
            'post' => $post->load(['category', 'location.parent.parent.parent', 'tags', 'media']),
            'relatedPosts' => Post::query()
                ->with(['category', 'location.parent.parent.parent', 'media'])
                ->published()
                ->where('category_id', $category->id)
                ->where('id', '!=', $post->id)
                ->latest('published_at')
                ->take(3)
                ->get(),
        ] + $this->sidebarData->article($post->id));
    }

    private function resolveLocation(string $path): Location
    {
        $segments = collect(explode('/', trim($path, '/')))
            ->filter()
            ->values();
        abort_if($segments->isEmpty() || $segments->count() > count(Location::TYPES), 404);

        $parentId = null;
        $location = null;

        foreach ($segments as $segment) {
            $location = Location::query()
                ->active()
                ->where('slug', $segment)
                ->where('parent_id', $parentId)
                ->first();
            abort_if($location === null, 404);
            $parentId = $location->id;
        }

        return $location->load('parent.parent.parent');
    }
}
