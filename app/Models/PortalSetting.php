<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PortalSetting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'value', 'is_public'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, mixed $value, string $group, bool $isPublic = true): static
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $value,
                'is_public' => $isPublic,
            ],
        );

        Cache::forget('portal.settings.all');

        return $setting;
    }
}
