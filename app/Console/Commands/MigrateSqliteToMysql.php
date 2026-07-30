<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class MigrateSqliteToMysql extends Command
{
    protected $signature = 'db:migrate-sqlite-to-mysql
        {--dry-run : Verifica conexiones, esquema y conteos sin copiar datos}
        {--execute : Copia y verifica todos los datos}
        {--source=migration_sqlite : Conexión SQLite de origen}
        {--target=migration_mysql : Conexión MySQL de destino}
        {--chunk=500 : Cantidad de registros por lote}';

    protected $description = 'Copia de forma segura una base SQLite hacia un esquema MySQL vacío';

    /**
     * @var array<string, array{source: int, target: int}>
     */
    private array $counts = [];

    public function handle(): int
    {
        if ($this->option('dry-run') === $this->option('execute')) {
            $this->error('Debes indicar exactamente una opción: --dry-run o --execute.');

            return self::INVALID;
        }

        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize < 1 || $chunkSize > 5000) {
            $this->error('El tamaño de lote debe estar entre 1 y 5000.');

            return self::INVALID;
        }

        $sourceName = (string) $this->option('source');
        $targetName = (string) $this->option('target');

        try {
            $source = DB::connection($sourceName);
            $target = DB::connection($targetName);
            $this->validateDrivers($source, $target);
            $tables = $this->validateSchemas($source, $target);
            $this->assertTargetIsEmpty($target, $tables);
            $this->counts = $this->collectCounts($source, $target, $tables);
            $this->renderCounts('Estado previo');

            if ($this->option('dry-run')) {
                $this->info('Simulación correcta: MySQL está vacío y preparado para recibir los datos.');

                return self::SUCCESS;
            }

            $this->copyTables($source, $target, $tables, $chunkSize);
            $this->counts = $this->collectCounts($source, $target, $tables);
            $this->renderCounts('Comparación final SQLite / MySQL');

            $differences = collect($this->counts)
                ->filter(fn (array $count) => $count['source'] !== $count['target']);
            $missingIds = $this->findMissingIds($source, $target, $tables);
            $orphans = $this->findOrphans($target);

            if ($differences->isNotEmpty() || $missingIds->isNotEmpty() || $orphans->isNotEmpty()) {
                $this->renderVerificationErrors($differences, $missingIds, $orphans);

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('Migración verificada: conteos, IDs y claves foráneas coinciden.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Migración abortada: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function validateDrivers(Connection $source, Connection $target): void
    {
        if ($source->getDriverName() !== 'sqlite') {
            throw new RuntimeException('La conexión de origen debe usar SQLite.');
        }

        if (! in_array($target->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('La conexión de destino debe usar MySQL o MariaDB.');
        }

        $source->getPdo();
        $target->getPdo();
    }

    /**
     * @return array<int, string>
     */
    private function validateSchemas(Connection $source, Connection $target): array
    {
        $sourceTables = collect($source->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        ))->pluck('name')->values();
        $targetTables = collect($target->select(
            <<<'SQL'
                SELECT TABLE_NAME AS name
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME
            SQL,
            [$target->getDatabaseName()],
        ))->pluck('name')->values();
        $missing = $sourceTables->diff($targetTables);
        $unexpected = $targetTables->diff($sourceTables);

        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Faltan tablas en MySQL: '.$missing->implode(', ').'.');
        }

        if ($unexpected->isNotEmpty()) {
            throw new RuntimeException('MySQL contiene tablas inesperadas: '.$unexpected->implode(', ').'.');
        }

        return $sourceTables->all();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function assertTargetIsEmpty(Connection $target, array $tables): void
    {
        $populated = collect($tables)
            ->reject(fn (string $table) => $table === 'migrations')
            ->mapWithKeys(fn (string $table) => [$table => $target->table($table)->count()])
            ->filter(fn (int $count) => $count > 0);

        if ($populated->isNotEmpty()) {
            throw new RuntimeException(
                'MySQL ya contiene datos y no se modificará. Tablas ocupadas: '.
                $populated->map(fn (int $count, string $table) => "{$table} ({$count})")->implode(', ').'.'
            );
        }
    }

    /**
     * @param  array<int, string>  $tables
     * @return array<string, array{source: int, target: int}>
     */
    private function collectCounts(Connection $source, Connection $target, array $tables): array
    {
        return collect($tables)->mapWithKeys(fn (string $table) => [
            $table => [
                'source' => $source->table($table)->count(),
                'target' => $target->table($table)->count(),
            ],
        ])->all();
    }

    private function renderCounts(string $title): void
    {
        $this->newLine();
        $this->components->info($title);
        $this->table(
            ['Tabla', 'SQLite', 'MySQL', 'Estado'],
            collect($this->counts)->map(fn (array $count, string $table) => [
                $table,
                $count['source'],
                $count['target'],
                $count['source'] === $count['target'] ? 'Igual' : 'Pendiente',
            ])->values()->all(),
        );
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function copyTables(
        Connection $source,
        Connection $target,
        array $tables,
        int $chunkSize,
    ): void {
        $copyTables = collect($tables)->reject(fn (string $table) => $table === 'migrations');
        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $target->transaction(function () use ($source, $target, $copyTables, $chunkSize): void {
                foreach ($copyTables as $table) {
                    $total = $source->table($table)->count();
                    if ($total === 0) {
                        $this->line("  {$table}: sin registros");

                        continue;
                    }

                    $columns = Schema::connection($source->getName())->getColumnListing($table);
                    $orderColumns = in_array('id', $columns, true) ? ['id'] : $columns;
                    $copied = 0;
                    $query = $source->table($table);

                    foreach ($orderColumns as $orderColumn) {
                        $query->orderBy($orderColumn);
                    }

                    $query->chunk($chunkSize, function (Collection $rows) use (
                        $target,
                        $table,
                        &$copied,
                    ): void {
                        $payload = $rows
                            ->map(fn (object $row) => (array) $row)
                            ->all();
                        $target->table($table)->insert($payload);
                        $copied += count($payload);
                    });

                    $this->line("  {$table}: {$copied}/{$total}");
                }
            });
        } finally {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @param  array<int, string>  $tables
     * @return Collection<string, array<int, int|string>>
     */
    private function findMissingIds(Connection $source, Connection $target, array $tables): Collection
    {
        return collect($tables)
            ->filter(fn (string $table) => in_array(
                'id',
                Schema::connection($source->getName())->getColumnListing($table),
                true,
            ))
            ->mapWithKeys(function (string $table) use ($source, $target): array {
                $sourceIds = $source->table($table)->orderBy('id')->pluck('id');
                $targetIds = $target->table($table)->orderBy('id')->pluck('id');
                $missing = $sourceIds->diff($targetIds)
                    ->merge($targetIds->diff($sourceIds))
                    ->unique()
                    ->values();

                return $missing->isEmpty() ? [] : [$table => $missing->all()];
            });
    }

    /**
     * @return Collection<int, array{relation: string, count: int}>
     */
    private function findOrphans(Connection $target): Collection
    {
        $database = (string) $target->getDatabaseName();
        $foreignKeys = collect($target->select(
            <<<'SQL'
                SELECT TABLE_NAME AS child_table,
                       COLUMN_NAME AS child_column,
                       REFERENCED_TABLE_NAME AS parent_table,
                       REFERENCED_COLUMN_NAME AS parent_column
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY TABLE_NAME, COLUMN_NAME
            SQL,
            [$database],
        ));

        return $foreignKeys->map(function (object $foreignKey) use ($target): array {
            $childTable = (string) $foreignKey->child_table;
            $childColumn = (string) $foreignKey->child_column;
            $parentTable = (string) $foreignKey->parent_table;
            $parentColumn = (string) $foreignKey->parent_column;
            $count = $target->table("{$childTable} as child")
                ->leftJoin(
                    "{$parentTable} as parent",
                    "child.{$childColumn}",
                    '=',
                    "parent.{$parentColumn}",
                )
                ->whereNotNull("child.{$childColumn}")
                ->whereNull("parent.{$parentColumn}")
                ->count();

            return [
                'relation' => "{$childTable}.{$childColumn} → {$parentTable}.{$parentColumn}",
                'count' => $count,
            ];
        })->filter(fn (array $result) => $result['count'] > 0)->values();
    }

    /**
     * @param  Collection<string, array{source: int, target: int}>  $differences
     * @param  Collection<string, array<int, int|string>>  $missingIds
     * @param  Collection<int, array{relation: string, count: int}>  $orphans
     */
    private function renderVerificationErrors(
        Collection $differences,
        Collection $missingIds,
        Collection $orphans,
    ): void {
        if ($differences->isNotEmpty()) {
            $this->error('Existen diferencias de conteo: '.$differences->keys()->implode(', ').'.');
        }

        if ($missingIds->isNotEmpty()) {
            $this->error('Existen diferencias de IDs: '.$missingIds->keys()->implode(', ').'.');
        }

        foreach ($orphans as $orphan) {
            $this->error("Relación huérfana {$orphan['relation']}: {$orphan['count']}.");
        }
    }
}
