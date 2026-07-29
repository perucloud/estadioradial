<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertPostRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Support\PostHtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $status = (string) $request->query('status');
        $categoryId = $request->integer('category');
        $locationFilter = (string) $request->query('location');
        $locationId = ctype_digit($locationFilter) ? (int) $locationFilter : 0;
        $perPage = in_array($request->integer('per_page'), [10, 20, 50, 100], true)
            ? $request->integer('per_page')
            : 20;
        $statuses = [...$this->statuses(), 'trash'];

        return view('admin.posts.index', [
            'posts' => Post::query()
                ->with(['category', 'location.parent.parent.parent', 'media', 'creator'])
                ->when($status === 'trash', fn (Builder $query) => $query->onlyTrashed())
                ->when($search !== '', fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")))
                ->when(in_array($status, $this->statuses(), true), fn (Builder $query) => $query->where('status', $status))
                ->when($categoryId > 0, fn (Builder $query) => $query->where('category_id', $categoryId))
                ->when($locationId > 0, fn (Builder $query) => $query->where('location_id', $locationId))
                ->when($locationFilter === 'none', fn (Builder $query) => $query->whereNull('location_id'))
                ->latest('updated_at')
                ->paginate($perPage)
                ->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()
                ->with('parent.parent.parent')
                ->where('is_active', true)
                ->where(function (Builder $query) use ($locationId): void {
                    $query->whereHas('posts');
                    if ($locationId > 0) {
                        $query->orWhereKey($locationId);
                    }
                })
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
            'statuses' => $statuses,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedCategory' => $categoryId,
            'selectedLocation' => $locationFilter,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.form', $this->formData());
    }

    public function store(UpsertPostRequest $request, PostHtmlSanitizer $sanitizer): RedirectResponse
    {
        $post = DB::transaction(function () use ($request, $sanitizer): Post {
            $data = $this->prepareData($request, $sanitizer);
            [$status, $publishedAt, $scheduledFor] = $this->resolveState($request);
            $media = Media::query()->findOrFail($data['media_id']);

            $post = Post::query()->create($data + [
                'slug' => $this->uniqueSlug($request->input('slug') ?: $data['title']),
                'author' => $request->user()->name,
                'image' => $media->url('article'),
                'status' => $status,
                'published_at' => $publishedAt,
                'scheduled_for' => $scheduledFor,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'reviewed_by' => in_array($status, ['published', 'scheduled'], true) ? $request->user()->id : null,
            ]);

            $this->syncRelations($post, $request);
            $this->log($request, 'post.created', $post, ['status' => $status]);

            return $post;
        });

        return redirect()->route('admin.posts.edit', $post)
            ->with('status', 'Noticia creada correctamente.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', $this->formData($post->load(['tags', 'inlineMedia', 'location.parent.parent.parent'])));
    }

    public function update(
        UpsertPostRequest $request,
        Post $post,
        PostHtmlSanitizer $sanitizer,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $post, $sanitizer): void {
            $data = $this->prepareData($request, $sanitizer);
            [$status, $publishedAt, $scheduledFor] = $this->resolveState($request, $post);
            $media = Media::query()->findOrFail($data['media_id']);

            $post->update($data + [
                'slug' => $request->filled('slug') ? $request->string('slug')->toString() : $post->slug,
                'image' => $media->url('article'),
                'status' => $status,
                'published_at' => $publishedAt,
                'scheduled_for' => $scheduledFor,
                'updated_by' => $request->user()->id,
                'reviewed_by' => in_array($status, ['published', 'scheduled'], true)
                    ? $request->user()->id
                    : $post->reviewed_by,
            ]);

            $this->syncRelations($post, $request);
            $this->log($request, 'post.updated', $post, ['status' => $status]);
        });

        return back()->with('status', 'Noticia actualizada.');
    }

    public function preview(Post $post): View
    {
        return view('admin.posts.preview', ['post' => $post->load(['category', 'location', 'tags', 'media'])]);
    }

    public function archive(Request $request, Post $post): RedirectResponse
    {
        $post->update([
            'status' => 'archived',
            'scheduled_for' => null,
            'updated_by' => $request->user()->id,
        ]);
        $this->log($request, 'post.archived', $post);

        return back()->with('status', 'Noticia archivada.');
    }

    public function restore(Request $request, Post $post): RedirectResponse
    {
        $post->update([
            'status' => 'draft',
            'published_at' => null,
            'scheduled_for' => null,
            'updated_by' => $request->user()->id,
        ]);
        $this->log($request, 'post.restored', $post);

        return back()->with('status', 'Noticia recuperada como borrador.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->log($request, 'post.deleted', $post, [
            'status' => $post->status,
            'title' => $post->title,
        ]);
        $post->delete();

        return back()->with('status', 'Noticia enviada a la papelera.');
    }

    public function restoreDeleted(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->trashed(), 404);

        $post->restore();
        $this->log($request, 'post.restored_from_trash', $post);

        return back()->with('status', 'Noticia restaurada desde la papelera.');
    }

    public function duplicate(Request $request, Post $post): RedirectResponse
    {
        $copy = DB::transaction(function () use ($request, $post): Post {
            $copy = $post->replicate([
                'slug',
                'status',
                'published_at',
                'scheduled_for',
                'views_count',
                'reviewed_by',
            ]);
            $copy->title = 'Copia de '.$post->title;
            $copy->slug = $this->uniqueSlug($copy->title);
            $copy->status = 'draft';
            $copy->published_at = null;
            $copy->scheduled_for = null;
            $copy->views_count = 0;
            $copy->created_by = $request->user()->id;
            $copy->updated_by = $request->user()->id;
            $copy->save();
            $copy->tags()->sync($post->tags()->pluck('tags.id'));
            $copy->inlineMedia()->sync($post->inlineMedia()->pluck('media.id'));
            $this->log($request, 'post.duplicated', $copy, ['source_id' => $post->id]);

            return $copy;
        });

        return redirect()->route('admin.posts.edit', $copy)->with('status', 'Copia creada como borrador.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Post $post = null): array
    {
        $locationSelection = $post?->location
            ? $post->location->lineage()->mapWithKeys(fn (Location $location) => [$location->type => $location->id])->all()
            : [];
        $oldSelection = collect(['country', 'region', 'province', 'district'])
            ->mapWithKeys(function (string $type): array {
                $value = session()->getOldInput('location_'.$type.'_id');

                return [$type => is_numeric($value) ? (int) $value : null];
            })
            ->filter()
            ->all();
        $selection = $oldSelection + $locationSelection;
        $locationsByType = collect([
            'country' => Location::query()
                ->active()
                ->where('type', 'country')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
        ]);

        foreach (['region' => 'country', 'province' => 'region', 'district' => 'province'] as $type => $parentType) {
            $parentId = $selection[$parentType] ?? null;
            $locationsByType->put($type, $parentId
                ? Location::query()
                    ->active()
                    ->where('type', $type)
                    ->where('parent_id', $parentId)
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->get()
                : collect());
        }

        return [
            'post' => $post,
            'categories' => Category::query()->where('is_active', true)->orderBy('display_order')->orderBy('name')->get(),
            'tags' => Tag::query()
                ->withCount('posts')
                ->orderByDesc('posts_count')
                ->orderBy('name')
                ->get(),
            'locationsByType' => $locationsByType,
            'locationSelection' => $selection,
            'locationOptionsUrl' => route('admin.locations.options'),
            'mediaItems' => Media::query()->latest()->limit(120)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareData(UpsertPostRequest $request, PostHtmlSanitizer $sanitizer): array
    {
        $data = $request->safe()->except([
            'slug',
            'tag_names',
            'inline_media_ids',
            'intent',
            'scheduled_for',
            'location_country_id',
            'location_region_id',
            'location_province_id',
            'location_district_id',
        ]);
        $data['body'] = $sanitizer->sanitize($data['body']);

        if (mb_strlen(trim(strip_tags($data['body']))) < 20) {
            throw ValidationException::withMessages([
                'body' => 'El contenido editorial debe tener al menos 20 caracteres.',
            ]);
        }

        return $data;
    }

    /**
     * @return array{string, mixed, mixed}
     */
    private function resolveState(UpsertPostRequest $request, ?Post $post = null): array
    {
        $intent = $request->string('intent')->toString();

        if (in_array($intent, ['publish', 'schedule'], true) && ! $request->user()->hasPermission('news.publish')) {
            abort(403);
        }

        return match ($intent) {
            'draft' => ['draft', null, null],
            'review' => ['in_review', null, null],
            'publish' => ['published', now(), null],
            'schedule' => $request->filled('scheduled_for')
                ? ['scheduled', null, $request->date('scheduled_for')]
                : throw ValidationException::withMessages(['scheduled_for' => 'Indica la fecha y hora de publicación.']),
            default => [
                $post?->status ?? 'draft',
                $post?->published_at,
                $post?->scheduled_for,
            ],
        };
    }

    private function syncRelations(Post $post, UpsertPostRequest $request): void
    {
        $tagIds = collect(preg_split('/[,;\r\n]+/u', (string) $request->input('tag_names')) ?: [])
            ->map(fn (string $name) => mb_substr(trim(strip_tags($name)), 0, 100))
            ->filter()
            ->unique(fn (string $name) => mb_strtolower($name))
            ->take(20)
            ->map(function (string $name): ?int {
                $slug = Str::slug($name);

                if ($slug === '') {
                    return null;
                }

                return Tag::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name],
                )->id;
            })
            ->filter();
        $post->tags()->sync($tagIds);
        $inlineIds = collect(explode(',', (string) $request->input('inline_media_ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique();
        $validIds = Media::query()
            ->whereIn('id', $inlineIds)
            ->get()
            ->filter(fn (Media $media) => str_contains($post->body, $media->url('article')))
            ->pluck('id');
        $post->inlineMedia()->sync($validIds);
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'noticia';
        $slug = $base;
        $suffix = 2;

        while (Post::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return ['draft', 'in_review', 'scheduled', 'published', 'archived'];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Request $request, string $action, Post $post, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $post->getMorphClass(),
            'subject_id' => $post->id,
            'properties' => $properties,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
