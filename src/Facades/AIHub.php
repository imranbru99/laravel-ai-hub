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
 * @method static \ImranDevBd\AiHub\PendingRequest deepseek(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest mistral(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest groq(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest ollama(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest openrouter(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest azure(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest together(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest fireworks(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest perplexity(?string $model = null, ?string $apiKey = null)
 * @method static \ImranDevBd\AiHub\PendingRequest promptTemplate(string $name, array $vars = [])
 * @method static \ImranDevBd\AiHub\PendingRequest model(string $model)
 * @method static \ImranDevBd\AiHub\PendingRequest prompt(string $prompt)
 * @method static \ImranDevBd\AiHub\PendingRequest apiKey(string $apiKey)
 * @method static \ImranDevBd\AiHub\PendingRequest structured(array $schema, ?string $name = 'response')
 * @method static \ImranDevBd\AiHub\PendingRequest asJsonSchema(array $schema, ?string $name = 'response', ?string $description = null)
 * @method static array configure(string $provider, ?string $apiKey = null, ?string $model = null, bool $makeDefault = true)
 * @method static array models(?string $provider = null)
 * @method static array capabilities(string $model)
 * @method static \ImranDevBd\AiHub\Chat\Conversation chat(?string $provider = null, ?string $model = null)
 * @method static \ImranDevBd\AiHub\Testing\AIHubFake fake(array|\Closure|\ImranDevBd\AiHub\Data\AiResponse|string $responses = [])
 * @method static \ImranDevBd\AiHub\Data\AiResponse response(array|string $content = '', string $provider = 'fake', string $model = 'fake-model', array $extra = [])
 * @method static \ImranDevBd\AiHub\Support\Analytics analytics()
 * @method static \ImranDevBd\AiHub\Support\SettingsStore settings()
 * @method static void auth(\Closure $callback)
 * @method static bool check(mixed $request)
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
