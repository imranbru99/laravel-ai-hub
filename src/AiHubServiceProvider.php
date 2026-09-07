<?php

namespace ImranDevBd\AiHub;

use ImranDevBd\AiHub\Console\ConfigureAiHubCommand;
use ImranDevBd\AiHub\Support\Analytics;
use ImranDevBd\AiHub\Support\SettingsStore;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AiHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-hub.php', 'ai-hub');
        $this->overlayPackageCatalog();

        $this->app->singleton(SettingsStore::class);
        $this->app->singleton(AIHubManager::class, fn () => new AIHubManager);
        $this->app->singleton(Analytics::class, fn () => new Analytics);
        $this->app->alias(AIHubManager::class, 'ai-hub');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ai-hub.php' => config_path('ai-hub.php'),
        ], 'ai-hub-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-hub');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/ai-hub'),
        ], 'ai-hub-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ai-hub-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConfigureAiHubCommand::class,
            ]);
        }

        // Apply saved keys/models over .env defaults
        try {
            $this->app->make(SettingsStore::class)->applyToConfig();
        } catch (\Throwable) {
            // DB may not be ready during install
        }

        $this->registerRoutes();
        $this->registerFilamentNavigation();
    }

    /**
     * Add an "AI Hub" sidebar item that opens Studio in a new tab.
     */
    protected function registerFilamentNavigation(): void
    {
        if (! class_exists(\Filament\Facades\Filament::class)
            || ! class_exists(\Filament\Navigation\NavigationItem::class)) {
            return;
        }

        \Filament\Facades\Filament::serving(function (): void {
            if (! config('ai-hub.filament.enabled', true) || ! config('ai-hub.settings.ui_enabled', true)) {
                return;
            }

            $panel = \Filament\Facades\Filament::getCurrentPanel();
            if (! $panel) {
                return;
            }

            if (method_exists($panel, 'hasPlugin') && $panel->hasPlugin('ai-hub')) {
                return;
            }

            $panel->navigationItems([\ImranDevBd\AiHub\Filament\AiHubNavigation::item()]);
        });
    }

    protected function registerRoutes(): void
    {
        if (! config('ai-hub.settings.ui_enabled', true)) {
            return;
        }

        $prefix = config('ai-hub.settings.route_prefix', 'ai-hub');
        $rawMiddleware = config('ai-hub.settings.middleware', ['web']);

        $middleware = [];
        foreach ((array) $rawMiddleware as $item) {
            if (is_string($item)) {
                foreach (explode(',', $item) as $part) {
                    $trimmed = trim($part);
                    if ($trimmed !== '') {
                        $middleware[] = $trimmed;
                    }
                }
            } elseif (! empty($item)) {
                $middleware[] = $item;
            }
        }

        if (config('ai-hub.settings.authorize_middleware', true)) {
            $middleware[] = \ImranDevBd\AiHub\Http\Middleware\AuthorizeStudio::class;
        }

        Route::middleware(empty($middleware) ? ['web', \ImranDevBd\AiHub\Http\Middleware\AuthorizeStudio::class] : array_values(array_unique($middleware)))
            ->prefix($prefix)
            ->name('ai-hub.')
            ->group(__DIR__.'/../routes/web.php');
    }

    /**
     * Keep package model lists & pricing current after Composer upgrades,
     * even when a stale published config/ai-hub.php exists in the host app.
     */
    protected function overlayPackageCatalog(): void
    {
        $package = require __DIR__.'/../config/ai-hub.php';

        $popular = [];
        foreach ($package['popular_models'] ?? [] as $provider => $models) {
            $existing = (array) config('ai-hub.popular_models.'.$provider, []);
            $popular[$provider] = array_values(array_unique(array_merge(
                array_values($models),
                array_values($existing)
            )));
        }

        if ($popular !== []) {
            config(['ai-hub.popular_models' => $popular]);
        }

        $pricing = $package['pricing'] ?? [];
        foreach ((array) config('ai-hub.pricing', []) as $provider => $rows) {
            if (! is_array($rows)) {
                continue;
            }

            $pricing[$provider] = array_replace($pricing[$provider] ?? [], $rows);
        }

        if ($pricing !== []) {
            config(['ai-hub.pricing' => $pricing]);
        }
    }
}
