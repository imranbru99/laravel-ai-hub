# Laravel AI Hub

<p align="center">
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/dt/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/php-v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="PHP"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-teal.svg?style=flat-square" alt="License"></a>
  <a href="https://imrandev.bd"><img src="https://img.shields.io/badge/by-Imran%20Dev%20BD-14b8a6.svg?style=flat-square" alt="Imran Dev BD"></a>
</p>

**One interface. Nine AI providers. Full cost & failure observability.**

Swap **OpenAI**, **Gemini**, **Claude**, **Grok**, **DeepSeek**, **Mistral**, **Groq**, **Ollama**, or **OpenRouter** via config or a premium visual UI — every call is logged, costed, retried, and (optionally) JSON-repaired automatically. Set provider **priority** so `#1` runs first and failover continues down the chain.

Built by **[Imran Dev BD](https://imrandev.bd/)** · Laravel **10–13** · PHP **8.2+**

```bash
composer require imrandevbd/laravel-ai-hub
```

**Packagist:** [`imrandevbd/laravel-ai-hub`](https://packagist.org/packages/imrandevbd/laravel-ai-hub)  
**GitHub:** [github.com/imranbru99/laravel-ai-hub](https://github.com/imranbru99/laravel-ai-hub)

---

## Why this package?

| | |
|---|---|
| **One API** | Same fluent chain for every provider |
| **Studio UI** | `/ai-hub` — keys, models, priority, analytics |
| **Failover** | `#1 → #2 → #3…` when a provider fails |
| **Money & health** | USD cost, failure %, JSON recovery %, latency p50/p95/p99 |
| **Local + cloud** | Ollama locally · OpenRouter for 100s of models |

---

## Features

| Feature | What you get |
|---------|----------------|
| Multi-provider | OpenAI · Gemini · Claude · Grok · DeepSeek · Mistral · Groq · Ollama · OpenRouter |
| Visual studio | `/ai-hub` — keys, models, priority, analytics |
| Priority failover | `#1 → #2 → #3` on failure |
| JSON recovery | Brace-depth parser for malformed LLM JSON |
| Retries | Exponential backoff + jitter |
| Cost tracking | USD from per-model pricing tables |
| Analytics | Failure rate, JSON recovery rate, latency p50/p95/p99, top jobs |
| Fluent API | `AIHub::gemini()->prompt()->recoverJson()->send()` |
| Async logging | `AiRequestLog` + queue job (non-blocking) |

---

## Install

```bash
composer require imrandevbd/laravel-ai-hub
php artisan vendor:publish --tag=ai-hub-config
php artisan migrate
```

### `.env` (optional — you can also set keys in the UI)

```env
AI_HUB_PROVIDER=openai
AI_HUB_FAILOVER=true
AI_HUB_MIDDLEWARE=auth   # protect /ai-hub in production

OPENAI_API_KEY=sk-...
GEMINI_API_KEY=...
ANTHROPIC_API_KEY=...
GROK_API_KEY=...
DEEPSEEK_API_KEY=...
MISTRAL_API_KEY=...
GROQ_API_KEY=...
OPENROUTER_API_KEY=...
# OLLAMA_BASE_URL=http://127.0.0.1:11434/v1
```

---

## Visual studio (`/ai-hub`)

Open **`/ai-hub`** after install.

### Tabs

1. **Keys & Models** — paste API keys, pick models, enable/disable providers, **Test** connection  
2. **Priority** — reorder who runs first (`↑` `↓` / **First**). Failover walks the list on errors  
3. **Analytics** — 30-day cost, failure %, JSON recovery %, latency p95, cost by provider, top jobs, daily chart  

Built with **Tailwind CSS** + **Alpine.js** (CDN) — modern dark premium UI.

### CLI alternative

```bash
php artisan ai-hub:configure
php artisan ai-hub:configure gemini --key=YOUR_KEY --model=gemini-2.0-flash --default
php artisan ai-hub:configure deepseek --key=YOUR_KEY --model=deepseek-chat
php artisan ai-hub:configure --show
```

---

## Usage

```php
use ImranDevBd\AiHub\Facades\AIHub;

// Shortcuts
$response = AIHub::gemini('gemini-2.0-flash')
    ->prompt('Summarize Laravel queues in 3 bullets')
    ->send();

echo $response->content;
echo $response->costUsd;
echo $response->latencyMs;

// JSON + recovery (great for Gemini)
$data = AIHub::provider('gemini')
    ->prompt('Return JSON: {"title":"","tags":[]}')
    ->recoverJson()
    ->send()
    ->json();

// Uses priority chain (#1 first, then failover)
AIHub::prompt('Hello')->send();

// Lock to one provider
AIHub::provider('openai')->withoutFailover()->prompt('Hello')->send();

// Persist settings for the whole app
AIHub::configure('openai', 'sk-...', 'gpt-4o-mini', makeDefault: true);

// Job tracing (shows in Analytics → Top jobs)
AIHub::gemini()->forJob(MyJob::class)->prompt($prompt)->send();
```

### Streaming & embeddings

```php
foreach (AIHub::openai()->prompt('Write a haiku')->stream() as $chunk) {
    echo $chunk;
}

$vector = AIHub::openai()
    ->model('text-embedding-3-small')
    ->embed('Laravel AI Hub')
    ->first();
```

### Analytics API

```php
AIHub::analytics()->summary(now()->subDays(30));
AIHub::analytics()->costByProvider();
AIHub::analytics()->latencyPercentiles('gemini');
AIHub::analytics()->topJobs(10);
AIHub::analytics()->dailyCost(30);
```

---

## Priority / failover

Set order in **`/ai-hub` → Priority** or config:

```php
// config/ai-hub.php
'priority' => ['gemini', 'openai', 'claude', 'grok', 'deepseek', 'mistral', 'groq', 'ollama', 'openrouter'],
'failover_enabled' => true,
```

Runtime:

```
#1 Gemini fails → #2 OpenAI → #3 Claude → …
```

---

## Providers

| Key | Env | Capabilities |
|-----|-----|----------------|
| `openai` | `OPENAI_API_KEY` | Chat · stream · embeddings |
| `gemini` | `GEMINI_API_KEY` | Chat · stream · embeddings |
| `claude` | `ANTHROPIC_API_KEY` | Chat · stream |
| `grok` | `GROK_API_KEY` / `XAI_API_KEY` | Chat · stream · embeddings |
| `deepseek` | `DEEPSEEK_API_KEY` | Chat · stream · embeddings |
| `mistral` | `MISTRAL_API_KEY` | Chat · stream · embeddings |
| `groq` | `GROQ_API_KEY` | Chat · stream · embeddings (fast) |
| `ollama` | `OLLAMA_BASE_URL` (key optional) | Local OpenAI-compatible |
| `openrouter` | `OPENROUTER_API_KEY` | Chat · stream · embeddings (router) |

```php
AIHub::deepseek()->prompt('Explain recursion')->send();
AIHub::mistral('mistral-large-latest')->prompt($prompt)->send();
AIHub::groq()->prompt('Fast answer')->send();
AIHub::ollama('llama3.2')->prompt('Hi from local')->send();
AIHub::openrouter('anthropic/claude-3.5-sonnet')->prompt($prompt)->send();
```

Extend later via custom `base_url`: Azure OpenAI, Cohere, Perplexity, Bedrock proxies, etc.

---

## Package layout

```
src/
  AIHubManager.php · PendingRequest.php
  Facades/AIHub.php
  Providers/   OpenAI · Gemini · Claude · OpenAICompatible (Grok, DeepSeek, Mistral, Groq, Ollama, OpenRouter)
  Support/     JsonRecovery · RetryHandler · CostCalculator · Analytics · SettingsStore · ProviderCatalog
  Http/Controllers/StudioController.php
  Models/      AiRequestLog · AiHubSetting
  Jobs/        TrackAiUsageJob
  Filament/    AiHubDashboardWidget (optional)
  Console/     ConfigureAiHubCommand
config/ai-hub.php
resources/views/studio.blade.php
database/migrations/
```

---

## Security

Protect `/ai-hub` in production:

```env
AI_HUB_MIDDLEWARE=auth
```

Prefer the visual UI for keys on shared hosts; keep `.env` out of git.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT © [Imran Dev BD](https://imrandev.bd/)

---

## Developed by Imran Dev BD

| | |
|---|---|
| Portfolio | [imrandev.bd](https://imrandev.bd/) |
| LinkedIn | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) |
| GitHub | [github.com/imranbru99](https://github.com/imranbru99) |
| X / Twitter | [@imrandev_bd](https://x.com/imrandev_bd) |
| YouTube | [@ImranDevBD](https://youtube.com/@ImranDevBD) |
| Instagram | [@imranbru99](https://instagram.com/imranbru99) |
| Facebook | [ExpertImranDev](https://facebook.com/ExpertImranDev) |
| TikTok | [@imrandev_bd](https://tiktok.com/@imrandev_bd) |
| Threads | [@imranbru99](https://www.threads.net/@imranbru99) |
| Pinterest | [@imrandev_bd](https://pinterest.com/imrandev_bd) |
| WhatsApp | [+880 1576-918420](https://wa.me/8801576918420) |
| Email | [me@imrandev.bd](mailto:me@imrandev.bd) |
| All Links | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

**Any issue? Contact → [imrandev.bd/contact](https://imrandev.bd/contact)**
