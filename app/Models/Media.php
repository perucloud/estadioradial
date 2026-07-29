<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'disk',
    'path',
    'variants',
    'original_name',
    'mime_type',
    'extension',
    'size',
    'width',
    'height',
    'alt_text',
    'caption',
    'credit',
    'license',
    'checksum',
    'uploaded_by',
])]
class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function featuredPosts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function inlinePosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function url(string $variant = 'article'): string
    {
        $path = $this->variants[$variant] ?? $this->path;

        return Storage::disk($this->disk)->url($path);
    }

    public function isInUse(): bool
    {
        return $this->featuredPosts()->exists()
            || $this->inlinePosts()->exists()
            || $this->programs()->exists()
            || $this->streams()->exists();
    }
}
