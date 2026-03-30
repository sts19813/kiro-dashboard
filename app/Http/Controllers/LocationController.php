<?php

namespace App\Http\Controllers;

use App\Models\LocationPoint;
use App\Services\LocationCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('mapa.index', [
            'googleMapsApiKey' => (string) config('services.google_maps.api_key', ''),
            'googleMapsMapId' => (string) config('services.google_maps.map_id', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LocationPoint::query()->create($this->validatedPointData($request));

        return redirect()
            ->route('locations.index')
            ->with('status', 'location-created');
    }

    public function destroy(LocationPoint $locationPoint): RedirectResponse
    {
        $locationPoint->delete();

        return redirect()
            ->route('locations.index')
            ->with('status', 'location-deleted');
    }

    public function apiIndex(Request $request, LocationCatalogService $catalog): JsonResponse
    {
        $filters = $request->only(['q', 'category', 'city', 'tag', 'source_type']);
        $allLocations = $catalog->getLocations();
        $locations = $catalog->getLocations($filters);

        return response()->json([
            'status' => 'success',
            'count' => count($locations),
            'filters' => $catalog->getFilterOptions($allLocations),
            'data' => $locations,
        ]);
    }

    public function apiPoints(Request $request, LocationCatalogService $catalog): JsonResponse
    {
        $filters = $request->only(['q', 'category', 'city', 'tag', 'source_type']);

        return response()->json($catalog->getLocations($filters));
    }

    public function apiStore(Request $request): JsonResponse
    {
        $point = LocationPoint::query()->create($this->validatedPointData($request));

        return response()->json([
            'status' => 'success',
            'message' => 'Point created successfully',
            'data' => $point,
        ], 201);
    }

    public function apiDestroy(LocationPoint $locationPoint): JsonResponse
    {
        $locationPoint->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Point deleted successfully',
        ]);
    }

    private function validatedPointData(Request $request): array
    {
        $validated = $request->validate($this->rules());

        return [
            'name' => (string) data_get($validated, 'name'),
            'category' => $this->nullableString(data_get($validated, 'category')),
            'city' => $this->nullableString(data_get($validated, 'city')),
            'address' => $this->nullableString(data_get($validated, 'address')),
            'phone' => $this->nullableString(data_get($validated, 'phone')),
            'email' => $this->nullableString(data_get($validated, 'email')),
            'website' => $this->nullableString(data_get($validated, 'website')),
            'description' => $this->nullableString(data_get($validated, 'description')),
            'lat' => (float) data_get($validated, 'lat'),
            'lng' => (float) data_get($validated, 'lng'),
            'tags' => $this->normalizeTags(data_get($validated, 'tags')),
            'metadata' => $this->normalizeMetadata(data_get($validated, 'metadata')),
            'source' => (string) data_get($validated, 'source', 'manual'),
            'is_active' => true,
        ];
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'tags' => ['nullable'],
            'metadata' => ['nullable'],
            'source' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function normalizeTags(mixed $value): array
    {
        if (is_array($value)) {
            return $this->sanitizeTagItems($value);
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return [];
        }

        $decoded = json_decode($stringValue, true);

        if (is_array($decoded)) {
            return $this->sanitizeTagItems($decoded);
        }

        $parts = preg_split('/[,|;]/', $stringValue);

        if (! is_array($parts)) {
            return [];
        }

        return $this->sanitizeTagItems($parts);
    }

    private function sanitizeTagItems(array $tags): array
    {
        $cleanTags = [];

        foreach ($tags as $tag) {
            $normalized = trim((string) $tag);
            if ($normalized !== '') {
                $cleanTags[] = $normalized;
            }
        }

        return array_values(array_unique($cleanTags));
    }

    private function normalizeMetadata(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return [];
        }

        $decoded = json_decode($stringValue, true);

        return is_array($decoded) ? $decoded : ['note' => $stringValue];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
