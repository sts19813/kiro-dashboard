<?php

namespace App\Services;

class TourismRecommendationService
{
    public function __construct(private readonly LocationCatalogService $catalog)
    {
    }

    public function recommend(float $lat, float $lng, ?float $budget = null, ?string $query = null, float $radiusKm = 10, int $limit = 10): array
    {
        $filters = [];

        if ($query !== null && trim($query) !== '') {
            $filters['q'] = trim($query);
        }

        $locations = $this->catalog->getLocations($filters);
        $scored = [];

        foreach ($locations as $location) {
            $distanceKm = $this->distanceKm($lat, $lng, (float) data_get($location, 'lat', 0), (float) data_get($location, 'lng', 0));

            if ($distanceKm > $radiusKm) {
                continue;
            }

            $budgetScore = $this->budgetScore($budget, (array) data_get($location, 'tags', []));
            $distanceScore = max(0, 1 - ($distanceKm / max($radiusKm, 0.1)));

            $score = round(($distanceScore * 0.7) + ($budgetScore * 0.3), 4);

            $scored[] = [
                ...$location,
                'distance_km' => round($distanceKm, 3),
                'score' => $score,
                'budget_match' => $budgetScore >= 0.5,
            ];
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
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
