<?php

namespace ImranDevBd\AiHub\Http\Controllers;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\Support\Analytics;
use ImranDevBd\AiHub\Support\BudgetGuard;
use ImranDevBd\AiHub\Support\ModelCapabilities;
use ImranDevBd\AiHub\Support\ProviderCatalog;
use ImranDevBd\AiHub\Support\SettingsStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
                'tagline' => 'Providers · keys · playground · priority · analytics',
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

            $model = (string) ($validated['model'] ?? config('ai-hub.defaults.'.$validated['provider'], ''));
            if (! empty($validated['model'])) {
                $pending->model($validated['model']);
            }
            if (! empty($validated['api_key']) && ! str_starts_with($validated['api_key'], '********')) {
                $pending->apiKey($validated['api_key']);
            }

            $pending->prompt($validated['prompt'] ?? 'Reply with exactly: OK');
            if (! ModelCapabilities::usesMaxCompletionTokens($model)) {
                $pending->maxTokens(32);
            }

            $response = $pending->send();

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
            'budget' => app(BudgetGuard::class)->snapshot(),
        ]);
    }

    public function playground(Request $request): JsonResponse
    {
        $validated = $this->validatePlayground($request);

        try {
            $response = $this->playgroundPending($validated)->send();

            return response()->json([
                'success' => true,
                'content' => $response->content,
                'provider' => $response->provider,
                'model' => $response->model,
                'latency_ms' => $response->latencyMs,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'total_tokens' => $response->totalTokens,
                'cost_usd' => $response->costUsd,
                'tool_calls' => $response->toolCalls,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function playgroundStream(Request $request): StreamedResponse
    {
        $validated = $this->validatePlayground($request);

        return response()->stream(function () use ($validated) {
            try {
                $pending = $this->playgroundPending($validated);
                echo 'data: '.json_encode([
                    'meta' => [
                        'provider' => $validated['provider'],
                        'model' => $validated['model'] ?? null,
                    ],
                ])."\n\n";
                $this->flushStream();

                foreach ($pending->stream() as $chunk) {
                    echo 'data: '.json_encode(['chunk' => $chunk])."\n\n";
                    $this->flushStream();
                }

                echo 'data: '.json_encode(['done' => true])."\n\n";
                $this->flushStream();
            } catch (Throwable $e) {
                echo 'data: '.json_encode(['error' => $e->getMessage()])."\n\n";
                $this->flushStream();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function saveBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'budget' => ['required', 'array'],
            'budget.monthly_usd' => ['nullable', 'numeric', 'min:0'],
            'budget.on_exceed' => ['required', 'in:block,warn'],
            'budget.per_provider' => ['nullable', 'array'],
            'budget.per_provider.*' => ['nullable', 'numeric', 'min:0'],
            'budget.per_job' => ['nullable', 'array'],
            'budget.per_job.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->settings->save(['budget' => $validated['budget']]);
        $this->settings->applyToConfig();

        return response()->json([
            'success' => true,
            'message' => 'Spend budget saved.',
            'data' => $this->bootPayload(),
            'budget' => app(BudgetGuard::class)->snapshot(),
        ]);
    }

    public function savePrompts(Request $request): JsonResponse
    {
        $rule = ProviderCatalog::validationRule();
        $validated = $request->validate([
            'prompts' => ['present', 'array'],
            'prompts.*.name' => ['required', 'string', 'max:80'],
            'prompts.*.provider' => ['nullable', $rule],
            'prompts.*.model' => ['nullable', 'string', 'max:120'],
            'prompts.*.system' => ['nullable', 'string', 'max:8000'],
            'prompts.*.user' => ['required', 'string', 'max:20000'],
        ]);

        $this->settings->save(['prompts' => array_values($validated['prompts'])]);

        return response()->json([
            'success' => true,
            'message' => 'Prompt templates saved.',
            'data' => $this->bootPayload(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePlayground(Request $request): array
    {
        return $request->validate([
            'provider' => ['required', ProviderCatalog::validationRule()],
            'model' => ['nullable', 'string', 'max:120'],
            'system' => ['nullable', 'string', 'max:8000'],
            'prompt' => ['required', 'string', 'max:20000'],
            'image' => ['nullable', 'string', 'max:4000'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:128000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function playgroundPending(array $validated): \ImranDevBd\AiHub\PendingRequest
    {
        $this->settings->applyToConfig();
        $pending = AIHub::provider($validated['provider'])->withoutFailover()->forJob('studio-playground');

        if (! empty($validated['model'])) {
            $pending->model($validated['model']);
        }
        if (! empty($validated['system'])) {
            $pending->system($validated['system']);
        }
        if (! empty($validated['image'])) {
            $pending->image($validated['image']);
        }
        $model = (string) ($validated['model'] ?? '');
        if (isset($validated['temperature']) && ($model === '' || ModelCapabilities::supportsTemperature($model))) {
            $pending->temperature((float) $validated['temperature']);
        }
        if (isset($validated['max_tokens'])) {
            $pending->maxTokens((int) $validated['max_tokens']);
        }

        return $pending->prompt($validated['prompt']);
    }

    protected function flushStream(): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
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
            'prompts' => $masked['prompts'] ?? [],
            'budget' => app(BudgetGuard::class)->snapshot(),
        ];
    }
}
