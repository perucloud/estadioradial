<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertLocationRequest;
use App\Models\ActivityLog;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocationController extends Controller
{
    private const PARENT_TYPE = [
        'country' => null,
        'region' => 'country',
        'province' => 'region',
        'district' => 'province',
    ];

    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q')), 0, 100);
        $status = in_array($request->query('status'), ['active', 'inactive', 'trash'], true)
            ? (string) $request->query('status')
            : '';
        $type = array_key_exists((string) $request->query('type'), Location::TYPES)
            ? (string) $request->query('type')
            : '';
        $parent = (string) $request->query('parent', '');
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 50, 100], true)
            ? (int) $request->query('per_page')
            : 20;

        $query = Location::query()
            ->with('parent')
            ->withCount(['children', 'posts'])
            ->orderBy('display_order')
            ->orderBy('name');

        if ($status === 'trash') {
            $query->onlyTrashed();
        }

        $ordered = $status === 'trash' ? $query->get() : $this->asTree($query->get());
        $filtered = $ordered
            ->when($search !== '', fn (Collection $locations) => $locations->filter(
                fn (Location $location) => str_contains(
                    mb_strtolower($location->name.' '.$location->slug.' '.$location->ubigeo),
                    mb_strtolower($search),
                )
            ))
            ->when($status === 'active', fn (Collection $locations) => $locations->where('is_active', true))
            ->when($status === 'inactive', fn (Collection $locations) => $locations->where('is_active', false))
            ->when($type !== '', fn (Collection $locations) => $locations->where('type', $type))
            ->when($parent === 'root', fn (Collection $locations) => $locations->whereNull('parent_id'))
            ->when(ctype_digit($parent), fn (Collection $locations) => $locations->where('parent_id', (int) $parent))
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $locations = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
        $parentOptions = $this->asTree(Location::query()
            ->with('parent')
            ->withCount(['children', 'posts'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get());

        return view('admin.taxonomy.locations', [
            'locations' => $locations,
            'parentOptions' => $parentOptions,
            'types' => Location::TYPES,
            'status' => $status,
            'typeFilter' => $type,
            'parentFilter' => $parent,
            'search' => $search,
            'perPage' => $perPage,
            'stats' => [
                'total' => Location::query()->count(),
                'countries' => Location::query()->where('type', 'country')->count(),
                'regions' => Location::query()->where('type', 'region')->count(),
                'provinces' => Location::query()->where('type', 'province')->count(),
                'districts' => Location::query()->where('type', 'district')->count(),
                'trash' => Location::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(UpsertLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->validateHierarchy(null, $data);
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['display_order'] = (int) Location::query()
            ->where('parent_id', $data['parent_id'])
            ->max('display_order') + 10;

        $location = Location::query()->create($data);
        $this->log($request, 'location.created', $location);

        return back()->with('status', 'Ubicación creada correctamente.');
    }

    public function update(UpsertLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $this->validateHierarchy($location, $data);
        $data['is_active'] = $request->boolean('is_active');

        $location->update($data);
        $this->log($request, 'location.updated', $location);

        return back()->with('status', 'Ubicación actualizada correctamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($data): void {
            foreach ($data['order'] as $id => $order) {
                Location::query()->whereKey((int) $id)->update(['display_order' => $order]);
            }
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'locations.reordered',
            'properties' => ['ids' => array_keys($data['order'])],
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return back()->with('status', 'Orden geográfico actualizado.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        if ($location->posts()->exists()) {
            return back()->withErrors([
                'location' => 'No se puede eliminar una ubicación utilizada por noticias. Reasigna primero esas publicaciones.',
            ]);
        }

        if ($location->children()->exists()) {
            return back()->withErrors([
                'location' => 'No se puede eliminar una ubicación que contiene divisiones territoriales. Elimina o traslada primero sus elementos hijos.',
            ]);
        }

        $this->log($request, 'location.deleted', $location);
        $location->delete();

        return back()->with('status', 'Ubicación enviada a la papelera.');
    }

    public function restore(Request $request, int $location): RedirectResponse
    {
        $locationModel = Location::onlyTrashed()->findOrFail($location);

        if ($locationModel->posts()->exists()) {
            return back()->withErrors([
                'location' => 'No se puede eliminar definitivamente una ubicación utilizada por noticias.',
            ]);
        }

        if ($locationModel->parent_id !== null && ! Location::query()->whereKey($locationModel->parent_id)->exists()) {
            return back()->withErrors([
                'location' => 'Restaura primero la ubicación superior.',
            ]);
        }

        $locationModel->restore();
        $this->log($request, 'location.restored', $locationModel);

        return back()->with('status', 'Ubicación restaurada.');
    }

    public function forceDestroy(Request $request, int $location): RedirectResponse
    {
        $locationModel = Location::onlyTrashed()->findOrFail($location);
        $this->log($request, 'location.force_deleted', $locationModel);
        $locationModel->forceDelete();

        return back()->with('status', 'Ubicación eliminada definitivamente.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateHierarchy(?Location $location, array &$data): void
    {
        $type = (string) $data['type'];
        $expectedParentType = self::PARENT_TYPE[$type];
        $parent = isset($data['parent_id'])
            ? Location::query()->find((int) $data['parent_id'])
            : null;

        if ($expectedParentType === null && $parent !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Un país debe estar en el nivel principal.',
            ]);
        }

        if ($expectedParentType !== null && $parent?->type !== $expectedParentType) {
            throw ValidationException::withMessages([
                'parent_id' => 'Una '.$this->typeLabel($type).' debe depender de una '.$this->typeLabel($expectedParentType).'.',
            ]);
        }

        if ($type === 'country' && blank($data['country_code'] ?? null)) {
            throw ValidationException::withMessages([
                'country_code' => 'Indica el código ISO de dos letras para el país.',
            ]);
        }

        if ($type !== 'country') {
            $data['country_code'] = null;
        }

        if ($location !== null) {
            if ($parent?->id === $location->id || ($parent && $this->isDescendant($location, $parent))) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Una ubicación no puede depender de sí misma ni de uno de sus descendientes.',
                ]);
            }

            $invalidChild = $location->children->first(
                fn (Location $child) => (self::PARENT_TYPE[$child->type] ?? null) !== $type
            );

            if ($invalidChild !== null) {
                throw ValidationException::withMessages([
                    'type' => 'No puedes cambiar el tipo porque dejaría inválida la sububicación '.$invalidChild->name.'.',
                ]);
            }
        }
    }

    private function isDescendant(Location $location, Location $candidate): bool
    {
        $visited = [];

        while ($candidate->parent !== null && ! in_array($candidate->id, $visited, true)) {
            if ($candidate->parent_id === $location->id) {
                return true;
            }

            $visited[] = $candidate->id;
            $candidate = $candidate->parent;
        }

        return false;
    }

    private function typeLabel(string $type): string
    {
        return mb_strtolower(Location::TYPES[$type] ?? $type);
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return Collection<int, Location>
     */
    private function asTree(Collection $locations): Collection
    {
        $grouped = $locations->groupBy(fn (Location $location) => $location->parent_id ?? 0);
        $result = collect();
        $visited = [];

        $append = function (int $parentId, int $depth) use (&$append, &$visited, $grouped, $result): void {
            foreach ($grouped->get($parentId, collect()) as $location) {
                if (isset($visited[$location->id])) {
                    continue;
                }

                $visited[$location->id] = true;
                $location->setAttribute('tree_depth', $depth);
                $result->push($location);
                $append($location->id, $depth + 1);
            }
        };

        $append(0, 0);

        foreach ($locations as $location) {
            if (! isset($visited[$location->id])) {
                $location->setAttribute('tree_depth', 0);
                $result->push($location);
            }
        }

        return $result;
    }

    private function log(Request $request, string $action, Location $location): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $location->getMorphClass(),
            'subject_id' => $location->id,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
    }
}
