<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('admin.taxonomy.tags', [
            'tags' => Tag::query()->withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $tag = Tag::query()->create($data);
        $this->log($request, 'tag.created', $tag);

        return back()->with('status', 'Etiqueta creada.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $this->validated($request, $tag);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $tag->update($data);
        $this->log($request, 'tag.updated', $tag);

        return back()->with('status', 'Etiqueta actualizada.');
    }

    public function merge(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'target_id' => ['required', 'integer', Rule::exists('tags', 'id'), Rule::notIn([$tag->id])],
        ]);
        $target = Tag::query()->findOrFail($data['target_id']);

        DB::transaction(function () use ($tag, $target): void {
            foreach ($tag->posts()->pluck('posts.id') as $postId) {
                $target->posts()->syncWithoutDetaching([$postId]);
            }

            $tag->delete();
        });

        $this->log($request, 'tag.merged', $target, ['source_id' => $tag->id]);

        return back()->with('status', "Etiqueta combinada con {$target->name}.");
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        if ($tag->posts()->exists()) {
            return back()->withErrors([
                'tag' => 'La etiqueta está en uso. Combínala con otra para conservar sus noticias.',
            ]);
        }

        $this->log($request, 'tag.deleted', $tag);
        $tag->delete();

        return back()->with('status', 'Etiqueta eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Tag $tag = null): array
    {
        if (! $request->filled('slug')) {
            $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tags')->ignore($tag),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Request $request, string $action, Tag $tag, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $tag->getMorphClass(),
            'subject_id' => $tag->id,
            'properties' => $properties,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
