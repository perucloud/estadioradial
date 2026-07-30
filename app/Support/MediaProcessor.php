<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaProcessor
{
    /**
     * @param  array{alt_text: string, caption?: ?string, credit?: ?string, license?: ?string}  $metadata
     */
    public function store(UploadedFile $file, array $metadata, ?int $userId): Media
    {
        $contents = $file->get();
        $dimensions = getimagesizefromstring($contents);

        if ($dimensions === false) {
            throw new RuntimeException('El archivo no contiene una imagen válida.');
        }

        $extension = mb_strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $directory = 'media/'.now()->format('Y/m').'/'.Str::uuid();
        $originalPath = "{$directory}/original.{$extension}";
        $disk = Storage::disk('public');

        try {
            if (! $disk->put($originalPath, $contents)) {
                throw new RuntimeException('No se pudo guardar la imagen.');
            }

            $variants = $this->createVariants($contents, $directory);

            return Media::query()->create([
                'disk' => 'public',
                'path' => $originalPath,
                'variants' => $variants,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'alt_text' => trim($metadata['alt_text']),
                'caption' => $this->nullable($metadata['caption'] ?? null),
                'credit' => $this->nullable($metadata['credit'] ?? null),
                'license' => $this->nullable($metadata['license'] ?? null),
                'checksum' => hash('sha256', $contents),
                'uploaded_by' => $userId,
            ]);
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        }
    }

    public function replace(Media $media, UploadedFile $file): Media
    {
        $contents = $file->get();
        $dimensions = getimagesizefromstring($contents);

        if ($dimensions === false) {
            throw new RuntimeException('El archivo no contiene una imagen válida.');
        }

        $extension = mb_strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $directory = 'media/'.now()->format('Y/m').'/'.Str::uuid();
        $originalPath = "{$directory}/original.{$extension}";
        $disk = Storage::disk('public');
        $previousDisk = $media->disk;
        $previousFiles = array_values(array_unique([
            $media->path,
            ...array_values($media->variants ?? []),
        ]));

        try {
            if (! $disk->put($originalPath, $contents)) {
                throw new RuntimeException('No se pudo guardar la nueva imagen.');
            }

            $variants = $this->createVariants($contents, $directory);

            $media->update([
                'disk' => 'public',
                'path' => $originalPath,
                'variants' => $variants,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'checksum' => hash('sha256', $contents),
            ]);
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        }

        Storage::disk($previousDisk)->delete($previousFiles);

        return $media->refresh();
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->deleteDirectory(dirname($media->path));
        $media->delete();
    }

    /**
     * @return array<string, string>
     */
    private function createVariants(string $contents, string $directory): array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return [];
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return [];
        }

        $variants = [];

        try {
            foreach (['thumb' => 420, 'card' => 800, 'article' => 1600] as $name => $maximumWidth) {
                $width = imagesx($source);
                $height = imagesy($source);
                $targetWidth = min($maximumWidth, $width);
                $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
                $target = imagecreatetruecolor($targetWidth, $targetHeight);

                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
                imagefill($target, 0, 0, $transparent);
                imagecopyresampled(
                    $target,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $width,
                    $height,
                );

                ob_start();
                imagewebp($target, null, 84);
                $webp = ob_get_clean();
                imagedestroy($target);

                if (! is_string($webp) || $webp === '') {
                    continue;
                }

                $path = "{$directory}/{$name}.webp";
                Storage::disk('public')->put($path, $webp);
                $variants[$name] = $path;
            }
        } finally {
            imagedestroy($source);
        }

        return $variants;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
