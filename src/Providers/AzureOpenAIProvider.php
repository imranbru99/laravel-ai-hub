<?php

namespace ImranDevBd\AiHub\Providers;

class AzureOpenAIProvider extends OpenAIProvider
{
    public function name(): string
    {
        return 'azure';
    }

    protected function chatUrl(string $model): string
    {
        return sprintf(
            '%s/openai/deployments/%s/chat/completions?api-version=%s',
            rtrim($this->baseUrl(), '/'),
            rawurlencode($model),
            urlencode($this->apiVersion())
        );
    }

    protected function embeddingsUrl(string $model): string
    {
        return sprintf(
            '%s/openai/deployments/%s/embeddings?api-version=%s',
            rtrim($this->baseUrl(), '/'),
            rawurlencode($model),
            urlencode($this->apiVersion())
        );
    }

    protected function client()
    {
        return $this->http()->withHeaders([
            'api-key' => $this->apiKey(),
        ]);
    }

    protected function apiVersion(): string
    {
        return (string) ($this->config['api_version'] ?? '2024-10-21');
    }
}
