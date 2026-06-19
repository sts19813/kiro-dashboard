<?php

namespace Tests\Feature;

use App\Models\LocationPoint;
use App\Services\TourismRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TourismRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_query_prefers_real_lodging_over_broad_giro_matches(): void
    {
        Cache::flush();

        LocationPoint::query()->create([
            'name' => 'RESTAURANTE CERCANO',
            'category' => 'restaurante',
            'city' => 'Mérida',
            'address' => '60 100, CENTRO, 97000, Mérida, Yucatán, México',
            'description' => 'Restaurante cercano',
            'lat' => 20.982,
            'lng' => -89.620,
            'tags' => ['comida', 'restaurante', 'Mérida'],
            'metadata' => ['Giro' => 'Hotelería y Restaurantes'],
            'source' => 'test',
            'is_active' => true,
        ]);

        LocationPoint::query()->create([
            'name' => 'HOTEL CON DIRECCION',
            'category' => 'hotel',
            'city' => 'Mérida',
            'address' => '62 200, CENTRO, 97000, Mérida, Yucatán, México',
            'description' => 'Hotel con hospedaje',
            'lat' => 20.986,
            'lng' => -89.624,
            'tags' => ['hospedaje', 'hotel', 'Mérida'],
            'source' => 'test',
            'is_active' => true,
        ]);

        $results = app(TourismRecommendationService::class)
            ->recommend(20.982, -89.620, 1000, 'hotel', 10, 5);

        $this->assertNotEmpty($results);
        $this->assertSame('HOTEL CON DIRECCION', data_get($results, '0.name'));
        $this->assertNotContains('RESTAURANTE CERCANO', collect($results)->pluck('name')->all());
    }

    public function test_multi_word_queries_match_terms_instead_of_exact_phrase(): void
    {
        Cache::flush();

        LocationPoint::query()->create([
            'name' => 'RESTAURANTE CENTRO',
            'category' => 'restaurante',
            'city' => 'Mérida',
            'address' => '60 100, CENTRO, 97000, Mérida, Yucatán, México',
            'description' => 'Restaurante en centro',
            'lat' => 20.982,
            'lng' => -89.620,
            'tags' => ['comida', 'restaurante', 'Mérida'],
            'source' => 'test',
            'is_active' => true,
        ]);

        LocationPoint::query()->create([
            'name' => 'RESTAURANTE NORTE',
            'category' => 'restaurante',
            'city' => 'Mérida',
            'address' => '30 200, MONTEBELLO, 97113, Mérida, Yucatán, México',
            'description' => 'Restaurante en norte',
            'lat' => 21.030,
            'lng' => -89.600,
            'tags' => ['comida', 'restaurante', 'Mérida'],
            'source' => 'test',
            'is_active' => true,
        ]);

        $results = app(TourismRecommendationService::class)
            ->recommend(20.982, -89.620, 3500, 'restaurante centro', 20, 5);

        $this->assertNotEmpty($results);
        $this->assertSame('RESTAURANTE CENTRO', data_get($results, '0.name'));
    }
}
