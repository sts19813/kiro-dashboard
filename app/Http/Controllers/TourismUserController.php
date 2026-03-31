<?php

namespace App\Http\Controllers;

use App\Models\TourismUser;
use App\Services\TourismRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TourismUserController extends Controller
{
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'preferences' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = TourismUser::query()->updateOrCreate(
            ['phone' => (string) $validated['phone']],
            [
                'name' => data_get($validated, 'name'),
                'email' => data_get($validated, 'email'),
                'preferred_language' => data_get($validated, 'preferred_language'),
                'currency_code' => strtoupper((string) data_get($validated, 'currency_code', 'MXN')),
                'budget_min' => data_get($validated, 'budget_min'),
                'budget_max' => data_get($validated, 'budget_max'),
                'preferences' => data_get($validated, 'preferences', []),
                'is_active' => data_get($validated, 'is_active', true),
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function show(string $phone): JsonResponse
    {
        $user = $this->resolveUser($phone);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function storeLocation(string $phone, Request $request): JsonResponse
    {
        $user = $this->resolveUser($phone);
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'search_query' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'array'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $location = $user->locations()->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $location,
        ], 201);
    }

    public function locationHistory(string $phone): JsonResponse
    {
        $user = $this->resolveUser($phone);
        $history = $user->locations()
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->paginate(100);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    public function recommendations(string $phone, Request $request, TourismRecommendationService $service): JsonResponse
    {
        $user = $this->resolveUser($phone);

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'q' => ['nullable', 'string', 'max:255'],
            'radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'track_location' => ['nullable', 'boolean'],
        ]);

        if ((bool) data_get($validated, 'track_location', true)) {
            $user->locations()->create([
                'lat' => (float) $validated['lat'],
                'lng' => (float) $validated['lng'],
                'budget' => data_get($validated, 'budget'),
                'search_query' => data_get($validated, 'q'),
                'context' => ['source' => 'recommendation_request'],
                'recorded_at' => now(),
            ]);
        }

        $recommendations = $service->recommend(
            (float) $validated['lat'],
            (float) $validated['lng'],
            data_get($validated, 'budget') !== null ? (float) $validated['budget'] : null,
            data_get($validated, 'q'),
            (float) data_get($validated, 'radius_km', 10),
            (int) data_get($validated, 'limit', 10)
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'recommendations' => $recommendations,
            ],
        ]);
    }

    public function storeChatMessage(string $phone, Request $request): JsonResponse
    {
        $user = $this->resolveUser($phone);

        $validated = $request->validate([
            'role' => ['required', 'in:user,assistant,system'],
            'message' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'sent_at' => ['nullable', 'date'],
        ]);

        $message = $user->chatMessages()->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ], 201);
    }

    public function chatHistory(string $phone): JsonResponse
    {
        $user = $this->resolveUser($phone);
        $messages = $user->chatMessages()
            ->orderBy('sent_at')
            ->orderBy('created_at')
            ->paginate(200);

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    private function resolveUser(string $phone): TourismUser
    {
        return TourismUser::query()->where('phone', $phone)->firstOrFail();
    }
}
