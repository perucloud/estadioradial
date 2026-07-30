<?php

namespace Tests\Unit;

use App\Support\HomeHeroConfig;
use PHPUnit\Framework\TestCase;

class HomeHeroConfigTest extends TestCase
{
    public function test_every_selectable_effect_has_a_public_css_implementation(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

        foreach (HomeHeroConfig::effects() as $effect) {
            $this->assertStringContainsString(
                sprintf('[data-hero-effect="%s"]', $effect),
                $css,
                "El efecto {$effect} no tiene una implementación CSS.",
            );
        }
    }

    public function test_presets_define_all_animation_and_behaviour_values(): void
    {
        $required = [
            'effect',
            'image_animation',
            'image_intensity',
            'content_animation',
            'transition_duration',
            'interval',
            'overlay_opacity',
            'preload_images',
            'pause_on_hover',
            'swipe',
            'lazy_load',
            'animate_when_visible',
            'show_arrows',
            'show_indicators',
            'loop',
            'pause_when_hidden',
            'reset_after_manual',
            'reduce_motion_mobile',
        ];

        foreach (HomeHeroConfig::presets() as $name => $preset) {
            $this->assertSame([], array_diff($required, array_keys($preset)), "El preset {$name} está incompleto.");
            $this->assertContains($preset['effect'], HomeHeroConfig::effects());
            $this->assertGreaterThanOrEqual(300, $preset['transition_duration']);
            $this->assertLessThanOrEqual(1500, $preset['transition_duration']);
            $this->assertGreaterThanOrEqual(3000, $preset['interval']);
            $this->assertLessThanOrEqual(20000, $preset['interval']);
        }
    }

    public function test_legacy_hero_configuration_is_merged_without_losing_its_behaviour(): void
    {
        $merged = HomeHeroConfig::merge([
            'effect' => 'parallax',
            'parallax' => true,
            'interval' => 8000,
            'news_limit' => 4,
        ]);

        $this->assertSame('custom', $merged['preset_mode']);
        $this->assertSame('parallax', $merged['effect']);
        $this->assertSame('parallax', $merged['image_animation']);
        $this->assertTrue($merged['parallax']);
        $this->assertSame(4, $merged['news_limit']);
    }
}
