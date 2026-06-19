<?php

namespace App\Services;

use App\Models\AgentConfig;
use App\Models\TourismUser;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TourismAgentChatService
{
    public function __construct(
        private readonly OpenAiResponseClient $openAi,
        private readonly TourismRecommendationService $recommendations,
        private readonly LocationCatalogService $catalog,
    ) {}

    public function reply(array $payload): array
    {
        $config = AgentConfig::activeConfig();
        $user = $this->resolveUser($payload);
        $location = $this->resolveLocation($user, $payload);
        $budget = $this->resolveBudget($user, $payload, $location);
        $message = trim((string) data_get($payload, 'message'));

        $userMessage = $user->chatMessages()->create([
            'role' => 'user',
            'message' => $message,
            'metadata' => [
                'source' => (string) data_get($payload, 'source', 'agent_playground'),
                'lat' => data_get($location, 'lat'),
                'lng' => data_get($location, 'lng'),
                'budget' => $budget,
            ],
            'sent_at' => now(),
        ]);

        $history = $this->recentHistory($user, (int) data_get($config, 'memory.max_messages', 15));
        $plan = $this->buildPlan($message, $history, $user, $location, $budget, $config);
        $catalogContext = $this->catalogContext($plan, $location, $budget, $config);
        $answer = $this->generateAnswer($message, $history, $user, $location, $budget, $plan, $catalogContext, $config);

        $assistantMessage = $user->chatMessages()->create([
            'role' => 'assistant',
            'message' => $answer,
            'metadata' => [
                'source' => (string) data_get($payload, 'source', 'agent_playground'),
                'plan' => $plan,
                'catalog' => [
                    'consulted' => (bool) data_get($catalogContext, 'consulted', false),
                    'basis' => (string) data_get($catalogContext, 'basis', 'none'),
                    'count' => count((array) data_get($catalogContext, 'locations', [])),
                ],
                'user_message_id' => $userMessage->id,
            ],
            'sent_at' => now(),
        ]);

        return [
            'user' => $user->fresh(),
            'message' => $assistantMessage,
            'reply' => $answer,
            'plan' => $plan,
            'catalog' => $catalogContext,
        ];
    }

    private function resolveUser(array $payload): TourismUser
    {
        $phone = trim((string) data_get($payload, 'phone'));

        if ($phone === '') {
            throw new RuntimeException('El telefono del usuario es obligatorio.');
        }

        $user = TourismUser::query()->firstOrNew(['phone' => $phone]);

        if (! $user->exists) {
            $user->currency_code = 'MXN';
            $user->is_active = true;
            $user->preferences = [];
        }

        $name = trim((string) data_get($payload, 'name', ''));

        if ($name !== '') {
            $user->name = $name;
        }

        $user->save();

        return $user;
    }

    private function resolveLocation(TourismUser $user, array $payload): ?array
    {
        $lat = data_get($payload, 'lat');
        $lng = data_get($payload, 'lng');

        if ($lat !== null && $lng !== null) {
            $location = $user->locations()->create([
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'accuracy_meters' => data_get($payload, 'accuracy_meters'),
                'budget' => data_get($payload, 'budget'),
                'search_query' => data_get($payload, 'message'),
                'context' => [
                    'source' => (string) data_get($payload, 'source', 'agent_playground'),
                    'location_source' => data_get($payload, 'location_source'),
                    'location_label' => data_get($payload, 'location_label'),
                ],
                'recorded_at' => now(),
            ]);

            return [
                'lat' => (float) $location->lat,
                'lng' => (float) $location->lng,
                'budget' => $location->budget !== null ? (float) $location->budget : null,
                'accuracy_meters' => $location->accuracy_meters !== null ? (float) $location->accuracy_meters : null,
                'label' => data_get($location->context, 'location_label'),
                'recorded_at' => $location->recorded_at?->toISOString(),
                'source' => data_get($location->context, 'location_source', 'current_message'),
            ];
        }

        $latest = $user->locations()
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $latest) {
            return null;
        }

        return [
            'lat' => (float) $latest->lat,
            'lng' => (float) $latest->lng,
            'budget' => $latest->budget !== null ? (float) $latest->budget : null,
            'accuracy_meters' => $latest->accuracy_meters !== null ? (float) $latest->accuracy_meters : null,
            'label' => data_get($latest->context, 'location_label'),
            'recorded_at' => $latest->recorded_at?->toISOString(),
            'source' => data_get($latest->context, 'location_source', 'history'),
        ];
    }

    private function resolveBudget(TourismUser $user, array $payload, ?array $location): ?float
    {
        $budget = data_get($payload, 'budget');

        if ($budget !== null && $budget !== '') {
            return (float) $budget;
        }

        $latestBudget = data_get($location, 'budget');

        if ($latestBudget !== null && $latestBudget !== '') {
            return (float) $latestBudget;
        }

        return $user->budget_max !== null ? (float) $user->budget_max : null;
    }

    private function recentHistory(TourismUser $user, int $limit): array
    {
        $safeLimit = max(1, min($limit, 30));

        return $user->chatMessages()
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit($safeLimit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message) => [
                'role' => $message->role,
                'message' => $message->message,
                'sent_at' => $message->sent_at?->toISOString(),
            ])
            ->all();
    }

    private function buildPlan(string $message, array $history, TourismUser $user, ?array $location, ?float $budget, array $config): array
    {
        $heuristic = $this->heuristicPlan($message, $location);

        if (! (bool) data_get($heuristic, 'needs_recommendations', false)) {
            return $heuristic;
        }

        try {
            $raw = $this->openAi->createText(
                $this->plannerInstructions(),
                $this->plannerInput($message, $history, $user, $location, $budget),
                [
                    'model' => (string) data_get($config, 'model.model_name', 'gpt-5'),
                    'temperature' => 0.1,
                    'max_output_tokens' => 260,
                    'text' => ['format' => ['type' => 'json_object']],
                    'timeout' => 30,
                ]
            );

            return $this->normalizePlan($raw, $heuristic);
        } catch (Throwable) {
            return $heuristic;
        }
    }

    private function plannerInstructions(): string
    {
        return implode("\n", [
            'Eres un clasificador de intencion para un agente turistico de Yucatan.',
            'Responde SOLO un objeto JSON valido.',
            'Decide si hace falta consultar una base de datos de lugares. Consultala solo si el usuario pide recomendaciones, lugares cercanos, restaurantes, actividades, rutas, hoteles, cenotes, playas, museos, bares, cafes, compras o un itinerario con lugares concretos.',
            'No consultes la base para saludos, aclaraciones, conversacion general, cambios de tono o preguntas que puedan responderse sin lugares concretos.',
            'Campos requeridos: needs_recommendations boolean, search_query string|null, radius_km number, limit number, user_intent string, missing_fields array, budget number|null.',
            'search_query debe ser breve: categoria, ciudad, necesidad o tipo de lugar; nunca copies todo el mensaje si no hace falta.',
        ]);
    }

    private function plannerInput(string $message, array $history, TourismUser $user, ?array $location, ?float $budget): string
    {
        return json_encode([
            'current_message' => $message,
            'recent_history' => array_slice($history, -8),
            'user' => [
                'phone' => $user->phone,
                'name' => $user->name,
                'preferences' => $user->preferences ?? [],
            ],
            'known_location' => $location,
            'known_budget' => $budget,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function normalizePlan(string $raw, array $fallback): array
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded) && preg_match('/\{.*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);
        }

        if (! is_array($decoded)) {
            return $fallback;
        }

        $needsRecommendations = (bool) data_get($decoded, 'needs_recommendations', false);
        $searchQuery = trim((string) data_get($decoded, 'search_query', ''));

        return [
            'needs_recommendations' => $needsRecommendations || (bool) data_get($fallback, 'needs_recommendations', false),
            'search_query' => $searchQuery !== '' ? Str::limit($searchQuery, 120, '') : data_get($fallback, 'search_query'),
            'radius_km' => max(0.5, min((float) data_get($decoded, 'radius_km', data_get($fallback, 'radius_km', 10)), 100)),
            'limit' => max(1, min((int) data_get($decoded, 'limit', data_get($fallback, 'limit', 5)), 10)),
            'user_intent' => Str::limit((string) data_get($decoded, 'user_intent', data_get($fallback, 'user_intent', 'chat')), 120, ''),
            'missing_fields' => array_values((array) data_get($decoded, 'missing_fields', [])),
            'budget' => data_get($decoded, 'budget'),
        ];
    }

    private function heuristicPlan(string $message, ?array $location): array
    {
        $lower = Str::lower($message);
        $category = $this->categoryFromMessage($lower);
        $needsRecommendations = Str::contains($lower, [
            'recomienda', 'recomendacion', 'recomendación', 'cerca', 'cercano', 'lugar', 'lugares',
            'restaurante', 'comer', 'cenote', 'playa', 'museo', 'hotel', 'bar', 'cafe', 'café',
            'hacienda', 'pueblo', 'itinerario', 'tour', 'actividad', 'visitar', 'que hago', 'qué hago',
            'sugerencia', 'opciones', 'plan', 'familia', 'romantico', 'romántico', 'niños', 'ninos',
        ]);

        return [
            'needs_recommendations' => $needsRecommendations,
            'search_query' => $category ?: ($needsRecommendations ? Str::limit($message, 120, '') : null),
            'radius_km' => $location ? 10 : 25,
            'limit' => 5,
            'user_intent' => $needsRecommendations ? 'recommend_places' : 'chat',
            'missing_fields' => $needsRecommendations && ! $location ? ['ubicacion'] : [],
            'budget' => null,
        ];
    }

    private function categoryFromMessage(string $message): ?string
    {
        $categories = [
            'restaurante' => ['restaurante', 'comer', 'cena', 'desayuno', 'almuerzo', 'comida'],
            'cenote' => ['cenote', 'nadar'],
            'playa' => ['playa', 'mar'],
            'museo' => ['museo', 'historia', 'cultura'],
            'hotel' => ['hotel', 'hospedaje', 'dormir'],
            'bar' => ['bar', 'tragos', 'coctel', 'cóctel'],
            'cafe' => ['cafe', 'café', 'cafeteria', 'cafetería'],
            'hacienda' => ['hacienda'],
            'tour' => ['tour', 'actividad', 'excursion', 'excursión'],
        ];

        foreach ($categories as $category => $needles) {
            if (Str::contains($message, $needles)) {
                return $category;
            }
        }

        return null;
    }

    private function catalogContext(array $plan, ?array $location, ?float $budget, array $config): array
    {
        if (! (bool) data_get($plan, 'needs_recommendations', false)) {
            return [
                'consulted' => false,
                'basis' => 'not_needed',
                'locations' => [],
            ];
        }

        $limit = min((int) data_get($plan, 'limit', data_get($config, 'rag.top_k', 5)), 8);
        $query = data_get($plan, 'search_query');

        if ($location) {
            $locations = $this->recommendations->recommend(
                (float) data_get($location, 'lat'),
                (float) data_get($location, 'lng'),
                $budget,
                $query,
                (float) data_get($plan, 'radius_km', 10),
                $limit
            );

            if (empty($locations) && $query) {
                $locations = $this->recommendations->recommend(
                    (float) data_get($location, 'lat'),
                    (float) data_get($location, 'lng'),
                    $budget,
                    null,
                    min((float) data_get($plan, 'radius_km', 10) * 2, 100),
                    $limit
                );
            }

            return [
                'consulted' => true,
                'basis' => 'nearby',
                'query' => $query,
                'locations' => $this->compactLocations($locations),
            ];
        }

        if ($query) {
            return [
                'consulted' => true,
                'basis' => 'text_search',
                'query' => $query,
                'locations' => $this->compactLocations($this->textSearch((string) $query, $limit)),
            ];
        }

        return [
            'consulted' => false,
            'basis' => 'missing_location',
            'locations' => [],
        ];
    }

    private function textSearch(string $query, int $limit): array
    {
        $terms = collect(preg_split('/\s+/', Str::lower($query)) ?: [])
            ->map(fn ($term) => trim($term, " \t\n\r\0\x0B.,;:!?()[]{}\"'"))
            ->filter(fn ($term) => mb_strlen($term) >= 4)
            ->unique()
            ->values()
            ->all();

        if (empty($terms)) {
            return [];
        }

        $scored = [];

        foreach ($this->catalog->getLocations() as $location) {
            $blob = Str::lower(implode(' ', [
                data_get($location, 'name'),
                data_get($location, 'category'),
                data_get($location, 'city'),
                data_get($location, 'address'),
                implode(' ', (array) data_get($location, 'tags', [])),
            ]));

            $score = 0;

            foreach ($terms as $term) {
                if (Str::contains($blob, $term)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scored[] = ['score' => $score, 'location' => $location];
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_map(fn (array $item) => $item['location'], array_slice($scored, 0, $limit));
    }

    private function compactLocations(array $locations): array
    {
        return array_map(fn (array $location) => [
            'id' => (string) data_get($location, 'id', ''),
            'name' => (string) data_get($location, 'name', ''),
            'category' => (string) data_get($location, 'category', ''),
            'city' => (string) data_get($location, 'city', ''),
            'address' => (string) data_get($location, 'address', ''),
            'phone' => (string) data_get($location, 'phone', ''),
            'website' => (string) data_get($location, 'website', ''),
            'distance_km' => data_get($location, 'distance_km'),
            'score' => data_get($location, 'score'),
            'tags' => array_slice(array_values((array) data_get($location, 'tags', [])), 0, 6),
        ], $locations);
    }

    private function generateAnswer(
        string $message,
        array $history,
        TourismUser $user,
        ?array $location,
        ?float $budget,
        array $plan,
        array $catalogContext,
        array $config
    ): string {
        return $this->openAi->createText(
            $this->answerInstructions($config),
            $this->answerInput($message, $history, $user, $location, $budget, $plan, $catalogContext),
            [
                'model' => (string) data_get($config, 'model.model_name', 'gpt-5'),
                'temperature' => (float) data_get($config, 'model.temperature', 0.7),
                'max_output_tokens' => (int) data_get($config, 'model.max_tokens', 500),
                'timeout' => 45,
            ]
        );
    }

    private function answerInstructions(array $config): string
    {
        $customPrompt = trim((string) data_get($config, 'general.system_prompt', ''));
        $tone = (string) data_get($config, 'general.tone', 'friendly');
        $responseMode = (string) data_get($config, 'behavior.response_mode', 'medium');

        return implode("\n", array_filter([
            'Eres un asistente IA experto en turismo de Yucatan. Tu trabajo es entender al usuario, recordar el contexto reciente y recomendar con criterio.',
            'Responde en espanol claro, natural y util. Formato ideal para WhatsApp: breve, directo y con 1 pregunta de seguimiento si falta informacion importante.',
            'No inventes lugares, telefonos, sitios web, precios, horarios ni distancias. Si se consulto catalogo, basa las recomendaciones concretas solo en esos lugares.',
            'Si el usuario pide lugares segun su ubicacion y no hay ubicacion conocida, pide que comparta ubicacion o indique municipio/zona.',
            'Si hay ubicacion y lugares candidatos, prioriza cercania, necesidad del usuario, presupuesto y variedad. Explica por que recomiendas cada lugar en una frase.',
            'Si la pregunta no requiere base de datos, conversa normalmente como guia turistico de Yucatan.',
            "Tono configurado: {$tone}. Extension configurada: {$responseMode}.",
            $customPrompt !== '' ? "Instrucciones adicionales del administrador:\n{$customPrompt}" : null,
        ]));
    }

    private function answerInput(
        string $message,
        array $history,
        TourismUser $user,
        ?array $location,
        ?float $budget,
        array $plan,
        array $catalogContext
    ): string {
        return json_encode([
            'user' => [
                'phone' => $user->phone,
                'name' => $user->name,
                'preferred_language' => $user->preferred_language,
                'currency_code' => $user->currency_code,
                'budget_min' => $user->budget_min,
                'budget_max' => $user->budget_max,
                'preferences' => $user->preferences ?? [],
            ],
            'known_location' => $location,
            'known_budget' => $budget,
            'recent_history' => $history,
            'decision_plan' => $plan,
            'catalog_context' => $catalogContext,
            'current_message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
