<?php

namespace Tests\Feature;

use App\Models\LocationPoint;
use App\Models\TourismUser;
use App\Services\OpenAiResponseClient;
use App\Services\TourismAgentChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class TourismAgentChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_address_request_uses_catalog_record(): void
    {
        Cache::flush();

        LocationPoint::query()->create([
            'name' => 'AGUANTINTA',
            'category' => 'bar',
            'city' => 'Mérida',
            'address' => '25 104, MEXICO, 97125, Mérida, Yucatán, México',
            'email' => 'AGUATINTABISTROENOTECA@GMAIL.COM',
            'description' => 'Bares, cantinas y similares',
            'lat' => 21.0023779,
            'lng' => -89.6098603,
            'tags' => ['bar', 'cantina', 'servicio', 'Mérida'],
            'source' => 'test',
            'is_active' => true,
        ]);

        $this->mock(OpenAiResponseClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('createText')
                ->once()
                ->andReturn(json_encode([
                    'needs_recommendations' => true,
                    'search_query' => 'AGUANTINTA',
                    'radius_km' => 25,
                    'limit' => 5,
                    'user_intent' => 'exact_place_info',
                    'missing_fields' => [],
                    'budget' => null,
                    'requested_fields' => ['address'],
                    'target_names' => ['AGUANTINTA'],
                ]));

            $mock->shouldReceive('createText')
                ->once()
                ->withArgs(function (string $instructions, string $input): bool {
                    $payload = json_decode($input, true);

                    $this->assertStringContainsString('exact_place_info', $instructions);
                    $this->assertSame('exact_place_info', data_get($payload, 'decision_plan.user_intent'));
                    $this->assertSame('exact_lookup', data_get($payload, 'catalog_context.basis'));
                    $this->assertSame('AGUANTINTA', data_get($payload, 'catalog_context.locations.0.name'));
                    $this->assertSame(
                        '25 104, MEXICO, 97125, Mérida, Yucatán, México',
                        data_get($payload, 'catalog_context.locations.0.address')
                    );

                    return true;
                })
                ->andReturn('La dirección exacta de AGUANTINTA es 25 104, MEXICO, 97125, Mérida, Yucatán, México.');
        });

        $response = app(TourismAgentChatService::class)->reply([
            'phone' => '+529990000000',
            'name' => 'Usuario demo',
            'message' => 'me interesa ir a AGUANTINTA, me puedes dar la direccion exacta',
        ]);

        $this->assertStringContainsString('25 104, MEXICO, 97125', $response['reply']);
        $this->assertSame('exact_lookup', data_get($response, 'catalog.basis'));
        $this->assertSame('AGUANTINTA', data_get($response, 'catalog.locations.0.name'));
    }

    public function test_exact_address_follow_up_can_use_previous_catalog_order(): void
    {
        Cache::flush();

        $user = TourismUser::query()->create([
            'phone' => '+529991111111',
            'name' => 'Usuario demo',
            'currency_code' => 'MXN',
            'is_active' => true,
            'preferences' => [],
        ]);

        $user->chatMessages()->create([
            'role' => 'assistant',
            'message' => 'Te recomiendo SNOWKI y AGUANTINTA.',
            'metadata' => [
                'catalog' => [
                    'locations' => [
                        [
                            'id' => 'snowki',
                            'name' => 'SNOWKI FROZEN NATURAL BAR',
                            'category' => 'bar',
                            'city' => 'Mérida',
                            'address' => 'Calle 1, Campestre, Mérida',
                        ],
                        [
                            'id' => 'aguantinta',
                            'name' => 'AGUANTINTA',
                            'category' => 'bar',
                            'city' => 'Mérida',
                            'address' => '25 104, MEXICO, 97125, Mérida, Yucatán, México',
                        ],
                    ],
                ],
            ],
            'sent_at' => now()->subMinute(),
        ]);

        $this->mock(OpenAiResponseClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('createText')
                ->once()
                ->andReturn(json_encode([
                    'needs_recommendations' => true,
                    'search_query' => null,
                    'radius_km' => 25,
                    'limit' => 5,
                    'user_intent' => 'exact_place_info',
                    'missing_fields' => [],
                    'budget' => null,
                    'requested_fields' => ['address'],
                    'target_names' => [],
                ]));

            $mock->shouldReceive('createText')
                ->once()
                ->withArgs(function (string $instructions, string $input): bool {
                    $payload = json_decode($input, true);

                    $this->assertSame('exact_lookup', data_get($payload, 'catalog_context.basis'));
                    $this->assertSame('AGUANTINTA', data_get($payload, 'catalog_context.locations.0.name'));

                    return true;
                })
                ->andReturn('La dirección exacta del segundo lugar, AGUANTINTA, es 25 104, MEXICO, 97125, Mérida, Yucatán, México.');
        });

        $response = app(TourismAgentChatService::class)->reply([
            'phone' => '+529991111111',
            'message' => 'me puedes dar la direccion exacta del segundo',
        ]);

        $this->assertSame('AGUANTINTA', data_get($response, 'catalog.locations.0.name'));
        $this->assertStringContainsString('AGUANTINTA', $response['reply']);
    }
}
