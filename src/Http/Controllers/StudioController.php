<?php

namespace ImranDevBd\AiHub\Http\Controllers;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\Support\Analytics;
use ImranDevBd\AiHub\Support\ProviderCatalog;
use ImranDevBd\AiHub\Support\SettingsStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;

class StudioController extends Controller
{
    public function __construct(
        protected SettingsStore $settings,
        protected Analytics $analytics,
    ) {}

    public function index(): View
    {
        return view('ai-hub::studio', [
            'boot' => $this->bootPayload(),
            'brand' => [
                'name' => 'Laravel AI Hub',
                'tagline' => 'Providers · keys · models · priority · analytics',
            ],
        ]);
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bootPayload(),
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $rule = ProviderCatalog::validationRule();

        $validated = $request->validate([
            'default' => ['required', $rule],
            'failover_enabled' => ['sometimes', 'boolean'],
            'providers' => ['required', 'array'],
            'providers.*.api_key' => ['nullable', 'string'],
            'providers.*.model' => ['nullable', 'string', 'max:120'],
            'providers.*.enabled' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'default' => $validated['default'],
            'failover_enabled' => (bool) ($validated['failover_enabled'] ?? true),
            'defaults' => [],
            'providers' => [],
        ];

        foreach (ProviderCatalog::keys() as $provider) {
            $row = $validated['providers'][$provider] ?? [];
            $model = $row['model'] ?? null;
            $payload['providers'][$provider] = [
                'api_key' => $row['api_key'] ?? null,
                'model' => $model,
                'enabled' => (bool) ($row['enabled'] ?? true),
            ];
            if ($model) {
                $payload['defaults'][$provider] = $model;
            }
        }

        $this->settings->save($payload);
        $this->settings->applyToConfig();
        AIHub::getFacadeRoot()->forget();

        return response()->json([
            'success' => true,
            'message' => 'API keys & models saved.',
            'data' => $this->bootPayload(),
        ]);
    }

    public function saveProvider(Request $request): JsonResponse
    {
        $rule = ProviderCatalog::validationRule();

        $validated = $request->validate([
            'provider' => ['required', $rule],
            'api_key' => ['nullable', 'string'],
            'model' => ['nullable', 'string', 'max:120'],
            'enabled' => ['sometimes', 'boolean'],
            'make_default' => ['sometimes', 'boolean'],
        ]);

        $provider = $validated['provider'];
        $model = $validated['model'] ?? null;
        $enabled = array_key_exists('enabled', $validated) ? (bool) $validated['enabled'] : true;

        $payload = [
            'providers' => [
                $provider => [
                    'api_key' => $validated['api_key'] ?? null,
                    'model' => $model,
                    'enabled' => $enabled,
                ],
            ],
        ];

        if (! empty($model)) {
            $payload['defaults'][$provider] = $model;
        }

        if (! empty($validated['make_default'])) {
            $payload['default'] = $provider;
        }

        $this->settings->save($payload);
        $this->settings->applyToConfig();
        AIHub::getFacadeRoot()->forget();

        return response()->json([
            'success' => true,
            'message' => ProviderCatalog::label($provider).' settings saved.',
            'data' => $this->bootPayload(),
        ]);
    }

    public function savePriority(Request $request): JsonResponse
    {
        $rule = ProviderCatalog::validationRule();

        $validated = $request->validate([
            'priority' => ['required', 'array', 'min:1'],
            'priority.*' => ['required', $rule],
            'failover_enabled' => ['sometimes', 'boolean'],
            'default' => ['sometimes', $rule],
        ]);

        $priority = array_values(array_unique($validated['priority']));
        foreach (ProviderCatalog::keys() as $p) {
            if (! in_array($p, $priority, true)) {
                $priority[] = $p;
            }
        }

        $payload = [
            'priority' => $priority,
            'failover_enabled' => (bool) ($validated['failover_enabled'] ?? true),
        ];

        if (! empty($validated['default'])) {
            $payload['default'] = $validated['default'];
        } elseif (! empty($priority[0])) {
            $payload['default'] = $priority[0];
        }

        $this->settings->save($payload);
        $this->settings->applyToConfig();
        AIHub::getFacadeRoot()->forget();

        return response()->json([
            'success' => true,
            'message' => 'Provider priority saved. #1 runs first.',
            'data' => $this->bootPayload(),
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', ProviderCatalog::validationRule()],
            'model' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string'],
            'prompt' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->settings->applyToConfig();
            $pending = AIHub::provider($validated['provider'])->withoutFailover();

            if (! empty($validated['model'])) {
                $pending->model($validated['model']);
            }
            if (! empty($validated['api_key']) && ! str_starts_with($validated['api_key'], '********')) {
                $pending->apiKey($validated['api_key']);
            }

            $response = $pending
                ->prompt($validated['prompt'] ?? 'Reply with exactly: OK')
                ->maxTokens(32)
                ->send();

            return response()->json([
                'success' => true,
                'message' => 'Connection OK',
                'provider' => $response->provider,
                'model' => $response->model,
                'content' => mb_substr($response->content, 0, 200),
                'latency_ms' => $response->latencyMs,
                'cost_usd' => $response->costUsd,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function analytics(): JsonResponse
    {
        $from = now()->subDays(30);

        return response()->json([
            'success' => true,
            'summary' => $this->analytics->summary($from),
            'cost_by_provider' => $this->analytics->costByProvider($from),
            'latency' => $this->analytics->latencyPercentiles(null, $from),
            'top_jobs' => $this->analytics->topJobs(8, $from),
            'daily' => $this->analytics->dailyCost(30),
        ]);
    }

    protected function bootPayload(): array
    {
        $masked = $this->settings->masked();
        $priority = $masked['priority'] ?? ProviderCatalog::keys();

        return [
            'settings' => $masked,
            'popular' => $this->settings->popularModels(),
            'priority' => $priority,
            'failover_enabled' => (bool) ($masked['failover_enabled'] ?? true),
            'providers' => ProviderCatalog::keys(),
            'labels' => ProviderCatalog::labels(),
        ];
    }
}
