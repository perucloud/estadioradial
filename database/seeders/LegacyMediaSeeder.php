<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LegacyMediaSeeder extends Seeder
{
    public function run(): void
    {
        Post::query()
            ->whereNull('media_id')
            ->whereNotNull('image')
            ->get()
            ->groupBy('image')
            ->each(function ($posts, string $image): void {
                if (! str_starts_with($image, '/images/demo/')) {
                    return;
                }

                $source = public_path(ltrim($image, '/'));
                $realSource = realpath($source);
                $allowedRoot = realpath(public_path('images/demo'));

                if ($realSource === false || $allowedRoot === false || ! str_starts_with($realSource, $allowedRoot)) {
                    return;
                }

                $contents = file_get_contents($realSource);

                if (! is_string($contents) || $contents === '') {
                    return;
                }

                $checksum = hash('sha256', $contents);
                $extension = mb_strtolower(pathinfo($realSource, PATHINFO_EXTENSION));
                $media = Media::query()->where('checksum', $checksum)->first();

                if (! $media) {
                    $directory = 'media/legacy/'.substr($checksum, 0, 16);
                    $path = "{$directory}/original.{$extension}";
                    Storage::disk('public')->put($path, $contents);

                    $media = Media::query()->create([
                        'disk' => 'public',
                        'path' => $path,
                        'variants' => [],
                        'original_name' => basename($realSource),
                        'mime_type' => $extension === 'svg' ? 'image/svg+xml' : mime_content_type($realSource),
                        'extension' => $extension,
                        'size' => strlen($contents),
                        'width' => 1200,
                        'height' => 675,
                        'alt_text' => 'Imagen referencial de '.mb_strtolower($posts->first()->title),
                        'credit' => 'Estación Radial',
                        'license' => 'Recurso gráfico propio',
                        'checksum' => $checksum,
                    ]);
                }

                Post::query()
                    ->whereIn('id', $posts->pluck('id'))
                    ->update([
                        'media_id' => $media->id,
                        'image' => $media->url('article'),
                    ]);
            });
    }
}
