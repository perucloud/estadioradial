<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'is_active',
        'show_in_menu',
        'show_on_home',
        'display_order',
        'relevance_weight',
        'homepage_limit',
        'homepage_layout',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'show_on_home' => 'boolean',
            'display_order' => 'integer',
            'relevance_weight' => 'integer',
            'homepage_limit' => 'integer',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
