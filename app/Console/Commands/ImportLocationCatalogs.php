<?php

namespace App\Console\Commands;

use App\Support\LocationCatalogImporter;
use Illuminate\Console\Command;

class ImportLocationCatalogs extends Command
{
    protected $signature = 'locations:import-catalogs';

    protected $description = 'Sincroniza países y el catálogo territorial completo del Perú';

    public function handle(LocationCatalogImporter $importer): int
    {
        $this->info('Sincronizando catálogos geográficos locales…');
        $summary = $importer->import();

        $this->table(
            ['Nivel', 'Registros'],
            collect($summary)->map(fn (int $count, string $level) => [$level, $count])->values()->all(),
        );
        $this->info('Catálogos sincronizados sin eliminar ubicaciones relacionadas.');

        return self::SUCCESS;
    }
}
