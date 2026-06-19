<?php

namespace App\Services;

use Illuminate\Support\Str;

class TourismRecommendationService
{
    public function __construct(private readonly LocationCatalogService $catalog) {}

    public function recommend(float $lat, float $lng, ?float $budget = null, ?string $query = null, float $radiusKm = 10, int $limit = 10): array
    {
        $locations = $this->catalog->getLocations();
        $profile = $this->queryProfile($query);
        $scored = [];

        foreach ($locations as $location) {
            $distanceKm = $this->distanceKm($lat, $lng, (float) data_get($location, 'lat', 0), (float) data_get($location, 'lng', 0));

            if ($distanceKm > $radiusKm) {
                continue;
            }

            $queryScore = $this->queryScore($location, $profile);

            if ($queryScore <= 0) {
                continue;
            }

            $budgetScore = $this->budgetScore($budget, (array) data_get($location, 'tags', []));
            $distanceScore = max(0, 1 - ($distanceKm / max($radiusKm, 0.1)));

            $score = round(($distanceScore * 0.45) + ($queryScore * 0.4) + ($budgetScore * 0.15), 4);

            $scored[] = [
                ...$location,
                'distance_km' => round($distanceKm, 3),
                'score' => $score,
                'budget_match' => $budgetScore >= 0.5,
                'query_match' => round($queryScore, 4),
            ];
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    private function queryProfile(?string $query): array
    {
        $normalized = $this->normalizeText((string) $query);
        $tokens = collect(preg_split('/\s+/', $normalized) ?: [])
            ->filter()
            ->values();

        $aliases = [
            'hotel' => ['hotel', 'hoteles', 'hospedaje', 'hospedar', 'hospede', 'hospedo', 'hostal', 'hostales', 'motel', 'moteles', 'cabana', 'cabanas', 'posada', 'huesped', 'huespedes', 'dormir'],
            'restaurante' => ['restaurante', 'restaurantes', 'comer', 'comida', 'cena', 'cenar', 'almuerzo', 'desayuno'],
            'cafeteria' => ['cafe', 'cafeteria', 'cafeterias', 'coffee'],
            'bar' => ['bar', 'bares', 'cantina', 'cantinas', 'tragos', 'coctel', 'cocteles', 'cerveza'],
            'cenote' => ['cenote', 'cenotes'],
            'playa' => ['playa', 'playas'],
            'museo' => ['museo', 'museos'],
        ];

        $category = null;
        $categoryAliasTokens = [];

        foreach ($aliases as $candidate => $needles) {
            if ($tokens->contains(fn ($token) => in_array($token, $needles, true))) {
                $category = $candidate;
                $categoryAliasTokens = $needles;

                break;
            }
        }

        $stopwords = [
            'a', 'al', 'algo', 'algun', 'alguna', 'algunas', 'algunos', 'bien', 'busca', 'buscar',
            'cerca', 'cercano', 'con', 'de', 'del', 'el', 'en', 'esta', 'este', 'la', 'las', 'lo',
            'los', 'me', 'mi', 'mil', 'mxn', 'noche', 'opcion', 'opciones', 'para', 'pesos',
            'por', 'presupuesto', 'que', 'recomienda', 'recomendacion', 'si', 'un', 'una',
        ];

        $terms = $tokens
            ->reject(fn ($token) => in_array($token, $stopwords, true))
            ->reject(fn ($token) => in_array($token, $categoryAliasTokens, true))
            ->filter(fn ($token) => mb_strlen($token) >= 3)
            ->unique()
            ->values()
            ->all();

        return [
            'has_query' => $normalized !== '',
            'category' => $category,
            'terms' => $terms,
        ];
    }

    private function queryScore(array $location, array $profile): float
    {
        if (! (bool) data_get($profile, 'has_query', false)) {
            return 1.0;
        }

        $categoryScore = $this->categoryScore($location, (string) data_get($profile, 'category', ''));

        if (data_get($profile, 'category') && $categoryScore <= 0) {
            return 0.0;
        }

        $text = $this->locationText($location);
        $terms = (array) data_get($profile, 'terms', []);
        $termScore = 0.0;

        if (! empty($terms)) {
            $matches = 0;

            foreach ($terms as $term) {
                if (Str::contains($text, $term)) {
                    $matches++;
                }
            }

            $termScore = $matches / count($terms);
        }

        if (data_get($profile, 'category')) {
            return min(1.0, ($categoryScore * 0.8) + ($termScore * 0.2));
        }

        return $termScore;
    }

    private function categoryScore(array $location, string $category): float
    {
        if ($category === '') {
            return 0.0;
        }

        $locationCategory = $this->normalizeText((string) data_get($location, 'category', ''));
        $tags = $this->normalizeText(implode(' ', (array) data_get($location, 'tags', [])));
        $name = $this->normalizeText((string) data_get($location, 'name', ''));

        return match ($category) {
            'hotel' => $locationCategory === 'hotel' || Str::contains($tags, 'hospedaje')
                ? 1.0
                : (Str::contains($name, ['hotel', 'hostal', 'motel', 'posada', 'huesped']) ? 0.8 : 0.0),
            'restaurante' => in_array($locationCategory, ['restaurante', 'comida rapida'], true) || Str::contains($tags, ['restaurante', 'comida'])
                ? 1.0
                : 0.0,
            'cafeteria' => $locationCategory === 'cafeteria' || Str::contains($tags, ['cafe', 'desayuno'])
                ? 1.0
                : 0.0,
            'bar' => $locationCategory === 'bar' || Str::contains($tags, ['bar', 'cantina', 'vida nocturna'])
                ? 1.0
                : 0.0,
            default => $locationCategory === $category || Str::contains($tags, $category) ? 1.0 : 0.0,
        };
    }

    private function locationText(array $location): string
    {
        return $this->normalizeText(implode(' ', [
            data_get($location, 'name'),
            data_get($location, 'category'),
            data_get($location, 'city'),
            data_get($location, 'address'),
            implode(' ', (array) data_get($location, 'tags', [])),
        ]));
    }

    private function normalizeText(string $value): string
    {
        $normalized = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized) ?: '';

        return trim(preg_replace('/\s+/', ' ', $normalized) ?: '');
    }

    private function budgetScore(?float $budget, array $tags): float
    {
        if ($budget === null) {
            return 1.0;
        }

        $normalizedTags = array_map(fn ($tag) => strtolower((string) $tag), $tags);
        $tagString = implode(' ', $normalizedTags);

        if (str_contains($tagString, '$$$$') && $budget < 1200) {
            return 0.0;
        }

        if (str_contains($tagString, '$$$') && $budget < 800) {
            return 0.2;
        }

        if (str_contains($tagString, '$$') && $budget < 400) {
            return 0.5;
        }

        if (str_contains($tagString, '$') && $budget < 150) {
            return 0.7;
        }

        return 1.0;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadiusKm;
    }
}
