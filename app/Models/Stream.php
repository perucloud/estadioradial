<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stream extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'format',
        'url',
        'cover',
        'media_id',
        'is_active',
        'is_primary',
        'fallback_message',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_primary' => 'boolean', 'sort_order' => 'integer'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function coverUrl(): string
    {
        return $this->media?->url('article') ?? $this->cover ?? '/images/demo/stream-cover.svg';
    }

    public function playbackUrl(): ?string
    {
        if (! $this->url) {
            return null;
        }

        if ($this->format === 'youtube') {
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|live/|embed/))([^?&/]+)~', $this->url, $match)) {
                return 'https://www.youtube.com/embed/'.$match[1];
            }
        }

        return $this->url;
    }
}
