<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'whatsapp' => [
                'enabled' => true,
                'provider' => 'meta_cloud_api',
                'api_base_url' => 'https://graph.facebook.com',
                'api_version' => '',
                'phone_number_id' => '',
                'business_account_id' => '',
                'access_token' => '',
                'webhook_verify_token' => '',
                'webhook_callback_url' => '',
                'default_country_code' => '52',
                'message_unit_cost' => 0.0,
                'currency_code' => 'MXN',
                'monthly_budget' => 0.0,
                'notes' => '',
            ],
            'datasets' => [],
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
        $whatsappEnabled = (bool) data_get(
            $config,
            'whatsapp.enabled',
            data_get($config, 'integrations.whatsapp_enabled', data_get($merged, 'whatsapp.enabled', true))
        );

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
                'whatsapp_enabled' => $whatsappEnabled,
                'webhook_url' => (string) data_get($merged, 'integrations.webhook_url', ''),
            ],
            'whatsapp' => [
                'enabled' => $whatsappEnabled,
                'provider' => (string) data_get($merged, 'whatsapp.provider', 'meta_cloud_api'),
                'api_base_url' => (string) data_get($merged, 'whatsapp.api_base_url', 'https://graph.facebook.com'),
                'api_version' => (string) data_get($merged, 'whatsapp.api_version', ''),
                'phone_number_id' => (string) data_get($merged, 'whatsapp.phone_number_id', ''),
                'business_account_id' => (string) data_get($merged, 'whatsapp.business_account_id', ''),
                'access_token' => (string) data_get($merged, 'whatsapp.access_token', ''),
                'webhook_verify_token' => (string) data_get($merged, 'whatsapp.webhook_verify_token', ''),
                'webhook_callback_url' => (string) data_get(
                    $merged,
                    'whatsapp.webhook_callback_url',
                    data_get($merged, 'integrations.webhook_url', '')
                ),
                'default_country_code' => (string) data_get($merged, 'whatsapp.default_country_code', '52'),
                'message_unit_cost' => (float) data_get($merged, 'whatsapp.message_unit_cost', 0.0),
                'currency_code' => strtoupper((string) data_get($merged, 'whatsapp.currency_code', 'MXN')),
                'monthly_budget' => (float) data_get($merged, 'whatsapp.monthly_budget', 0.0),
                'notes' => (string) data_get($merged, 'whatsapp.notes', ''),
            ],
            'datasets' => self::sanitizeDatasets(data_get($merged, 'datasets', [])),
            'security' => [
                'api_key' => (string) data_get($merged, 'security.api_key', ''),
            ],
        ];
    }

    private static function sanitizeDatasets(mixed $datasets): array
    {
        if (! is_array($datasets)) {
            return [];
        }

        $normalizedDatasets = [];

        foreach ($datasets as $dataset) {
            if (! is_array($dataset)) {
                continue;
            }

            $storagePath = (string) data_get($dataset, 'storage_path', '');
            $url = (string) data_get($dataset, 'url', '');

            if ($url === '' && $storagePath !== '') {
                $url = url(Storage::disk('public')->url($storagePath));
            }

            $deterministicSeed = $storagePath !== '' ? $storagePath : ($url.(string) data_get($dataset, 'file_name', 'dataset'));
            $id = (string) data_get($dataset, 'id', sha1($deterministicSeed));

            $normalizedDatasets[] = [
                'id' => $id,
                'file_name' => (string) data_get($dataset, 'file_name', 'dataset'),
                'file_type' => (string) data_get($dataset, 'file_type', ''),
                'file_size' => (int) data_get($dataset, 'file_size', 0),
                'url' => $url,
                'uploaded_at' => (string) data_get($dataset, 'uploaded_at', now()->toISOString()),
                'storage_path' => $storagePath,
            ];
        }

        return $normalizedDatasets;
    }
}
