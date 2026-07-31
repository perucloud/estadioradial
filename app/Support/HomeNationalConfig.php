<?php

namespace App\Support;

final class HomeNationalConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'category_mode' => 'all',
            'category_ids' => [],
            'sort_order' => 'latest',
            'news_limit' => 5,
            'coverage_mode' => 'national_only',
            'exclude_regional_duplicates' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public static function merge(array $stored): array
    {
        return array_replace(self::defaults(), $stored);
    }
}
