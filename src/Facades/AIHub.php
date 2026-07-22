<?php

namespace ImranDevBd\AiHub\Facades;

use Illuminate\Support\Facades\Facade;
use ImranDevBd\AiHub\AIHubManager;
use ImranDevBd\AiHub\PendingRequest;
use ImranDevBd\AiHub\Support\Analytics;

/**
 * @method static \ImranDevBd\AiHub\PendingRequest provider(?string $name = null)
 * @method static \ImranDevBd\AiHub\PendingRequest openai(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest gemini(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest claude(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest grok(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest model(string $model)
 * @method static \ImranDevBd\AiHub\PendingRequest prompt(string $prompt)
 * @method static \ImranDevBd\AiHub\PendingRequest apiKey(string $apiKey)
 * @method static array configure(string $provider, ?string $apiKey = null, ?string $model = null, bool $makeDefault = true)
 * @method static \ImranDevBd\AiHub\Support\Analytics analytics()
 * @method static \ImranDevBd\AiHub\Support\SettingsStore settings()
 *
 * @see \ImranDevBd\AiHub\AIHubManager
 */
class AIHub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AIHubManager::class;
    }
}
