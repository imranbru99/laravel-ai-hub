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
    'priority' => [
        'openai', 'gemini', 'claude', 'grok',
        'deepseek', 'mistral', 'groq', 'ollama', 'openrouter',
        'azure', 'together', 'fireworks', 'perplexity',
    ],

    'failover_enabled' => env('AI_HUB_FAILOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Default Models (per provider)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'openai' => env('AI_HUB_OPENAI_MODEL', 'gpt-5.6-luna'),
        'gemini' => env('AI_HUB_GEMINI_MODEL', 'gemini-3.7-flash'),
        'claude' => env('AI_HUB_CLAUDE_MODEL', 'claude-3-7-sonnet-latest'),
        'grok' => env('AI_HUB_GROK_MODEL', 'grok-4.1-fast'),
        'deepseek' => env('AI_HUB_DEEPSEEK_MODEL', 'deepseek-chat'),
        'mistral' => env('AI_HUB_MISTRAL_MODEL', 'mistral-small-latest'),
        'groq' => env('AI_HUB_GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'ollama' => env('AI_HUB_OLLAMA_MODEL', 'llama3.3'),
        'openrouter' => env('AI_HUB_OPENROUTER_MODEL', 'google/gemini-3.7-flash'),
        'azure' => env('AI_HUB_AZURE_MODEL', 'gpt-4o'),
        'together' => env('AI_HUB_TOGETHER_MODEL', 'meta-llama/Llama-3.3-70B-Instruct-Turbo'),
        'fireworks' => env('AI_HUB_FIREWORKS_MODEL', 'accounts/fireworks/models/llama-v3p3-70b-instruct'),
        'perplexity' => env('AI_HUB_PERPLEXITY_MODEL', 'sonar-pro'),
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
        'authorize_middleware' => env('AI_HUB_AUTHORIZE', true),
        'roles' => array_filter(explode(',', (string) env('AI_HUB_ROLES', ''))),
        'gate' => env('AI_HUB_GATE', 'viewAiHub'),
        'allowed_emails' => array_filter(explode(',', (string) env('AI_HUB_EMAILS', ''))),
        'database' => env('AI_HUB_SETTINGS_DB', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Popular models (shown in UI dropdowns)
    |--------------------------------------------------------------------------
    */
    'popular_models' => [
        'openai' => [
            'gpt-5.6-luna',
            'gpt-5.6-terra',
            'gpt-5.6-sol',
            'gpt-5.5',
            'gpt-5.4',
            'gpt-5',
            'gpt-5-mini',
            'gpt-5-pro',
            'o3-mini',
            'o3',
            'o4-mini',
            'o1',
            'o1-mini',
            'gpt-4o-mini',
            'gpt-4o',
            'gpt-4.1',
            'gpt-4.1-mini',
            'chatgpt-4o-latest',
            'gpt-4-turbo',
        ],
        'gemini' => [
            'gemini-3.7-flash',
            'gemini-3.6-flash',
            'gemini-3.1-pro',
            'gemini-3.0-flash',
            'gemini-3.0-pro',
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite',
            'gemini-2.0-pro-exp-02-05',
            'gemini-2.0-flash-thinking-exp-01-21',
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-1.5-flash-8b',
        ],
        'claude' => [
            'claude-3-7-sonnet-latest',
            'claude-sonnet-4-20250514',
            'claude-3-5-sonnet-latest',
            'claude-3-5-haiku-latest',
            'claude-3-opus-latest',
            'claude-3-7-sonnet-20250219',
            'claude-3-5-sonnet-20241022',
            'claude-3-5-haiku-20241022',
        ],
        'grok' => [
            'grok-4.6',
            'grok-4.5',
            'grok-4.3',
            'grok-4.1-fast',
            'grok-3',
            'grok-3-mini',
            'grok-2-latest',
            'grok-2-1212',
            'grok-2-vision-1212',
            'grok-2',
            'grok-beta',
        ],
        'deepseek' => [
            'deepseek-chat',
            'deepseek-reasoner',
            'deepseek-coder',
        ],
        'mistral' => [
            'mistral-large-latest',
            'mistral-small-latest',
            'codestral-latest',
            'ministral-8b-latest',
            'ministral-3b-latest',
            'pixtral-large-latest',
            'pixtral-12b-2409',
            'mistral-medium-latest',
            'open-mistral-nemo',
            'open-codestral-mamba',
        ],
        'groq' => [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'deepseek-r1-distill-llama-70b',
            'deepseek-r1-distill-qwen-32b',
            'llama-3.3-70b-specdec',
            'qwen-2.5-32b',
            'qwen-2.5-coder-32b',
            'llama-3.2-11b-vision-preview',
            'llama-3.2-90b-vision-preview',
            'llama-3.2-3b-preview',
            'llama-3.2-1b-preview',
            'llama-3.1-70b-versatile',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
        ],
        'ollama' => [
            'llama3.3',
            'llama3.2',
            'deepseek-r1',
            'qwen2.5-coder',
            'qwen2.5',
            'mistral-small',
            'phi4',
            'llama3.2-vision',
            'llama3.1',
            'gemma2',
            'mistral',
            'phi3',
            'codellama',
        ],
        'openrouter' => [
            'google/gemini-3.7-flash',
            'google/gemini-3.1-pro',
            'google/gemini-3-flash',
            'google/gemini-2.5-pro',
            'google/gemini-2.5-flash',
            'google/gemini-2.0-flash-001',
            'anthropic/claude-3.7-sonnet',
            'anthropic/claude-3.7-sonnet:thinking',
            'anthropic/claude-3.5-sonnet',
            'anthropic/claude-3.5-haiku',
            'openai/gpt-5',
            'openai/gpt-5-mini',
            'openai/gpt-5-pro',
            'openai/gpt-4o',
            'openai/gpt-4o-mini',
            'openai/o3-mini',
            'openai/o1',
            'deepseek/deepseek-r1',
            'deepseek/deepseek-chat',
            'meta-llama/llama-3.3-70b-instruct',
            'x-ai/grok-2-1212',
            'mistralai/mistral-large-2411',
            'qwen/qwen-2.5-coder-32b-instruct',
        ],
        'azure' => [
            'gpt-5',
            'gpt-5-mini',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4.1',
            'o3-mini',
            'o1',
        ],
        'together' => [
            'meta-llama/Llama-3.3-70B-Instruct-Turbo',
            'meta-llama/Meta-Llama-3.1-405B-Instruct-Turbo',
            'Qwen/Qwen2.5-72B-Instruct-Turbo',
            'deepseek-ai/DeepSeek-R1',
            'deepseek-ai/DeepSeek-V3',
        ],
        'fireworks' => [
            'accounts/fireworks/models/llama-v3p3-70b-instruct',
            'accounts/fireworks/models/llama-v3p1-405b-instruct',
            'accounts/fireworks/models/qwen2p5-72b-instruct',
            'accounts/fireworks/models/deepseek-r1',
        ],
        'perplexity' => [
            'sonar-pro',
            'sonar',
            'sonar-reasoning-pro',
            'sonar-reasoning',
        ],
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
            'driver' => 'openai-compatible',
            'api_key' => env('GROK_API_KEY', env('XAI_API_KEY')),
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'timeout' => (int) env('AI_HUB_GROK_TIMEOUT', 60),
        ],

        'deepseek' => [
            'driver' => 'openai-compatible',
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'timeout' => (int) env('AI_HUB_DEEPSEEK_TIMEOUT', 60),
        ],

        'mistral' => [
            'driver' => 'openai-compatible',
            'api_key' => env('MISTRAL_API_KEY'),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            'timeout' => (int) env('AI_HUB_MISTRAL_TIMEOUT', 60),
        ],

        'groq' => [
            'driver' => 'openai-compatible',
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'timeout' => (int) env('AI_HUB_GROQ_TIMEOUT', 60),
        ],

        'ollama' => [
            'driver' => 'openai-compatible',
            'api_key' => env('OLLAMA_API_KEY', 'ollama'),
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434/v1'),
            'timeout' => (int) env('AI_HUB_OLLAMA_TIMEOUT', 120),
        ],

        'openrouter' => [
            'driver' => 'openai-compatible',
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'timeout' => (int) env('AI_HUB_OPENROUTER_TIMEOUT', 60),
        ],

        'azure' => [
            'driver' => 'azure',
            'api_key' => env('AZURE_OPENAI_API_KEY'),
            'base_url' => env('AZURE_OPENAI_ENDPOINT'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),
            'timeout' => (int) env('AI_HUB_AZURE_TIMEOUT', 60),
        ],

        'together' => [
            'driver' => 'openai-compatible',
            'api_key' => env('TOGETHER_API_KEY'),
            'base_url' => env('TOGETHER_BASE_URL', 'https://api.together.xyz/v1'),
            'timeout' => (int) env('AI_HUB_TOGETHER_TIMEOUT', 60),
        ],

        'fireworks' => [
            'driver' => 'openai-compatible',
            'api_key' => env('FIREWORKS_API_KEY'),
            'base_url' => env('FIREWORKS_BASE_URL', 'https://api.fireworks.ai/inference/v1'),
            'timeout' => (int) env('AI_HUB_FIREWORKS_TIMEOUT', 60),
        ],

        'perplexity' => [
            'driver' => 'openai-compatible',
            'api_key' => env('PERPLEXITY_API_KEY'),
            'base_url' => env('PERPLEXITY_BASE_URL', 'https://api.perplexity.ai'),
            'timeout' => (int) env('AI_HUB_PERPLEXITY_TIMEOUT', 60),
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
        // 'after_response' (default: 0 worker needed, 0 latency), 'queue' (background worker), or 'sync' (direct)
        'async' => env('AI_HUB_LOGGING_ASYNC', 'after_response'),
        'after_response' => env('AI_HUB_LOGGING_AFTER_RESPONSE', true),
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
            'gpt-5.6-sol' => ['input' => 5.00, 'output' => 30.00],
            'gpt-5.6-terra' => ['input' => 2.00, 'output' => 12.00],
            'gpt-5.6-luna' => ['input' => 0.20, 'output' => 1.20],
            'gpt-5.5' => ['input' => 5.00, 'output' => 30.00],
            'gpt-5.4' => ['input' => 2.50, 'output' => 15.00],
            'gpt-5' => ['input' => 5.00, 'output' => 30.00],
            'gpt-5-pro' => ['input' => 10.00, 'output' => 50.00],
            'gpt-5-mini' => ['input' => 0.25, 'output' => 1.50],
            'o3-mini' => ['input' => 1.10, 'output' => 4.40],
            'o3' => ['input' => 5.00, 'output' => 20.00],
            'o1' => ['input' => 15.00, 'output' => 60.00],
            'o1-mini' => ['input' => 1.10, 'output' => 4.40],
            'o1-preview' => ['input' => 15.00, 'output' => 60.00],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
            'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
            'o4-mini' => ['input' => 0.50, 'output' => 2.00],
            'chatgpt-4o-latest' => ['input' => 5.00, 'output' => 15.00],
            'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
            'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.00],
            'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.00],
        ],
        'gemini' => [
            'gemini-3.7-flash' => ['input' => 0.75, 'output' => 3.75],
            'gemini-3.6-flash' => ['input' => 0.50, 'output' => 2.50],
            'gemini-3.1-pro' => ['input' => 2.00, 'output' => 8.00],
            'gemini-3.0-flash' => ['input' => 0.25, 'output' => 1.00],
            'gemini-3.0-pro' => ['input' => 1.50, 'output' => 6.00],
            'gemini-2.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'gemini-2.5-flash' => ['input' => 0.15, 'output' => 0.60],
            'gemini-2.0-flash' => ['input' => 0.10, 'output' => 0.40],
            'gemini-2.0-flash-lite' => ['input' => 0.075, 'output' => 0.30],
            'gemini-2.0-pro-exp-02-05' => ['input' => 1.25, 'output' => 5.00],
            'gemini-2.0-flash-thinking-exp-01-21' => ['input' => 0.10, 'output' => 0.40],
            'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
            'gemini-1.5-flash-8b' => ['input' => 0.0375, 'output' => 0.15],
            'text-embedding-004' => ['input' => 0.00, 'output' => 0.00],
        ],
        'claude' => [
            'claude-3-7-sonnet-latest' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-7-sonnet-20250219' => ['input' => 3.00, 'output' => 15.00],
            'claude-sonnet-4-20250514' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-sonnet-latest' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-haiku-latest' => ['input' => 0.80, 'output' => 4.00],
            'claude-3-5-haiku-20241022' => ['input' => 0.80, 'output' => 4.00],
            'claude-3-opus-latest' => ['input' => 15.00, 'output' => 75.00],
            'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
            'claude-3-haiku-20240307' => ['input' => 0.25, 'output' => 1.25],
        ],
        'grok' => [
            'grok-4.6' => ['input' => 2.00, 'output' => 6.00],
            'grok-4.5' => ['input' => 2.00, 'output' => 6.00],
            'grok-4.3' => ['input' => 1.25, 'output' => 2.50],
            'grok-4.1-fast' => ['input' => 0.20, 'output' => 0.50],
            'grok-3' => ['input' => 3.00, 'output' => 15.00],
            'grok-3-mini' => ['input' => 0.30, 'output' => 0.50],
            'grok-2-latest' => ['input' => 2.00, 'output' => 10.00],
            'grok-2-1212' => ['input' => 2.00, 'output' => 10.00],
            'grok-2' => ['input' => 2.00, 'output' => 10.00],
            'grok-2-vision-1212' => ['input' => 2.00, 'output' => 10.00],
            'grok-beta' => ['input' => 5.00, 'output' => 15.00],
        ],
        'deepseek' => [
            'deepseek-chat' => ['input' => 0.27, 'output' => 1.10],
            'deepseek-reasoner' => ['input' => 0.55, 'output' => 2.19],
            'deepseek-coder' => ['input' => 0.27, 'output' => 1.10],
        ],
        'mistral' => [
            'mistral-large-latest' => ['input' => 2.00, 'output' => 6.00],
            'mistral-medium-latest' => ['input' => 0.40, 'output' => 2.00],
            'mistral-small-latest' => ['input' => 0.10, 'output' => 0.30],
            'codestral-latest' => ['input' => 0.30, 'output' => 0.90],
            'ministral-8b-latest' => ['input' => 0.10, 'output' => 0.10],
            'ministral-3b-latest' => ['input' => 0.04, 'output' => 0.04],
            'pixtral-large-latest' => ['input' => 2.00, 'output' => 6.00],
            'pixtral-12b-2409' => ['input' => 0.15, 'output' => 0.15],
            'open-mistral-nemo' => ['input' => 0.15, 'output' => 0.15],
            'open-codestral-mamba' => ['input' => 0.15, 'output' => 0.15],
            'mistral-embed' => ['input' => 0.10, 'output' => 0.00],
        ],
        'groq' => [
            'llama-3.3-70b-versatile' => ['input' => 0.59, 'output' => 0.79],
            'llama-3.3-70b-specdec' => ['input' => 0.59, 'output' => 0.99],
            'llama-3.1-70b-versatile' => ['input' => 0.59, 'output' => 0.79],
            'llama-3.1-8b-instant' => ['input' => 0.05, 'output' => 0.08],
            'deepseek-r1-distill-llama-70b' => ['input' => 0.75, 'output' => 0.99],
            'deepseek-r1-distill-qwen-32b' => ['input' => 0.69, 'output' => 0.69],
            'qwen-2.5-32b' => ['input' => 0.69, 'output' => 0.69],
            'qwen-2.5-coder-32b' => ['input' => 0.69, 'output' => 0.69],
            'llama-3.2-90b-vision-preview' => ['input' => 0.90, 'output' => 0.90],
            'llama-3.2-11b-vision-preview' => ['input' => 0.18, 'output' => 0.18],
            'llama-3.2-3b-preview' => ['input' => 0.06, 'output' => 0.06],
            'llama-3.2-1b-preview' => ['input' => 0.04, 'output' => 0.04],
            'mixtral-8x7b-32768' => ['input' => 0.24, 'output' => 0.24],
            'gemma2-9b-it' => ['input' => 0.20, 'output' => 0.20],
        ],
        'ollama' => [
            // local — treat as free for cost calc
            'llama3.3' => ['input' => 0.0, 'output' => 0.0],
            'llama3.2' => ['input' => 0.0, 'output' => 0.0],
            'llama3.1' => ['input' => 0.0, 'output' => 0.0],
            'deepseek-r1' => ['input' => 0.0, 'output' => 0.0],
            'qwen2.5-coder' => ['input' => 0.0, 'output' => 0.0],
            'qwen2.5' => ['input' => 0.0, 'output' => 0.0],
            'mistral-small' => ['input' => 0.0, 'output' => 0.0],
            'phi4' => ['input' => 0.0, 'output' => 0.0],
            'llama3.2-vision' => ['input' => 0.0, 'output' => 0.0],
            'gemma2' => ['input' => 0.0, 'output' => 0.0],
            'mistral' => ['input' => 0.0, 'output' => 0.0],
            'phi3' => ['input' => 0.0, 'output' => 0.0],
            'codellama' => ['input' => 0.0, 'output' => 0.0],
        ],
        'openrouter' => [
            // varies by routed model — accurate standard defaults
            'google/gemini-3.7-flash' => ['input' => 0.75, 'output' => 3.75],
            'google/gemini-3.1-pro' => ['input' => 2.00, 'output' => 8.00],
            'google/gemini-3-flash' => ['input' => 0.25, 'output' => 1.00],
            'google/gemini-2.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'google/gemini-2.5-flash' => ['input' => 0.15, 'output' => 0.60],
            'google/gemini-2.0-flash-001' => ['input' => 0.10, 'output' => 0.40],
            'anthropic/claude-3.7-sonnet' => ['input' => 3.00, 'output' => 15.00],
            'anthropic/claude-3.7-sonnet:thinking' => ['input' => 3.00, 'output' => 15.00],
            'anthropic/claude-3.5-sonnet' => ['input' => 3.00, 'output' => 15.00],
            'anthropic/claude-3.5-haiku' => ['input' => 0.80, 'output' => 4.00],
            'openai/gpt-5' => ['input' => 5.00, 'output' => 30.00],
            'openai/gpt-5-pro' => ['input' => 10.00, 'output' => 50.00],
            'openai/gpt-5-mini' => ['input' => 0.25, 'output' => 1.50],
            'openai/gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'openai/gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'openai/o3-mini' => ['input' => 1.10, 'output' => 4.40],
            'openai/o1' => ['input' => 15.00, 'output' => 60.00],
            'deepseek/deepseek-r1' => ['input' => 0.55, 'output' => 2.19],
            'deepseek/deepseek-chat' => ['input' => 0.27, 'output' => 1.10],
            'meta-llama/llama-3.3-70b-instruct' => ['input' => 0.59, 'output' => 0.79],
            'x-ai/grok-2-1212' => ['input' => 2.00, 'output' => 10.00],
            'mistralai/mistral-large-2411' => ['input' => 2.00, 'output' => 6.00],
            'qwen/qwen-2.5-coder-32b-instruct' => ['input' => 0.15, 'output' => 0.60],
        ],
        'azure' => [
            'gpt-5' => ['input' => 5.00, 'output' => 30.00],
            'gpt-5-mini' => ['input' => 0.25, 'output' => 1.50],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
            'o3-mini' => ['input' => 1.10, 'output' => 4.40],
            'o1' => ['input' => 15.00, 'output' => 60.00],
        ],
        'together' => [
            'meta-llama/Llama-3.3-70B-Instruct-Turbo' => ['input' => 0.88, 'output' => 0.88],
            'meta-llama/Meta-Llama-3.1-405B-Instruct-Turbo' => ['input' => 3.50, 'output' => 3.50],
            'Qwen/Qwen2.5-72B-Instruct-Turbo' => ['input' => 1.20, 'output' => 1.20],
            'deepseek-ai/DeepSeek-R1' => ['input' => 3.00, 'output' => 7.00],
            'deepseek-ai/DeepSeek-V3' => ['input' => 1.25, 'output' => 1.25],
        ],
        'fireworks' => [
            'accounts/fireworks/models/llama-v3p3-70b-instruct' => ['input' => 0.90, 'output' => 0.90],
            'accounts/fireworks/models/llama-v3p1-405b-instruct' => ['input' => 3.00, 'output' => 3.00],
            'accounts/fireworks/models/qwen2p5-72b-instruct' => ['input' => 0.90, 'output' => 0.90],
            'accounts/fireworks/models/deepseek-r1' => ['input' => 3.00, 'output' => 8.00],
        ],
        'perplexity' => [
            'sonar-pro' => ['input' => 3.00, 'output' => 15.00],
            'sonar' => ['input' => 1.00, 'output' => 1.00],
            'sonar-reasoning-pro' => ['input' => 2.00, 'output' => 8.00],
            'sonar-reasoning' => ['input' => 1.00, 'output' => 5.00],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Spend budgets
    |--------------------------------------------------------------------------
    */
    'budget' => [
        'monthly_usd' => env('AI_HUB_BUDGET_MONTHLY'),
        'on_exceed' => env('AI_HUB_BUDGET_ON_EXCEED', 'block'),
        'per_provider' => [],
        'per_job' => [],
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
