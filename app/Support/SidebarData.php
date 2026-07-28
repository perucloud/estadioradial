<?php

namespace App\Support;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\PortalSetting;
use App\Models\Post;

class SidebarData
{
    public function article(?int $excludePostId = null): array
    {
        return $this->build(
            settingKey: 'article.sidebar',
            placement: 'article_sidebar',
            defaults: [
                'modules' => ['most_read', 'latest', 'advertisements', 'social', 'categories'],
                'most_read_limit' => 5,
                'latest_limit' => 5,
                'sticky' => true,
            ],
            excludePostId: $excludePostId,
        );
    }

    public function section(): array
    {
        return $this->build(
            settingKey: 'section.sidebar',
            placement: 'section_sidebar',
            defaults: [
                'modules' => ['most_read', 'latest', 'advertisements', 'categories', 'social'],
                'most_read_limit' => 5,
                'latest_limit' => 4,
                'sticky' => true,
                'adaptive' => true,
            ],
        );
    }

    private function build(
        string $settingKey,
        string $placement,
        array $defaults,
        ?int $excludePostId = null,
    ): array {
        $storedSettings = PortalSetting::value($settingKey, []);
        $settings = array_replace($defaults, is_array($storedSettings) ? $storedSettings : []);
        $mostReadLimit = min(10, max(1, (int) $settings['most_read_limit']));
        $latestLimit = min(10, max(1, (int) $settings['latest_limit']));

        return [
            'sidebarSettings' => $settings,
            'sidebarMostRead' => Post::query()
                ->with('category')
                ->published()
                ->when($excludePostId, fn ($query) => $query->where('id', '!=', $excludePostId))
                ->orderByDesc('views_count')
                ->latest('published_at')
                ->take($mostReadLimit)
                ->get(),
            'sidebarLatest' => Post::query()
                ->with('category')
                ->published()
                ->when($excludePostId, fn ($query) => $query->where('id', '!=', $excludePostId))
                ->latest('published_at')
                ->take($latestLimit)
                ->get(),
            'sidebarAdvertisements' => Advertisement::query()
                ->currentlyActive()
                ->where('placement', $placement)
                ->orderBy('sort_order')
                ->get(),
            'sidebarCategories' => Category::query()
                ->where('is_active', true)
                ->where('show_in_menu', true)
                ->withCount(['posts' => fn ($query) => $query->published()])
                ->orderBy('display_order')
                ->orderByDesc('relevance_weight')
                ->get(),
            'sidebarSocialLinks' => $this->socialLinks(),
        ];
    }

    private function socialLinks(): array
    {
        $storedLinks = PortalSetting::value('social.links', []);

        return array_replace(
            $this->defaultSocialLinks(),
            is_array($storedLinks) ? $storedLinks : [],
        );
    }

    private function defaultSocialLinks(): array
    {
        return [
            'facebook' => 'https://www.facebook.com/',
            'x' => 'https://x.com/',
            'tiktok' => 'https://www.tiktok.com/',
            'instagram' => 'https://www.instagram.com/',
            'youtube' => 'https://www.youtube.com/',
        ];
    }
}
