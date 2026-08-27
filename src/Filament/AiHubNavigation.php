<?php

namespace ImranDevBd\AiHub\Filament;

use Filament\Navigation\NavigationItem;

class AiHubNavigation
{
    public static function studioUrl(): string
    {
        try {
            return route('ai-hub.index');
        } catch (\Throwable) {
            return url('/'.trim((string) config('ai-hub.settings.route_prefix', 'ai-hub'), '/'));
        }
    }

    public static function openInNewTab(): bool
    {
        return (bool) config('ai-hub.filament.open_in_new_tab', true);
    }

    public static function item(): NavigationItem
    {
        $group = config('ai-hub.filament.navigation_group');

        return NavigationItem::make((string) config('ai-hub.filament.navigation_label', 'AI Hub'))
            ->url(fn (): string => self::studioUrl(), self::openInNewTab())
            ->icon((string) config('ai-hub.filament.navigation_icon', 'heroicon-o-cpu-chip'))
            ->group(is_string($group) && $group !== '' ? $group : null)
            ->sort((int) config('ai-hub.filament.navigation_sort', 50))
            ->visible(fn (): bool => (bool) config('ai-hub.filament.enabled', true)
                && (bool) config('ai-hub.settings.ui_enabled', true));
    }
}
