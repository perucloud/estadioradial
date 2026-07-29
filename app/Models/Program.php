<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'hosts',
        'image',
        'media_id',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'display_order' => 'integer'];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function presenters(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function imageUrl(): string
    {
        return $this->media?->url('article') ?? $this->image ?? '/images/demo/program-news.svg';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
