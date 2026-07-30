<?php

namespace App\Support;

final class HomeHeroConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'mode' => 'automatic',
            'interval' => 8000,
            'loop' => true,
            'effect' => 'parallax',
            'parallax' => true,
            'news_limit' => 4,
            'quantity_mode' => 'specific',
            'selection_mode' => 'automatic',
            'sort_order' => 'latest',
            'post_ids' => [],
            'category_mode' => 'all',
            'category_ids' => [],
            'preset_mode' => 'custom',
            'image_animation' => 'parallax',
            'image_intensity' => 'soft',
            'content_animation' => 'fade-up',
            'transition_duration' => 800,
            'overlay_opacity' => 0,
            'preload_images' => true,
            'pause_on_hover' => true,
            'swipe' => true,
            'lazy_load' => true,
            'animate_when_visible' => true,
            'show_arrows' => true,
            'show_indicators' => true,
            'pause_when_hidden' => true,
            'reset_after_manual' => true,
            'reduce_motion_mobile' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public static function merge(array $stored): array
    {
        $settings = array_replace(self::defaults(), $stored);

        if ($stored !== [] && ! array_key_exists('preset_mode', $stored)) {
            $settings['preset_mode'] = 'custom';
        }

        // Compatibilidad con los tres efectos disponibles antes de esta ampliación.
        if ($settings['effect'] === 'slide') {
            $settings['effect'] = 'slide-horizontal';
        }

        if ($settings['effect'] === 'parallax' && ! array_key_exists('image_animation', $stored)) {
            $settings['image_animation'] = 'parallax';
            $settings['parallax'] = (bool) ($stored['parallax'] ?? true);
        }

        return $settings;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return [
            'elegant' => [
                'effect' => 'fade',
                'image_animation' => 'ken-burns',
                'image_intensity' => 'soft',
                'content_animation' => 'fade-up',
                'transition_duration' => 800,
                'interval' => 8000,
                'overlay_opacity' => 0,
                'preload_images' => true,
                'pause_on_hover' => true,
                'swipe' => true,
                'lazy_load' => true,
                'animate_when_visible' => true,
                'show_arrows' => true,
                'show_indicators' => true,
                'loop' => true,
                'pause_when_hidden' => true,
                'reset_after_manual' => true,
                'reduce_motion_mobile' => true,
            ],
            'dynamic' => [
                'effect' => 'slide-horizontal',
                'image_animation' => 'zoom-in',
                'image_intensity' => 'medium',
                'content_animation' => 'slide-left',
                'transition_duration' => 500,
                'interval' => 5000,
                'overlay_opacity' => 0,
                'preload_images' => true,
                'pause_on_hover' => true,
                'swipe' => true,
                'lazy_load' => true,
                'animate_when_visible' => true,
                'show_arrows' => true,
                'show_indicators' => true,
                'loop' => true,
                'pause_when_hidden' => true,
                'reset_after_manual' => true,
                'reduce_motion_mobile' => true,
            ],
            'cinematic' => [
                'effect' => 'scale-fade',
                'image_animation' => 'ken-burns',
                'image_intensity' => 'soft-slow',
                'content_animation' => 'fade-up',
                'transition_duration' => 1200,
                'interval' => 10000,
                'overlay_opacity' => 30,
                'preload_images' => true,
                'pause_on_hover' => true,
                'swipe' => true,
                'lazy_load' => true,
                'animate_when_visible' => true,
                'show_arrows' => true,
                'show_indicators' => false,
                'loop' => true,
                'pause_when_hidden' => true,
                'reset_after_manual' => true,
                'reduce_motion_mobile' => true,
            ],
            'minimal' => [
                'effect' => 'fade',
                'image_animation' => 'none',
                'image_intensity' => 'soft',
                'content_animation' => 'fade',
                'transition_duration' => 600,
                'interval' => 6000,
                'overlay_opacity' => 0,
                'preload_images' => true,
                'pause_on_hover' => true,
                'swipe' => true,
                'lazy_load' => true,
                'animate_when_visible' => true,
                'show_arrows' => false,
                'show_indicators' => false,
                'loop' => true,
                'pause_when_hidden' => true,
                'reset_after_manual' => true,
                'reduce_motion_mobile' => true,
            ],
        ];
    }

    public static function effects(): array
    {
        return [
            'fade',
            'slide-horizontal',
            'slide-vertical',
            'push',
            'zoom-in',
            'zoom-out',
            'ken-burns',
            'scale-fade',
            'parallax',
            'blur',
            'cards-stack',
            'cinematic',
        ];
    }
}
