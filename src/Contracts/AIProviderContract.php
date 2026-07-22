<?php

namespace ImranDevBd\AiHub\Contracts;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;

interface AIProviderContract
{
    public function complete(array $payload): AiResponse;

    /**
     * @return \Generator<int, string>
     */
    public function stream(array $payload): \Generator;

    public function embed(array $payload): EmbeddingResponse;

    public function name(): string;
}
