# Changelog

All notable changes to **Laravel AI Hub** are documented here.

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
