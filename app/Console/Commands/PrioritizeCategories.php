<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class PrioritizeCategories extends Command
{
    protected $signature = 'editorial:prioritize
        {categories* : Slugs en el orden de importancia deseado}
        {--hide-missing : Oculta de portada las categorías no incluidas}';

    protected $description = 'Define el orden y la relevancia de las categorías en menú y portada';

    public function handle(): int
    {
        $slugs = collect($this->argument('categories'))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->unique()
            ->values();

        $categories = Category::query()->whereIn('slug', $slugs)->get()->keyBy('slug');
        $missing = $slugs->diff($categories->keys());

        if ($missing->isNotEmpty()) {
            $this->error('Categorías inexistentes: '.$missing->join(', '));

            return self::FAILURE;
        }

        foreach ($slugs as $index => $slug) {
            $categories[$slug]->update([
                'display_order' => ($index + 1) * 10,
                'relevance_weight' => max(1, 100 - ($index * 5)),
                'is_active' => true,
                'show_in_menu' => true,
                'show_on_home' => true,
            ]);
        }

        if ($this->option('hide-missing')) {
            Category::query()
                ->whereNotIn('slug', $slugs)
                ->update(['show_on_home' => false]);
        }

        $this->info('Prioridad editorial actualizada: '.$slugs->join(' > '));

        return self::SUCCESS;
    }
}
