<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgentConfig extends Model
{
    protected $fillable = [
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public static function defaultConfig(): array
    {
        return [
            'general' => [
                'name' => '',
                'purpose' => '',
                'language' => 'es',
                'tone' => 'friendly',
                'welcome_message' => '',
                'fallback_message' => '',
                'system_prompt' => '',
            ],
            'model' => [
                'model_name' => 'gpt-5',
                'temperature' => 0.7,
                'max_tokens' => 500,
            ],
            'memory' => [
                'enabled' => true,
                'max_messages' => 15,
            ],
            'rag' => [
                'enabled' => false,
                'top_k' => 3,
            ],
            'behavior' => [
                'response_mode' => 'medium',
                'human_fallback' => true,
            ],
            'integrations' => [
                'whatsapp_enabled' => true,
                'webhook_url' => '',
            ],
            'security' => [
                'api_key' => '',
            ],
        ];
    }

    public static function active(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public static function activeConfig(): array
    {
        $config = self::active()?->config;

        if (! is_array($config)) {
            return self::defaultConfig();
        }

        return self::sanitizeConfig($config);
    }

    public static function saveAsActive(array $config): self
    {
        $sanitizedConfig = self::sanitizeConfig($config);

        return DB::transaction(function () use ($sanitizedConfig) {
            self::query()->where('is_active', true)->update(['is_active' => false]);

            $record = self::query()->oldest('id')->first();

            if (! $record) {
                return self::query()->create([
                    'config' => $sanitizedConfig,
                    'is_active' => true,
                ]);
            }

            $record->fill([
                'config' => $sanitizedConfig,
                'is_active' => true,
            ]);
            $record->save();

            return $record->refresh();
        });
    }

    public static function sanitizeConfig(array $config): array
    {
        $merged = array_replace_recursive(self::defaultConfig(), $config);

        return [
            'general' => [
                'name' => (string) data_get($merged, 'general.name', ''),
                'purpose' => (string) data_get($merged, 'general.purpose', ''),
                'language' => (string) data_get($merged, 'general.language', 'es'),
                'tone' => (string) data_get($merged, 'general.tone', 'friendly'),
                'welcome_message' => (string) data_get($merged, 'general.welcome_message', ''),
                'fallback_message' => (string) data_get($merged, 'general.fallback_message', ''),
                'system_prompt' => (string) data_get($merged, 'general.system_prompt', ''),
            ],
            'model' => [
                'model_name' => (string) data_get($merged, 'model.model_name', 'gpt-5'),
                'temperature' => (float) data_get($merged, 'model.temperature', 0.7),
                'max_tokens' => (int) data_get($merged, 'model.max_tokens', 500),
            ],
            'memory' => [
                'enabled' => (bool) data_get($merged, 'memory.enabled', true),
                'max_messages' => (int) data_get($merged, 'memory.max_messages', 15),
            ],
            'rag' => [
                'enabled' => (bool) data_get($merged, 'rag.enabled', false),
                'top_k' => (int) data_get($merged, 'rag.top_k', 3),
            ],
            'behavior' => [
                'response_mode' => (string) data_get($merged, 'behavior.response_mode', 'medium'),
                'human_fallback' => (bool) data_get($merged, 'behavior.human_fallback', true),
            ],
            'integrations' => [
                'whatsapp_enabled' => (bool) data_get($merged, 'integrations.whatsapp_enabled', true),
                'webhook_url' => (string) data_get($merged, 'integrations.webhook_url', ''),
            ],
            'security' => [
                'api_key' => (string) data_get($merged, 'security.api_key', ''),
            ],
        ];
    }
}
