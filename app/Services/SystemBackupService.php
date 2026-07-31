<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class SystemBackupService
{
    public function create(bool $includeMedia = true): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP no está disponible en PHP.');
        }

        $directory = Storage::disk('local')->path('backups');
        File::ensureDirectoryExists($directory);
        $filename = 'estacionradial-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($directory.DIRECTORY_SEPARATOR.$filename, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No fue posible crear el archivo de respaldo.');
        }

        $manifest = [
            'created_at' => now()->toIso8601String(),
            'database' => config('database.default'),
            'application' => config('app.name'),
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        foreach (DB::connection()->getSchemaBuilder()->getTableListing() as $table) {
            $rows = DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all();
            $zip->addFromString(
                'database/'.$table.'.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
            );
        }

        if ($includeMedia) {
            $mediaRoot = Storage::disk('public')->path('');
            if (is_dir($mediaRoot)) {
                foreach (File::allFiles($mediaRoot) as $file) {
                    $zip->addFile($file->getPathname(), 'media/'.$file->getRelativePathname());
                }
            }
        }

        $zip->close();

        return $filename;
    }

    public function files(): array
    {
        $directory = Storage::disk('local')->path('backups');
        File::ensureDirectoryExists($directory);

        return collect(File::files($directory))
            ->filter(fn ($file) => $file->getExtension() === 'zip')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function path(string $filename): string
    {
        $safe = basename($filename);
        $path = Storage::disk('local')->path('backups/'.$safe);

        abort_unless($safe === $filename && is_file($path), 404);

        return $path;
    }

    public function delete(string $filename): void
    {
        File::delete($this->path($filename));
    }

    public function enforceRetention(int $retention): void
    {
        foreach (array_slice($this->files(), max(1, $retention)) as $file) {
            File::delete(Storage::disk('local')->path('backups/'.$file['name']));
        }
    }
}
