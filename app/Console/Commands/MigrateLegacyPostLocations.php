<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MigrateLegacyPostLocations extends Command
{
    protected $signature = 'editorial:migrate-locations
        {--apply : Guarda las coincidencias territoriales inequívocas}';

    protected $description = 'Detecta ubicaciones para noticias antiguas de las categorías Regionales y Locales';

    public function handle(): int
    {
        $locations = Location::query()
            ->active()
            ->whereIn('type', ['region', 'province', 'district'])
            ->with('parent.parent.parent')
            ->get();
        $posts = Post::query()
            ->whereNull('location_id')
            ->whereHas('category', fn ($query) => $query->whereIn('slug', ['regionales', 'locales']))
            ->with(['category', 'tags'])
            ->get();
        $matches = collect();
        $unresolved = collect();

        foreach ($posts as $post) {
            $location = $this->detectLocation($post, $locations);

            if ($location === null) {
                $unresolved->push($post);

                continue;
            }

            $matches->push([$post, $location]);

            if ($this->option('apply')) {
                $post->update(['location_id' => $location->id]);
            }
        }

        if ($matches->isNotEmpty()) {
            $this->table(
                ['Noticia', 'Categoría anterior', 'Ubicación detectada'],
                $matches->map(fn (array $match) => [
                    Str::limit($match[0]->title, 58),
                    $match[0]->category->name,
                    $match[1]->fullName(),
                ]),
            );
        }

        $action = $this->option('apply') ? 'actualizadas' : 'detectadas (vista previa)';
        $this->info("Coincidencias {$action}: {$matches->count()}.");

        if ($unresolved->isNotEmpty()) {
            $this->warn(
                "{$unresolved->count()} noticias quedaron sin ubicación porque no existe una coincidencia inequívoca. "
                .'Deben revisarse desde el editor.'
            );
        }

        if (! $this->option('apply') && $matches->isNotEmpty()) {
            $this->line('Ejecuta php artisan editorial:migrate-locations --apply para guardar estas coincidencias.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Location>  $locations
     */
    private function detectLocation(Post $post, Collection $locations): ?Location
    {
        $haystack = $this->normalize(collect([
            $post->title,
            $post->source_name,
            $post->tags->pluck('name')->implode(' '),
        ])->filter()->implode(' '));

        $matches = $locations
            ->filter(fn (Location $location) => str_contains(
                " {$haystack} ",
                ' '.$this->normalize($location->name).' ',
            ))
            ->groupBy(fn (Location $location) => $this->normalize($location->name))
            ->map(fn (Collection $sameName) => $this->safestSameNameMatch($sameName, $haystack))
            ->filter()
            ->values();

        return $matches
            ->sortByDesc(fn (Location $location) => array_search(
                $location->type,
                ['country', 'region', 'province', 'district'],
                true,
            ))
            ->first();
    }

    /**
     * @param  Collection<int, Location>  $locations
     */
    private function safestSameNameMatch(Collection $locations, string $haystack): ?Location
    {
        if ($locations->count() === 1) {
            return $locations->first();
        }

        return $locations
            ->sortBy(function (Location $location) use ($haystack): array {
                $ancestorMatches = $location->lineage()
                    ->reject(fn (Location $ancestor) => $ancestor->id === $location->id)
                    ->reject(fn (Location $ancestor) => $this->normalize($ancestor->name) === $this->normalize($location->name))
                    ->unique(fn (Location $ancestor) => $this->normalize($ancestor->name))
                    ->filter(fn (Location $ancestor) => str_contains(
                        " {$haystack} ",
                        ' '.$this->normalize($ancestor->name).' ',
                    ))
                    ->count();
                $depth = array_search(
                    $location->type,
                    ['country', 'region', 'province', 'district'],
                    true,
                );

                return [-$ancestorMatches, $depth];
            })
            ->first();
    }

    private function normalize(string $value): string
    {
        return preg_replace(
            '/[^a-z0-9]+/',
            ' ',
            mb_strtolower(Str::ascii($value)),
        ) ?: '';
    }
}
