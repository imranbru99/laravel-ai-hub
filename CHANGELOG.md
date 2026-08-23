# Changelog

All notable changes to **Laravel AI Hub** are documented here.

## [1.2.0] — 2026-08-23

### Added
- Complete August 2026 marketplace model update across all 9 AI providers:
  - **Gemini**: Added `gemini-3.7-flash`, `gemini-3.6-flash`, `gemini-3.1-pro`, `gemini-3.0-flash`, `gemini-3.0-pro`, `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.0-pro-exp-02-05`, `gemini-2.0-flash-thinking-exp-01-21`, `gemini-1.5-flash-8b`, and `text-embedding-004`, while retaining all `2.0` and `1.5` models.
  - **OpenAI**: Added `gpt-5.6-luna`, `gpt-5.6-terra`, `gpt-5.6-sol`, `gpt-5.5`, `gpt-5.4`, `gpt-5`, `gpt-5-mini`, `gpt-5-pro`, `o3`, `o3-mini`, `o4-mini`, `o1`, `o1-mini`, `o1-preview`, `chatgpt-4o-latest`, `gpt-4-turbo`, while retaining `gpt-4o-mini`, `gpt-4o`, `gpt-4.1`, and `gpt-4.1-mini`.
  - **Grok**: Added `grok-4.6`, `grok-4.5`, `grok-4.3`, `grok-4.1-fast`, `grok-3`, `grok-3-mini`, `grok-2-1212`, `grok-2-vision-1212`, while retaining `grok-2-latest`, `grok-2`, and `grok-beta`.
  - **Claude**: Added `claude-3-7-sonnet-latest` (hybrid reasoning), `claude-3-7-sonnet-20250219`, `claude-sonnet-4-20250514`, `claude-3-5-sonnet-20241022`, `claude-3-5-haiku-20241022`, `claude-3-opus-20240229`, `claude-3-haiku-20240307`, while retaining `claude-3-5-sonnet-latest`, `claude-3-5-haiku-latest`, `claude-3-opus-latest`.
  - **OpenRouter**: Added `google/gemini-3.7-flash`, `google/gemini-3.1-pro`, `google/gemini-3-flash`, `anthropic/claude-3.7-sonnet`, `anthropic/claude-3.7-sonnet:thinking`, `openai/gpt-5`, `openai/gpt-5-mini`, `openai/gpt-5-pro`, `openai/o3-mini`, `openai/o1`, and `x-ai/grok-2-1212`.
  - **DeepSeek**: Refined models & pricing for `deepseek-chat` (DeepSeek-V3), `deepseek-reasoner` (DeepSeek-R1), and `deepseek-coder`.
  - **Mistral**: Added `ministral-8b-latest`, `ministral-3b-latest`, `pixtral-large-latest`, `pixtral-12b-2409`, `open-codestral-mamba`, and `mistral-embed`.
  - **Groq**: Added `deepseek-r1-distill-llama-70b`, `deepseek-r1-distill-qwen-32b`, `llama-3.3-70b-specdec`, `qwen-2.5-32b`, `qwen-2.5-coder-32b`, `llama-3.2-11b-vision-preview`, `llama-3.2-90b-vision-preview`, `llama-3.2-3b-preview`, `llama-3.2-1b-preview`, and `llama-3.1-70b-versatile`.
  - **Ollama**: Added `llama3.3`, `deepseek-r1`, `qwen2.5-coder`, `qwen2.5`, `mistral-small`, `phi4`, `llama3.2-vision`, and `gemma2`.
- Updated comprehensive token pricing matrix in `config/ai-hub.php` with official standard rates per 1M tokens.
- **Built-in Route Authorization & Role-Based Access**:
  - Added `AuthorizeStudio` middleware for automatic protection of `/ai-hub` Studio UI and API routes.
  - Added `AIHub::auth(\Closure $callback)` for defining custom authorization callbacks (Horizon/Telescope style).
  - Added `AI_HUB_ROLES` and `AI_HUB_EMAILS` config/environment options for instant role-based access control.
  - Added automatic Laravel Gate (`viewAiHub`) resolution for team permissions.

## [1.1.1] — 2026-08-15

### Fixed
- Fixed model select dropdown initialization in Studio UI (`/ai-hub`) by populating options directly via Blade rather than template loops inside `<select>` tags.

### Added
- Added multi-middleware parsing (comma-separated strings and arrays) in `AiHubServiceProvider` to easily protect `/ai-hub` with authentication, roles, and gates via `.env` or config.
- Documented role & gate access control in `README.md`.

## [1.1.0] — 2026-07-23

### Added
- **DeepSeek**, **Mistral**, **Groq**, **Ollama**, and **OpenRouter** providers (OpenAI-compatible)
- Shared `OpenAICompatibleProvider` + `ProviderCatalog` for scalable provider lists
- Fluent shortcuts: `deepseek()`, `mistral()`, `groq()`, `ollama()`, `openrouter()`
- Popular models + pricing tables for the new providers
- Studio UI / CLI / settings store now cover all nine providers

### Changed
- Grok now uses the OpenAI-compatible driver path
- README, keywords, and package description updated for the expanded provider set

## [1.0.0] — 2026-07-22

### Added
- Initial release: OpenAI, Gemini, Claude, Grok
- Fluent `AIHub` API, retries, JSON recovery, cost calculator
- Async usage logging + analytics
- Visual studio at `/ai-hub` (keys, priority failover, analytics)
- `ai-hub:configure` Artisan command
- Optional Filament dashboard widget stub
