<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('ai-hub.logging.table', 'ai_hub_request_logs');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->string('model', 120)->index();
            $table->string('type', 30)->default('complete')->index();
            $table->string('job')->nullable()->index();
            $table->boolean('success')->default(false)->index();
            $table->boolean('json_recovered')->default(false)->index();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_usd', 12, 8)->default(0);
            $table->decimal('latency_ms', 12, 2)->default(0);
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->text('error')->nullable();
            $table->string('content_preview', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'provider']);
            $table->index(['created_at', 'success']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai-hub.logging.table', 'ai_hub_request_logs'));
    }
};
