<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'image',
        'author',
        'status',
        'is_featured',
        'views_count',
        'source_name',
        'source_url',
        'image_credit',
        'image_license',
        'editorial_priority',
        'is_homepage_hidden',
        'pinned_until',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'editorial_priority' => 'integer',
            'is_homepage_hidden' => 'boolean',
            'pinned_until' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeVisibleOnHome(Builder $query): Builder
    {
        return $query
            ->where('is_homepage_hidden', false)
            ->whereHas('category', fn (Builder $query) => $query
                ->where('is_active', true)
                ->where('show_on_home', true));
    }

    public function scopeEditorialOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN pinned_until IS NOT NULL AND pinned_until >= ? THEN 1 ELSE 0 END DESC', [now()])
            ->orderByDesc('editorial_priority')
            ->orderByDesc(
                Category::query()
                    ->select('relevance_weight')
                    ->whereColumn('categories.id', 'posts.category_id')
                    ->limit(1)
            )
            ->latest('published_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
