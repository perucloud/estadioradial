<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.taxonomy.categories', [
            'categories' => Category::query()
                ->withCount('posts')
                ->orderBy('display_order')
                ->orderByDesc('relevance_weight')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['display_order'] = (int) Category::query()->max('display_order') + 10;
        $data += [
            'is_active' => true,
            'show_in_menu' => true,
            'show_on_home' => true,
        ];

        $category = Category::query()->create($data);
        $this->log($request, 'category.created', $category);

        return back()->with('status', 'Categoría creada.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_menu'] = $request->boolean('show_in_menu');
        $data['show_on_home'] = $request->boolean('show_on_home');

        $category->update($data);
        $this->log($request, 'category.updated', $category);

        return back()->with('status', 'Categoría actualizada.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data['order'] as $id => $order) {
                Category::query()->whereKey((int) $id)->update(['display_order' => $order]);
            }
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'categories.reordered',
            'properties' => ['ids' => array_keys($data['order'])],
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return back()->with('status', 'Orden de categorías actualizado.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->posts()->exists()) {
            return back()->withErrors([
                'category' => 'No se puede eliminar una categoría que contiene noticias. Puedes desactivarla.',
            ]);
        }

        $this->log($request, 'category.deleted', $category);
        $category->delete();

        return back()->with('status', 'Categoría eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
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
                Rule::unique('categories')->ignore($category),
            ],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'relevance_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'homepage_limit' => ['required', 'integer', 'min:1', 'max:12'],
            'homepage_layout' => ['required', Rule::in(['standard', 'featured', 'grid'])],
            'is_active' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
        ]);
    }

    private function log(Request $request, string $action, Category $category): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $category->getMorphClass(),
            'subject_id' => $category->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
