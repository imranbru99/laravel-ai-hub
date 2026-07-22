<?php

namespace ImranDevBd\AiHub\Filament;

use ImranDevBd\AiHub\Support\Analytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Optional Filament widget. Requires filament/filament installed in the host app.
 * Register manually: AiHubDashboardWidget::class in your panel provider.
 */
class AiHubDashboardWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        if (! class_exists(StatsOverviewWidget::class)) {
            return [];
        }

        /** @var Analytics $analytics */
        $analytics = app(Analytics::class);
        $summary = $analytics->summary(now()->subDays(30));
        $latency = $analytics->latencyPercentiles();

        return [
            Stat::make('AI Cost (30d)', '$'.number_format($summary['total_cost_usd'], 4))
                ->description($summary['requests'].' requests · '.$summary['total_tokens'].' tokens')
                ->descriptionIcon('heroicon-m-currency-dollar'),
            Stat::make('Failure Rate', $summary['failure_rate'].'%')
                ->description($summary['failures'].' failed')
                ->color($summary['failure_rate'] > 5 ? 'danger' : 'success'),
            Stat::make('JSON Recovered', $summary['json_recovery_rate'].'%')
                ->description($summary['json_recovered'].' repaired responses')
                ->color('warning'),
            Stat::make('Latency p95', $latency['p95'].' ms')
                ->description('p50 '.$latency['p50'].' · p99 '.$latency['p99']),
        ];
    }

    public static function canView(): bool
    {
        return (bool) config('ai-hub.filament.enabled', true)
            && class_exists(StatsOverviewWidget::class);
    }
}
