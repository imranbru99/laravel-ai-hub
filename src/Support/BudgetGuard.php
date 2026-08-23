<?php

namespace ImranDevBd\AiHub\Support;

use ImranDevBd\AiHub\Exceptions\AiHubException;
use ImranDevBd\AiHub\Models\AiRequestLog;
use Illuminate\Support\Facades\Schema;

class BudgetGuard
{
    /**
     * @return array<int, array{level: string, message: string, key: string}>
     */
    public function assert(?string $provider = null, ?string $job = null): array
    {
        $warnings = $this->evaluate($provider, $job);
        $onExceed = $this->onExceed();

        foreach ($warnings as $warning) {
            if ($warning['level'] === 'breach' && $onExceed === 'block') {
                throw new AiHubException($warning['message']);
            }
        }

        return $warnings;
    }

    /**
     * @return array<int, array{level: string, message: string, key: string}>
     */
    public function evaluate(?string $provider = null, ?string $job = null): array
    {
        $budget = $this->budget();
        $warnings = [];

        $monthly = (float) ($budget['monthly_usd'] ?? 0);
        $monthSpend = $this->spendThisMonth();

        if ($monthly > 0 && $monthSpend >= $monthly) {
            $warnings[] = [
                'key' => 'monthly',
                'level' => 'breach',
                'message' => sprintf('Monthly AI spend cap of $%s reached (spent $%s).', $this->money($monthly), $this->money($monthSpend)),
            ];
        }

        if ($provider) {
            $cap = (float) data_get($budget, "per_provider.{$provider}", 0);
            if ($cap > 0) {
                $spent = $this->spendThisMonth($provider);
                if ($spent >= $cap) {
                    $warnings[] = [
                        'key' => 'provider',
                        'level' => 'breach',
                        'message' => sprintf('Provider [%s] monthly cap of $%s reached (spent $%s).', $provider, $this->money($cap), $this->money($spent)),
                    ];
                }
            }
        }

        if ($job) {
            $cap = (float) data_get($budget, "per_job.{$job}", 0);
            if ($cap > 0) {
                $spent = $this->spendThisMonth(null, $job);
                if ($spent >= $cap) {
                    $warnings[] = [
                        'key' => 'job',
                        'level' => 'breach',
                        'message' => sprintf('Job [%s] monthly cap of $%s reached (spent $%s).', $job, $this->money($cap), $this->money($spent)),
                    ];
                }
            }
        }

        return $warnings;
    }

    public function snapshot(): array
    {
        $budget = $this->budget();
        $monthly = (float) ($budget['monthly_usd'] ?? 0);
        $spent = $this->spendThisMonth();
        $remaining = $monthly > 0 ? max(0, round($monthly - $spent, 6)) : null;
        $warnings = $this->evaluate();

        return [
            'monthly_usd' => $monthly > 0 ? $monthly : null,
            'month_spend' => round($spent, 6),
            'remaining' => $remaining,
            'on_exceed' => $this->onExceed(),
            'per_provider' => (array) ($budget['per_provider'] ?? []),
            'per_job' => (array) ($budget['per_job'] ?? []),
            'breached' => $warnings !== [],
            'warnings' => $warnings,
        ];
    }

    public function spendThisMonth(?string $provider = null, ?string $job = null): float
    {
        if (! $this->logsReady()) {
            return 0.0;
        }

        $query = AiRequestLog::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('success', true);

        if ($provider) {
            $query->where('provider', $provider);
        }

        if ($job) {
            $query->where('job', $job);
        }

        return round((float) $query->sum('cost_usd'), 6);
    }

    protected function budget(): array
    {
        return (array) config('ai-hub.budget', app(SettingsStore::class)->get('budget', []));
    }

    protected function onExceed(): string
    {
        $value = (string) ($this->budget()['on_exceed'] ?? 'block');

        return in_array($value, ['block', 'warn'], true) ? $value : 'block';
    }

    protected function logsReady(): bool
    {
        try {
            return Schema::hasTable(config('ai-hub.logging.table', 'ai_hub_request_logs'));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function money(float $value): string
    {
        return number_format($value, 4, '.', '');
    }
}
