<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\PortalSetting;
use Illuminate\Database\Seeder;

class AppearanceSeeder extends Seeder
{
    public function run(): void
    {
        PortalSetting::query()->updateOrCreate(
            ['key' => 'social.links'],
            [
                'group' => 'social',
                'value' => [
                    'facebook' => 'https://www.facebook.com/',
                    'x' => 'https://x.com/',
                    'tiktok' => 'https://www.tiktok.com/',
                    'instagram' => 'https://www.instagram.com/',
                    'youtube' => 'https://www.youtube.com/',
                ],
                'is_public' => true,
            ],
        );

        PortalSetting::query()->updateOrCreate(
            ['key' => 'article.sidebar'],
            [
                'group' => 'article',
                'value' => [
                    'modules' => ['most_read', 'latest', 'advertisements', 'social', 'categories'],
                    'most_read_limit' => 5,
                    'latest_limit' => 5,
                    'sticky' => true,
                ],
                'is_public' => true,
            ],
        );

        PortalSetting::query()->updateOrCreate(
            ['key' => 'section.sidebar'],
            [
                'group' => 'section',
                'value' => [
                    'modules' => ['most_read', 'latest', 'advertisements', 'categories', 'social'],
                    'most_read_limit' => 5,
                    'latest_limit' => 4,
                    'sticky' => true,
                    'adaptive' => true,
                ],
                'is_public' => true,
            ],
        );

        $campaigns = [
            [
                'name' => 'Campaña comercial principal',
                'image' => '/images/demo/ad-business.svg',
                'alt_text' => 'Espacio publicitario para conectar una marca con la audiencia',
                'sort_order' => 10,
            ],
            [
                'name' => 'Campaña comercial secundaria',
                'image' => '/images/demo/ad-community.svg',
                'alt_text' => 'Espacio publicitario para una campaña regional',
                'sort_order' => 20,
            ],
        ];

        foreach (['article_sidebar', 'section_sidebar'] as $placement) {
            foreach ($campaigns as $advertisement) {
                Advertisement::query()->updateOrCreate(
                    ['name' => $advertisement['name'], 'placement' => $placement],
                    $advertisement + [
                        'destination_url' => null,
                        'open_in_new_tab' => true,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
