<?php

namespace ImranDevBd\AiHub\Support;

use ImranDevBd\AiHub\Models\AiRequestLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Analytics
{
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = $this->baseQuery($from, $to);

        $total = (clone $query)->count();
        $failed = (clone $query)->where('success', false)->count();
        $recovered = (clone $query)->where('json_recovered', true)->count();

        return [
            'requests' => $total,
            'failures' => $failed,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            'json_recovered' => $recovered,
            'json_recovery_rate' => $total > 0 ? round(($recovered / $total) * 100, 2) : 0,
            'total_cost_usd' => round((float) (clone $query)->sum('cost_usd'), 6),
            'total_tokens' => (int) (clone $query)->sum('total_tokens'),
            'avg_latency_ms' => round((float) (clone $query)->avg('latency_ms'), 2),
        ];
    }

    public function costByProvider(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return $this->baseQuery($from, $to)
            ->select('provider', DB::raw('SUM(cost_usd) as cost'), DB::raw('COUNT(*) as requests'))
            ->groupBy('provider')
            ->orderByDesc('cost')
            ->get();
    }

    public function latencyPercentiles(?string $provider = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $latencies = $this->baseQuery($from, $to)
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->orderBy('latency_ms')
            ->pluck('latency_ms')
            ->map(fn ($v) => (float) $v)
            ->values();

        if ($latencies->isEmpty()) {
            return ['p50' => 0, 'p95' => 0, 'p99' => 0];
        }

        return [
            'p50' => $this->percentile($latencies, 50),
            'p95' => $this->percentile($latencies, 95),
            'p99' => $this->percentile($latencies, 99),
        ];
    }

    public function topJobs(int $limit = 10, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return $this->baseQuery($from, $to)
            ->whereNotNull('job')
            ->select('job', DB::raw('SUM(total_tokens) as tokens'), DB::raw('SUM(cost_usd) as cost'), DB::raw('COUNT(*) as requests'))
            ->groupBy('job')
            ->orderByDesc('tokens')
            ->limit($limit)
            ->get();
    }

    public function dailyCost(int $days = 30): Collection
    {
        $from = now()->subDays($days)->startOfDay();

        return AiRequestLog::query()
            ->where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(cost_usd) as cost'), DB::raw('COUNT(*) as requests'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    protected function baseQuery(?Carbon $from = null, ?Carbon $to = null)
    {
        return AiRequestLog::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }

    protected function percentile(Collection $sorted, float $pct): float
    {
        $count = $sorted->count();
        if ($count === 0) {
            return 0.0;
        }

        $index = (int) ceil(($pct / 100) * $count) - 1;
        $index = max(0, min($count - 1, $index));

        return round((float) $sorted[$index], 2);
    }
}
