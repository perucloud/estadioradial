<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'country' => 'País',
        'region' => 'Región',
        'province' => 'Provincia',
        'district' => 'Distrito',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'type',
        'country_code',
        'ubigeo',
        'latitude',
        'longitude',
        'description',
        'seo_title',
        'seo_description',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return Collection<int, Location>
     */
    public function lineage(): Collection
    {
        $lineage = collect();
        $current = $this;
        $visited = [];

        while ($current !== null && ! isset($visited[$current->id])) {
            $visited[$current->id] = true;
            $lineage->prepend($current);
            $current = $current->parent;
        }

        return $lineage->values();
    }

    public function fullName(): string
    {
        return $this->lineage()->pluck('name')->implode(' → ');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
