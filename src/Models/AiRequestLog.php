<?php

namespace ImranDevBd\AiHub\Models;

use Illuminate\Database\Eloquent\Model;

class AiRequestLog extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('ai-hub.logging.table', 'ai_hub_request_logs');
    }

    protected $fillable = [
        'provider',
        'model',
        'type',
        'job',
        'success',
        'json_recovered',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'latency_ms',
        'attempts',
        'error',
        'content_preview',
        'meta',
    ];

    protected $casts = [
        'success' => 'boolean',
        'json_recovered' => 'boolean',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost_usd' => 'float',
        'latency_ms' => 'float',
        'attempts' => 'integer',
        'meta' => 'array',
    ];
}
