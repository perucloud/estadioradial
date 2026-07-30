<?php

namespace App\Support;

final class HomeRegionalConfig
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
            'pagination_enabled' => false,
            'show_page_numbers' => true,
            'per_page' => 5,
            'region_id' => null,
            'province_id' => null,
            'district_id' => null,
            'highlight_province' => false,
            'highlight_district' => false,
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
