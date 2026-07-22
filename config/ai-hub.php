<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    */
    'default' => env('AI_HUB_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Provider priority (failover order — #1 tried first)
    |--------------------------------------------------------------------------
    */
    'priority' => ['openai', 'gemini', 'claude', 'grok'],

    'failover_enabled' => env('AI_HUB_FAILOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Default Models (per provider)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'openai' => env('AI_HUB_OPENAI_MODEL', 'gpt-4o-mini'),
        'gemini' => env('AI_HUB_GEMINI_MODEL', 'gemini-2.0-flash'),
        'claude' => env('AI_HUB_CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
        'grok' => env('AI_HUB_GROK_MODEL', 'grok-2-latest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Easy settings UI + storage
    |--------------------------------------------------------------------------
    |
    | Visit /ai-hub to change provider, API key, and model visually.
    | Or run: php artisan ai-hub:configure
    |
    */
    'settings' => [
        'ui_enabled' => env('AI_HUB_UI', true),
        'route_prefix' => env('AI_HUB_PATH', 'ai-hub'),
        'middleware' => array_filter(['web', env('AI_HUB_MIDDLEWARE')]),
        'database' => env('AI_HUB_SETTINGS_DB', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Popular models (shown in UI dropdowns)
    |--------------------------------------------------------------------------
    */
    'popular_models' => [
        'openai' => ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini', 'gpt-4.1', 'o4-mini'],
        'gemini' => ['gemini-2.0-flash', 'gemini-2.0-flash-lite', 'gemini-1.5-flash', 'gemini-1.5-pro'],
        'claude' => ['claude-sonnet-4-20250514', 'claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest', 'claude-3-opus-latest'],
        'grok' => ['grok-2-latest', 'grok-2', 'grok-beta'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'timeout' => (int) env('AI_HUB_OPENAI_TIMEOUT', 60),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (int) env('AI_HUB_GEMINI_TIMEOUT', 60),
        ],

        'claude' => [
            'driver' => 'claude',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
            'timeout' => (int) env('AI_HUB_CLAUDE_TIMEOUT', 60),
        ],

        'grok' => [
            'driver' => 'grok',
            'api_key' => env('GROK_API_KEY', env('XAI_API_KEY')),
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'timeout' => (int) env('AI_HUB_GROK_TIMEOUT', 60),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Retry / Rate limits
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'enabled' => env('AI_HUB_RETRY', true),
        'max_attempts' => (int) env('AI_HUB_RETRY_ATTEMPTS', 3),
        'base_delay_ms' => (int) env('AI_HUB_RETRY_DELAY_MS', 500),
        'multiplier' => (float) env('AI_HUB_RETRY_MULTIPLIER', 2.0),
        'retry_on' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Analytics
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('AI_HUB_LOGGING', true),
        'async' => env('AI_HUB_LOGGING_ASYNC', true),
        'queue' => env('AI_HUB_QUEUE', 'default'),
        'table' => 'ai_hub_request_logs',
        'prune_days' => (int) env('AI_HUB_LOG_PRUNE_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Recovery
    |--------------------------------------------------------------------------
    |
    | Brace-depth parser for malformed LLM JSON (common with Gemini).
    |
    */
    'json_recovery' => [
        'enabled' => true,
        'strip_markdown_fences' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token pricing (USD per 1M tokens) — update as providers change rates
    |--------------------------------------------------------------------------
    */
    'pricing' => [
        'openai' => [
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
            'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
            'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.00],
            'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.00],
        ],
        'gemini' => [
            'gemini-2.0-flash' => ['input' => 0.10, 'output' => 0.40],
            'gemini-2.0-flash-lite' => ['input' => 0.075, 'output' => 0.30],
            'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
        ],
        'claude' => [
            'claude-sonnet-4-20250514' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-sonnet-latest' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-haiku-latest' => ['input' => 0.80, 'output' => 4.00],
            'claude-3-opus-latest' => ['input' => 15.00, 'output' => 75.00],
        ],
        'grok' => [
            'grok-2-latest' => ['input' => 2.00, 'output' => 10.00],
            'grok-2' => ['input' => 2.00, 'output' => 10.00],
            'grok-beta' => ['input' => 5.00, 'output' => 15.00],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament dashboard (optional)
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'enabled' => env('AI_HUB_FILAMENT', true),
        'navigation_group' => 'AI Hub',
        'navigation_icon' => 'heroicon-o-cpu-chip',
    ],

];
