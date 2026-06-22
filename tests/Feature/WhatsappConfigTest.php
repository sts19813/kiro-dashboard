<?php

namespace Tests\Feature;

use App\Models\AgentConfig;
use App\Models\TourismUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_whatsapp_config_with_message_stats(): void
    {
        AgentConfig::saveAsActive([
            'whatsapp' => [
                'message_unit_cost' => 0.50,
                'currency_code' => 'MXN',
            ],
        ]);

        $tourismUser = TourismUser::query()->create([
            'phone' => '+529990000000',
            'name' => 'Usuario demo',
            'currency_code' => 'MXN',
            'preferences' => [],
            'is_active' => true,
        ]);

        $tourismUser->chatMessages()->createMany([
            [
                'role' => 'assistant',
                'message' => 'Hola, te puedo ayudar.',
                'sent_at' => now(),
            ],
            [
                'role' => 'system',
                'message' => 'Mensaje automatico de seguimiento.',
                'sent_at' => now(),
            ],
            [
                'role' => 'user',
                'message' => 'Gracias.',
                'sent_at' => now(),
            ],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/config/whatsapp');

        $response->assertOk();
        $response->assertSee('Configuracion de WhatsApp');
        $response->assertSee('Usuario demo');
        $response->assertSee('$1.00');
    }

    public function test_whatsapp_config_can_be_saved_and_keeps_secret_fields_when_blank(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/config/whatsapp', [
            'whatsapp' => [
                'enabled' => '1',
                'provider' => 'meta_cloud_api',
                'api_base_url' => 'https://graph.facebook.com',
                'api_version' => 'v20.0',
                'phone_number_id' => '123456',
                'business_account_id' => '987654',
                'access_token' => 'secret-token',
                'webhook_verify_token' => 'verify-token',
                'webhook_callback_url' => 'https://example.test/webhooks/whatsapp',
                'default_country_code' => '52',
                'message_unit_cost' => '0.25',
                'currency_code' => 'MXN',
                'monthly_budget' => '500',
                'notes' => 'Cuenta principal',
            ],
        ])->assertRedirect(route('settings.whatsapp'));

        $this->actingAs($user)->post('/config/whatsapp', [
            'whatsapp' => [
                'enabled' => '1',
                'provider' => 'meta_cloud_api',
                'api_base_url' => 'https://graph.facebook.com',
                'api_version' => 'v20.0',
                'phone_number_id' => '123456',
                'business_account_id' => '987654',
                'access_token' => '',
                'webhook_verify_token' => '',
                'webhook_callback_url' => 'https://example.test/webhooks/whatsapp',
                'default_country_code' => '52',
                'message_unit_cost' => '0.40',
                'currency_code' => 'MXN',
                'monthly_budget' => '500',
                'notes' => 'Cuenta principal',
            ],
        ])->assertRedirect(route('settings.whatsapp'));

        $config = AgentConfig::activeConfig();

        $this->assertSame('secret-token', data_get($config, 'whatsapp.access_token'));
        $this->assertSame('verify-token', data_get($config, 'whatsapp.webhook_verify_token'));
        $this->assertSame(0.40, data_get($config, 'whatsapp.message_unit_cost'));
        $this->assertSame('https://example.test/webhooks/whatsapp', data_get($config, 'integrations.webhook_url'));
    }
}
