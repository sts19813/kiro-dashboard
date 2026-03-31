<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_verified_user_can_open_providers_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/provedores');

        $response->assertOk();
    }

    public function test_legacy_location_routes_redirect_to_provedores(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/ubicaciones')->assertRedirect('/provedores');
        $this->actingAs($user)->get('/mapa')->assertRedirect('/provedores');
        $this->actingAs($user)->get('/proveedores')->assertRedirect('/provedores');
    }
}
