<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertCategoryRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $status = in_array($request->query('status'), ['active', 'inactive', 'trash'], true)
            ? (string) $request->query('status')
            : '';
        $parent = (string) $request->query('parent', '');
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 50, 100], true)
            ? (int) $request->query('per_page')
            : 20;

        $query = Category::query()
            ->with('parent')
            ->withCount(['posts', 'children'])
            ->orderBy('display_order')
            ->orderBy('name');

        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        $ordered = $status === 'trash'
            ? $query->get()
            : $this->asTree($query->get());

        $filtered = $ordered
            ->when($search !== '', fn (Collection $categories) => $categories->filter(
                fn (Category $category) => str_contains(
                    mb_strtolower($category->name.' '.$category->slug.' '.$category->description),
                    mb_strtolower($search),
                )
            ))
            ->when($status === 'active', fn (Collection $categories) => $categories->where('is_active', true))
            ->when($status === 'inactive', fn (Collection $categories) => $categories->where('is_active', false))
            ->when($parent === 'root', fn (Collection $categories) => $categories->whereNull('parent_id'))
            ->when(ctype_digit($parent), fn (Collection $categories) => $categories->where('parent_id', (int) $parent))
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $categories = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
        $parentOptions = $this->asTree(Category::query()
            ->withCount(['posts', 'children'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get());

        return view('admin.taxonomy.categories', [
            'categories' => $categories,
            'parentOptions' => $parentOptions,
            'status' => $status,
            'search' => $search,
            'parentFilter' => $parent,
            'perPage' => $perPage,
            'stats' => [
                'total' => Category::query()->count(),
                'active' => Category::query()->where('is_active', true)->count(),
                'inactive' => Category::query()->where('is_active', false)->count(),
                'trash' => Category::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(UpsertCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['display_order'] = (int) Category::query()->max('display_order') + 10;
        $this->setVisibility($data, $request, true);
        $this->ensureValidParent(null, $data['parent_id'] ?? null);

        $category = Category::query()->create($data);
        $this->log($request, 'category.created', $category);

        return back()->with('status', 'Categoría creada correctamente.');
    }

    public function update(UpsertCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $this->setVisibility($data, $request);
        $this->ensureValidParent($category, $data['parent_id'] ?? null);

        $category->update($data);
        $this->log($request, 'category.updated', $category);

        return back()->with('status', 'Categoría actualizada correctamente.');
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
        $data = $request->validate([
            'replacement_category_id' => [
                'nullable',
                'integer',
                Rule::notIn([$category->id]),
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
        ]);
        $replacementId = isset($data['replacement_category_id'])
            ? (int) $data['replacement_category_id']
            : null;

        if ($category->posts()->exists() && $replacementId === null) {
            return back()->withErrors([
                'category' => 'Selecciona una categoría de reemplazo para trasladar las noticias antes de eliminar.',
            ]);
        }

        if ($replacementId === $category->id || ($replacementId !== null && $this->isDescendant($category, $replacementId))) {
            return back()->withErrors([
                'category' => 'La categoría de reemplazo no puede ser descendiente de la categoría eliminada.',
            ]);
        }

        DB::transaction(function () use ($request, $category, $replacementId): void {
            if ($replacementId !== null) {
                $category->posts()->update(['category_id' => $replacementId]);
            }

            $category->children()->update(['parent_id' => $category->parent_id]);
            $this->log($request, 'category.deleted', $category);
            $category->delete();
        });

        return back()->with('status', 'Categoría enviada a la papelera sin perder noticias.');
    }

    public function restore(Request $request, int $category): RedirectResponse
    {
        $categoryModel = Category::onlyTrashed()->findOrFail($category);
        $categoryModel->restore();
        $this->log($request, 'category.restored', $categoryModel);

        return back()->with('status', 'Categoría restaurada.');
    }

    public function forceDestroy(Request $request, int $category): RedirectResponse
    {
        $categoryModel = Category::onlyTrashed()->findOrFail($category);

        if ($categoryModel->posts()->exists()) {
            return back()->withErrors([
                'category' => 'No se puede eliminar definitivamente una categoría que contiene noticias.',
            ]);
        }

        $this->log($request, 'category.force_deleted', $categoryModel);
        $categoryModel->forceDelete();

        return back()->with('status', 'Categoría eliminada definitivamente.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function setVisibility(array &$data, Request $request, bool $useDefaults = false): void
    {
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $useDefaults;
        $data['show_in_menu'] = $request->has('show_in_menu') ? $request->boolean('show_in_menu') : $useDefaults;
        $data['show_on_home'] = $request->has('show_on_home') ? $request->boolean('show_on_home') : $useDefaults;
    }

    private function ensureValidParent(?Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category?->id === $parentId || ($category && $this->isDescendant($category, $parentId))) {
            throw ValidationException::withMessages([
                'parent_id' => 'Una categoría no puede depender de sí misma ni de una de sus subcategorías.',
            ]);
        }
    }

    private function isDescendant(Category $category, int $candidateId): bool
    {
        $candidate = Category::query()->find($candidateId);
        $visited = [];

        while ($candidate !== null && ! in_array($candidate->id, $visited, true)) {
            if ($candidate->parent_id === $category->id) {
                return true;
            }

            $visited[] = $candidate->id;
            $candidate = $candidate->parent;
        }

        return false;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    private function asTree(Collection $categories): Collection
    {
        $grouped = $categories->groupBy(fn (Category $category) => $category->parent_id ?? 0);
        $result = collect();
        $visited = [];

        $append = function (int $parentId, int $depth) use (&$append, &$visited, $grouped, $result): void {
            foreach ($grouped->get($parentId, collect()) as $category) {
                if (isset($visited[$category->id])) {
                    continue;
                }

                $visited[$category->id] = true;
                $category->setAttribute('tree_depth', $depth);
                $result->push($category);
                $append($category->id, $depth + 1);
            }
        };

        $append(0, 0);

        foreach ($categories as $category) {
            if (! isset($visited[$category->id])) {
                $category->setAttribute('tree_depth', 0);
                $result->push($category);
            }
        }

        return $result;
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
