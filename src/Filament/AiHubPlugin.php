<?php

namespace ImranDevBd\AiHub\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Optional Filament plugin. Auto-registered when Filament is installed;
 * you can also add it explicitly: ->plugin(AiHubPlugin::make())
 */
class AiHubPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'ai-hub';
    }

    public function register(Panel $panel): void
    {
        $panel->navigationItems([AiHubNavigation::item()]);
    }

    public function boot(Panel $panel): void {}
}
