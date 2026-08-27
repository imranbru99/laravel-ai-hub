# Laravel AI Hub

<p align="center">
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/dt/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/php-v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="PHP"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-teal.svg?style=flat-square" alt="License"></a>
  <a href="https://imrandev.bd"><img src="https://img.shields.io/badge/by-Imran%20Dev%20BD-14b8a6.svg?style=flat-square" alt="Imran Dev BD"></a>
</p>

**One unified interface. Thirteen AI providers. Playground, budgets, tools, vision, and cost telemetry.**

Swap **OpenAI**, **Gemini**, **Claude**, **Grok**, **DeepSeek**, **Mistral**, **Groq**, **Ollama**, **OpenRouter**, **Azure OpenAI**, **Together**, **Fireworks**, or **Perplexity** via config or a visual Studio UI — every request is cost-tracked in USD, retried with exponential backoff, JSON-repaired automatically, and logged asynchronously with zero user latency (no queue workers required).

Built by **[Imran Dev BD](https://imrandev.bd/)** · Laravel **10–13** · PHP **8.2+**

```bash
composer require imrandevbd/laravel-ai-hub
```

**Packagist:** [`imrandevbd/laravel-ai-hub`](https://packagist.org/packages/imrandevbd/laravel-ai-hub)  
**GitHub:** [github.com/imranbru99/laravel-ai-hub](https://github.com/imranbru99/laravel-ai-hub)

---

## Why Laravel AI Hub?

| Advantage | Benefit |
|---|---|
| **One Unified API** | Identical fluent chain across 13 providers including Azure OpenAI, Together, Fireworks & Perplexity |
| **August 2026 Models** | Out-of-the-box support for Gemini 3.7 Flash, GPT-5.6 series, Claude 3.7 Sonnet, Grok 4 series & DeepSeek R1 |
| **Interactive Studio UI** | `/ai-hub` — Playground, light/dark theme, per-card save, budgets, prompt templates |
| **Automatic Failover** | `#1 → #2 → #3…` chain fallback when rate-limited or unavailable |
| **Tools, vision & cache** | Function tools, image inputs, named prompt templates, optional response cache |
| **Spend budgets** | Monthly / provider / job USD caps with block or warn |
| **Zero-Worker Logging** | `after_response` logging stores analytics immediately without requiring `php artisan queue:work` |
| **JSON Auto-Repair** | Depth-aware parser recovers malformed or truncated JSON from LLM responses automatically |
| **Cost & Health Analytics** | Real-time USD spend calculation, failure %, latency percentiles (p50/p95/p99) & top jobs |
| **Role & Gate Protection** | Built-in `AIHub::auth()` callback, `AI_HUB_ROLES` check, and Laravel Gate integration |

---

## Features at a Glance

| Feature | Description |
|---|---|
| **Multi-Provider Hub** | OpenAI · Gemini · Claude · Grok · DeepSeek · Mistral · Groq · Ollama · OpenRouter · Azure · Together · Fireworks · Perplexity |
| **Visual Studio** | `/ai-hub` with Playground, light/dark mode, per-provider save, budgets & templates |
| **Reasoning-model params** | GPT-5 / o-series / DeepSeek-R1: omit `temperature`, send `max_completion_tokens` so playground and `->temperature()` never 400 |
| **Failover Priority Chain** | Customize and drag-and-drop provider priority order (`#1` tried first) |
| **JSON Recovery** | Strips markdown code blocks and repairs broken/unclosed braces automatically |
| **Retries & Backoff** | Exponential backoff with jitter on 429 and 5xx errors |
| **Real-time Cost Tracking** | Precise USD pricing calculation per 1M tokens from built-in pricing tables |
| **Spend budgets** | Monthly, per-provider, and per-job USD caps (`block` or `warn`) |
| **Zero-Latency Logging** | Logs saved automatically via `after_response` lifecycle or background queue |
| **Telemetry Dashboard** | 30-day spend, failure rate, JSON recovery rate, latency p95, daily cost chart |
| **Filament Widget** | Optional ready-to-use widget for Filament v3/v4 admin panels |
| **Filament nav (new tab)** | Admin sidebar **AI Hub** opens `/ai-hub` Studio in a new browser tab |

---

## Installation

```bash
# 1. Install via Composer
composer require imrandevbd/laravel-ai-hub

# 2. Publish configuration and migrations
php artisan vendor:publish --tag=ai-hub-config
php artisan migrate
```

### Upgrading

```bash
composer update imrandevbd/laravel-ai-hub
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Studio (`/ai-hub`) reads the package model catalog automatically after an upgrade. Re-publishing config is optional:

```bash
php artisan vendor:publish --tag=ai-hub-config --force
```

`--force` overwrites a previously published `config/ai-hub.php`. Skip it if you customized that file — new models still appear in the dropdown.

If Studio still shows the **old layout** (no Playground tab, no `Studio v1.4.0` badge), Laravel is using a published copy at `resources/views/vendor/ai-hub/studio.blade.php`. That file wins over the package. Either delete it, or refresh it:

```bash
php artisan vendor:publish --tag=ai-hub-views --force
php artisan view:clear
```

### Environment Configuration (`.env`)

```env
# Default Provider & Failover
AI_HUB_PROVIDER=gemini
AI_HUB_FAILOVER=true

# Security (Optional: Restrict /ai-hub studio access)
AI_HUB_ROLES="admin,developer"
AI_HUB_EMAILS="admin@yourdomain.com"
# AI_HUB_MIDDLEWARE="auth,role:admin"

# Provider API Keys
GEMINI_API_KEY=AIzaSy...
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GROK_API_KEY=xai-...
DEEPSEEK_API_KEY=sk-...
MISTRAL_API_KEY=...
GROQ_API_KEY=gsk_...
OPENROUTER_API_KEY=sk-or-...
AZURE_OPENAI_API_KEY=...
AZURE_OPENAI_ENDPOINT=https://YOUR-RESOURCE.openai.azure.com
TOGETHER_API_KEY=...
FIREWORKS_API_KEY=...
PERPLEXITY_API_KEY=...
# OLLAMA_BASE_URL=http://127.0.0.1:11434/v1
# AI_HUB_BUDGET_MONTHLY=50
# AI_HUB_BUDGET_ON_EXCEED=block
```

> **Tip:** You can leave `.env` empty and configure all keys and models visually via the **`/ai-hub`** Studio UI. Keys are stored encrypted in the database.

---

## Visual Studio (`/ai-hub`)

Visit **`/ai-hub`** in your browser after installing.

```
+------------------------------------------------------------------------------------+
|  Laravel AI Hub Studio                      Configured: [● 6 of 9 ready]          |
+------------------------------------------------------------------------------------+
|  Default Provider:  [★ Gemini 3.7 Flash]  [OpenAI]  [Claude]  [Grok]  [DeepSeek]   |
+------------------------------------------------------------------------------------+
|  [Google Gemini]            [OpenAI]                    [Anthropic Claude]         |
|  ● Saved & ready            ● Unsaved edits [Save]      ● Saved & ready            |
|  Key: •••••••• saved        Key: [sk-...           ]    Key: •••••••• saved        |
|  Model: [gemini-3.7-flash]  Model: [gpt-5.6-luna   ]    Model: [claude-3.7-sonnet] |
|  [Test] [Current Default]   [Test] [Revert] [Save]      [Test] [Set as Default]    |
+------------------------------------------------------------------------------------+
```

### Studio UI Highlights:
1. **Light & Dark Theme**: Header sun/moon toggle with preference saved in `localStorage`.
2. **Per-Card Instant Save**: Directly beside the API key and model selection on each provider card, an instant **`Save`** button lights up as soon as you type or make a change.
3. **Clear Saved Status Badges**:
   - `● Saved & ready` (Green badge) — Configured credentials stored in database.
   - `● Unsaved edits` (Amber badge) — Modified inputs waiting to be saved.
   - `No key added` (Slate badge) — Unconfigured provider.
   - `★ Default` (Cyan badge) — Active `#1` priority default provider.
4. **Floating Quick-Save Bar**: Automatically slides up if unsaved changes exist anywhere on the page, letting you save all modified providers with one click without scrolling.
5. **Interactive Connection Tester**: Test your API keys and models with live round-trip latency and token cost diagnostics.
6. **Playground**: Send or stream a prompt against any provider, attach an image URL, and save named templates. GPT-5 / o-series / DeepSeek-R1 sampling limits are applied automatically (`temperature` omitted, `max_tokens` remapped).
7. **Spend budgets**: Set a monthly USD cap on the Analytics tab (`block` requests or `warn` only).

### CLI Alternative (`ai-hub:configure`)

```bash
# Interactive configuration wizard
php artisan ai-hub:configure

# Direct provider setup
php artisan ai-hub:configure gemini --key=YOUR_KEY --model=gemini-3.7-flash --default
php artisan ai-hub:configure openai --key=YOUR_KEY --model=gpt-5.6-luna
php artisan ai-hub:configure deepseek --key=YOUR_KEY --model=deepseek-chat

# View current masked configuration
php artisan ai-hub:configure --show
```

---

## Supported Providers & August 2026 Models

| Provider | Key | August 2026 Flagship & Reasoning Models |
|---|---|---|
| **Google Gemini** | `gemini` | `gemini-3.7-flash`, `gemini-3.6-flash`, `gemini-3.1-pro`, `gemini-3.0-flash`, `gemini-2.5-pro`, `gemini-2.0-flash`, `text-embedding-004` |
| **OpenAI** | `openai` | `gpt-5.6-luna`, `gpt-5.6-terra`, `gpt-5.6-sol`, `gpt-5.5`, `gpt-5`, `o3-mini`, `o3`, `o4-mini`, `o1`, `gpt-4o-mini`, `gpt-4o` |
| **Anthropic Claude** | `claude` | `claude-3-7-sonnet-latest` (Hybrid Thinking), `claude-sonnet-4-20250514`, `claude-3-5-sonnet-latest`, `claude-3-5-haiku-latest`, `claude-3-opus-latest` |
| **xAI Grok** | `grok` | `grok-4.6`, `grok-4.5`, `grok-4.3`, `grok-4.1-fast`, `grok-3`, `grok-3-mini`, `grok-2-latest`, `grok-2-vision-1212` |
| **DeepSeek** | `deepseek` | `deepseek-chat` (DeepSeek-V3), `deepseek-reasoner` (DeepSeek-R1), `deepseek-coder` |
| **Mistral AI** | `mistral` | `mistral-large-latest`, `mistral-small-latest`, `codestral-latest`, `ministral-8b-latest`, `pixtral-large-latest` |
| **Groq (LPU Speed)** | `groq` | `llama-3.3-70b-versatile`, `deepseek-r1-distill-llama-70b`, `deepseek-r1-distill-qwen-32b`, `qwen-2.5-coder-32b`, `llama-3.1-8b-instant` |
| **Ollama (Local)** | `ollama` | `llama3.3`, `deepseek-r1`, `qwen2.5-coder`, `qwen2.5`, `mistral-small`, `phi4`, `gemma2`, `llama3.2-vision` |
| **OpenRouter** | `openrouter` | `google/gemini-3.7-flash`, `anthropic/claude-3.7-sonnet`, `openai/gpt-5`, `openai/o3-mini`, `deepseek/deepseek-r1` |
| **Azure OpenAI** | `azure` | Deployment name as model (`gpt-4o`, `gpt-5-mini`, `o3-mini`). Set `AZURE_OPENAI_ENDPOINT` |
| **Together** | `together` | `meta-llama/Llama-3.3-70B-Instruct-Turbo`, `deepseek-ai/DeepSeek-R1` |
| **Fireworks** | `fireworks` | `accounts/fireworks/models/llama-v3p3-70b-instruct`, `accounts/fireworks/models/deepseek-r1` |
| **Perplexity** | `perplexity` | `sonar-pro`, `sonar`, `sonar-reasoning-pro` |

---

## Usage Guide

```php
use ImranDevBd\AiHub\Facades\AIHub;

// 1. Basic Prompt (uses default provider & model)
$response = AIHub::prompt('Explain quantum computing in simple terms')->send();

echo $response->content;       // Generated text
echo $response->costUsd;       // Calculated token cost (e.g. 0.000412)
echo $response->latencyMs;     // Request duration (e.g. 312.4ms)
echo $response->totalTokens;   // Prompt + completion tokens

// 2. Specific Provider & Model Shortcuts
$response = AIHub::gemini('gemini-3.7-flash')
    ->prompt('Write a concise summary')
    ->send();

$response = AIHub::openai('gpt-5.6-luna')
    ->prompt('Write a high-performance database query')
    ->send();

$response = AIHub::claude('claude-3-7-sonnet-latest')
    ->prompt('Refactor this architecture')
    ->send();

$response = AIHub::deepseek('deepseek-reasoner')
    ->prompt('Solve this complex mathematical problem')
    ->send();
```

### JSON Auto-Recovery (Fixes Malformed LLM JSON)

LLMs often wrap JSON in markdown blocks or cut off closing brackets. `recoverJson()` automatically repairs the syntax:

```php
$data = AIHub::gemini()
    ->prompt('Return a user profile JSON object')
    ->recoverJson()
    ->send()
    ->json(); // Returns clean PHP array
```

### Streaming Responses

```php
foreach (AIHub::openai()->prompt('Write a technical essay')->stream() as $chunk) {
    echo $chunk;
    flush();
}
```

### Tools, vision, cache, and prompt templates

```php
// Vision
$response = AIHub::openai()
    ->prompt('Describe this image')
    ->image('https://example.com/photo.jpg')
    ->send();

// Function tools (OpenAI-style schema; mapped for Claude & Gemini)
$response = AIHub::openai()->tools([[
    'type' => 'function',
    'function' => [
        'name' => 'get_weather',
        'description' => 'Get the weather for a city',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'city' => ['type' => 'string'],
            ],
            'required' => ['city'],
        ],
    ],
]])->prompt('Weather in Dhaka?')->send();

$calls = $response->toolCalls; // run the tool, then continue with messages()

// Response cache (hits log type=cache_hit at $0)
$response = AIHub::gemini()->prompt($faq)->cache(3600)->send();

// Named templates saved in Studio Playground ({ticket} is interpolated)
$response = AIHub::promptTemplate('support.reply', ['ticket' => $body])->send();

// New providers
AIHub::azure('gpt-4o')->prompt('Hello from Azure')->send();
AIHub::together()->prompt('Hello from Together')->send();
AIHub::fireworks()->prompt('Hello from Fireworks')->send();
AIHub::perplexity('sonar-pro')->prompt('What shipped this week?')->send();
```

### Vector Embeddings

```php
$vector = AIHub::openai()
    ->model('text-embedding-3-small')
    ->embed('Laravel AI Hub semantic search')
    ->first(); // Returns float array vector
```

### Multi-turn Chat Conversations

```php
$response = AIHub::claude()->messages([
    ['role' => 'system', 'content' => 'You are a Senior Laravel Architect.'],
    ['role' => 'user', 'content' => 'How should I structure domain actions?'],
    ['role' => 'assistant', 'content' => 'Use single-responsibility action classes.'],
    ['role' => 'user', 'content' => 'Give me an example.'],
])->send();
```

### Tagging Workflows & Background Jobs

Tag your requests to trace spend by feature in Analytics:

```php
AIHub::gemini()
    ->forJob('invoice-ocr')
    ->prompt($invoicePrompt)
    ->send();
```

---

## Failover Priority Chain

Configure provider priority in `/ai-hub` or in `config/ai-hub.php`:

```php
'priority' => [
    'gemini',      // #1 Tried first
    'openai',      // #2 If Gemini is rate-limited or down
    'claude',      // #3
    'grok',        // #4
    'deepseek',    // #5
    'mistral',     // #6
    'groq',        // #7
    'ollama',      // #8
    'openrouter',  // #9
    'azure',       // #10
    'together',    // #11
    'fireworks',   // #12
    'perplexity',  // #13
],
'failover_enabled' => true,
```

```
Runtime execution:
#1 Gemini fails (429 Rate Limit) -> Auto Failover -> #2 OpenAI succeeds!
```

---

## How Logging Works (Zero Queue Worker Required)

The package captures comprehensive telemetry without slowing down your user's web requests:

```php
// config/ai-hub.php
'logging' => [
    'enabled' => env('AI_HUB_LOGGING', true),
    'async' => env('AI_HUB_LOGGING_ASYNC', 'after_response'), // 'after_response', 'queue', or 'sync'
    'prune_days' => 90, // Automatic log pruning
],
```

| Mode | Worker Needed? | User Latency | Description |
|---|:---:|:---:|---|
| **`after_response`** *(Default)* | **No** ❌ | **0ms** | Executes database logging immediately after HTTP response is returned to browser. |
| **`queue`** | **Yes** ✅ | **0ms** | Pushes `TrackAiUsageJob` to your background queue (Redis/Database/Horizon). |
| **`sync`** | **No** ❌ | ~1-2ms | Synchronous direct database insert (ideal for testing/CLI). |

### Automatic Log Pruning

`AiRequestLog` uses Laravel's `MassPrunable` trait. Clean up old logs automatically in your scheduler (`routes/console.php`):

```php
use ImranDevBd\AiHub\Models\AiRequestLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [AiRequestLog::class]])->daily();
```

---

## Security & Access Control for `/ai-hub`

The package includes built-in `AuthorizeStudio` middleware to protect `/ai-hub` Studio from unauthorized access.

### 1. Dedicated Callback (`AIHub::auth`) — *Recommended*

In your `app/Providers/AppServiceProvider.php` (or `AuthServiceProvider.php`):

```php
use ImranDevBd\AiHub\Facades\AIHub;

public function boot(): void
{
    AIHub::auth(function ($request) {
        return app()->environment('local') ||
               ($request->user() && in_array($request->user()->email, ['admin@yourdomain.com'])) ||
               ($request->user() && $request->user()->hasRole('admin'));
    });
}
```

### 2. Role & Email Protection via `.env`

```env
# Automatically verifies $user->hasRole(), $user->role, or is_admin
AI_HUB_ROLES="admin,super-admin,developer"

# Optional: Restrict to specific admin emails
AI_HUB_EMAILS="lead@company.com,devops@company.com"
```

### 3. Laravel Gate (`viewAiHub`)

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewAiHub', function (User $user) {
        return in_array($user->role, ['admin', 'developer']);
    });
}
```

### 4. Custom Middleware Stack via `.env`

```env
AI_HUB_MIDDLEWARE="auth,role:admin"
```

- **Guests**: Redirected to `/login` (or 403 for API calls).
- **Unauthorized users**: Blocked with `403 Forbidden`.
- **Local Environment**: Automatically enabled during local development.

---

## Filament admin

When Filament is installed, the panel sidebar gets an **AI Hub** item that opens Studio (`/ai-hub`) in a **new tab**. Dashboard widget stats do the same.

```php
use ImranDevBd\AiHub\Filament\AiHubPlugin;

$panel->plugin(AiHubPlugin::make()); // optional — auto-registered if omitted
```

```env
AI_HUB_FILAMENT=true
AI_HUB_FILAMENT_NEW_TAB=true
# AI_HUB_FILAMENT_GROUP="Settings"
```

---

## Analytics API

Access aggregated telemetry programmatically:

```php
// 30-day overview summary
$summary = AIHub::analytics()->summary(now()->subDays(30));
// ['total_cost_usd' => 12.45, 'requests' => 1420, 'failure_rate' => 0.4, 'json_recovery_rate' => 3.2]

// Breakdown of spend per provider
$costs = AIHub::analytics()->costByProvider();

// Latency percentiles
$latency = AIHub::analytics()->latencyPercentiles('gemini');
// ['p50' => 240, 'p95' => 580, 'p99' => 890]

// Top tracked jobs by token volume
$topJobs = AIHub::analytics()->topJobs(10);

// Daily spending trend
$daily = AIHub::analytics()->dailyCost(30);
```

---

## License

MIT © [Imran Dev BD](https://imrandev.bd/)

---

## Developed by Imran Dev BD

| Platform | Link |
|---|---|
| Portfolio | [imrandev.bd](https://imrandev.bd/) |
| LinkedIn | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) |
| GitHub | [github.com/imranbru99](https://github.com/imranbru99) |
| X / Twitter | [@imrandev_bd](https://x.com/imrandev_bd) |
| YouTube | [@ImranDevBD](https://youtube.com/@ImranDevBD) |
| Facebook | [ExpertImranDev](https://facebook.com/ExpertImranDev) |
| WhatsApp | [+880 1576-918420](https://wa.me/8801576918420) |
| Email | [me@imrandev.bd](mailto:me@imrandev.bd) |
| All Links | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

**Need help or have questions? Contact → [imrandev.bd/contact](https://imrandev.bd/contact)**
