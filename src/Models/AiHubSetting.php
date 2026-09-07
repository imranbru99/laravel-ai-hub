<?php

namespace ImranDevBd\AiHub\Models;

use Illuminate\Database\Eloquent\Model;

class AiHubSetting extends Model
{
    protected $table = 'ai_hub_settings';

    protected $fillable = [
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
