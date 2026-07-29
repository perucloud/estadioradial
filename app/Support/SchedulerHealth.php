<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SchedulerHealth
{
    private const CACHE_KEY = 'scheduler.health';

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $health = Cache::get(self::CACHE_KEY, []);
        $lastCompletedAt = isset($health['last_completed_at'])
            ? CarbonImmutable::parse($health['last_completed_at'])
            : null;

        return array_merge([
            'last_started_at' => null,
            'last_completed_at' => null,
            'last_error' => null,
            'published_count' => 0,
        ], $health, [
            'active' => empty($health['last_error'])
                && ($lastCompletedAt?->greaterThanOrEqualTo(now()->subMinutes(3)) ?? false),
        ]);
    }

    public function started(): void
    {
        $this->store([
            'last_started_at' => now()->toIso8601String(),
            'last_error' => null,
        ]);
    }

    public function completed(int $publishedCount): void
    {
        $this->store([
            'last_completed_at' => now()->toIso8601String(),
            'last_error' => null,
            'published_count' => $publishedCount,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->store([
            'last_completed_at' => now()->toIso8601String(),
            'last_error' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function store(array $values): void
    {
        Cache::forever(self::CACHE_KEY, array_merge(
            Cache::get(self::CACHE_KEY, []),
            $values,
        ));
    }
}
