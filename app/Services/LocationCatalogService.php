<?php

namespace App\Services;

use App\Models\AgentConfig;
use App\Models\LocationPoint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocationCatalogService
{
    public function getLocations(array $filters = []): array
    {
        $locations = array_merge(
            $this->loadDatasetLocations(),
            $this->loadManualLocations()
        );

        $filtered = $this->applyFilters($locations, $filters);

        usort($filtered, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return $filtered;
    }

    public function getFilterOptions(array $locations): array
    {
        $categories = [];
        $cities = [];
        $tags = [];

        foreach ($locations as $location) {
            $category = trim((string) data_get($location, 'category', ''));
            $city = trim((string) data_get($location, 'city', ''));

            if ($category !== '') {
                $categories[$category] = true;
            }

            if ($city !== '') {
                $cities[$city] = true;
            }

            foreach ((array) data_get($location, 'tags', []) as $tag) {
                $tag = trim((string) $tag);
                if ($tag !== '') {
                    $tags[$tag] = true;
                }
            }
        }

        ksort($categories);
        ksort($cities);
        ksort($tags);

        return [
            'categories' => array_keys($categories),
            'cities' => array_keys($cities),
            'tags' => array_keys($tags),
        ];
    }

    private function loadDatasetLocations(): array
    {
        $locations = [];

        foreach ($this->datasetSources() as $datasetSource) {
            $storagePath = $datasetSource['storage_path'];
            $fileType = strtolower($datasetSource['file_type']);
            $sourceLabel = $datasetSource['source_label'];

            if ($fileType === 'csv') {
                $locations = array_merge($locations, $this->parseCsvDataset($storagePath, $sourceLabel));
                continue;
            }

            if ($fileType === 'json') {
                $locations = array_merge($locations, $this->parseJsonDataset($storagePath, $sourceLabel));
            }
        }

        return $locations;
    }

    private function loadManualLocations(): array
    {
        return LocationPoint::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (LocationPoint $point) {
                $raw = array_merge($point->metadata ?? [], [
                    'id' => $point->id,
                    'name' => $point->name,
                    'category' => $point->category,
                    'city' => $point->city,
                    'address' => $point->address,
                    'phone' => $point->phone,
                    'email' => $point->email,
                    'website' => $point->website,
                    'description' => $point->description,
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                ]);

                return [
                    'id' => 'manual-'.$point->id,
                    'name' => (string) $point->name,
                    'category' => (string) ($point->category ?? ''),
                    'city' => (string) ($point->city ?? ''),
                    'address' => (string) ($point->address ?? ''),
                    'phone' => (string) ($point->phone ?? ''),
                    'email' => (string) ($point->email ?? ''),
                    'website' => (string) ($point->website ?? ''),
                    'description' => (string) ($point->description ?? ''),
                    'lat' => (float) $point->lat,
                    'lng' => (float) $point->lng,
                    'tags' => $this->normalizeTags($point->tags ?? []),
                    'source' => (string) ($point->source ?? 'manual'),
                    'source_type' => 'manual',
                    'editable' => true,
                    'raw' => $raw,
                ];
            })
            ->all();
    }

    private function datasetSources(): array
    {
        $sources = [];
        $configDatasets = (array) data_get(AgentConfig::activeConfig(), 'datasets', []);

        foreach ($configDatasets as $dataset) {
            $storagePath = trim((string) data_get($dataset, 'storage_path', ''));
            $fileType = strtolower(trim((string) data_get($dataset, 'file_type', '')));
            $sourceLabel = (string) data_get($dataset, 'file_name', basename($storagePath));

            if ($storagePath === '' || ! in_array($fileType, ['csv', 'json'], true)) {
                continue;
            }

            if (! Storage::disk('public')->exists($storagePath)) {
                continue;
            }

            $sources[] = [
                'storage_path' => $storagePath,
                'file_type' => $fileType,
                'source_label' => $sourceLabel,
            ];
        }

        if (! empty($sources)) {
            return $sources;
        }

        $fallbackPath = 'agent-datasets/BD-Restaurantes.csv';

        if (Storage::disk('public')->exists($fallbackPath)) {
            $sources[] = [
                'storage_path' => $fallbackPath,
                'file_type' => 'csv',
                'source_label' => 'BD-Restaurantes.csv',
            ];
        }

        return $sources;
    }

    private function parseCsvDataset(string $storagePath, string $sourceLabel): array
    {
        $fileContent = Storage::disk('public')->get($storagePath);
        $lines = preg_split('/\r\n|\r|\n/', $fileContent);

        if (! is_array($lines) || count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv((string) array_shift($lines));
        $headers = array_map(fn ($header) => trim((string) $header), $headers);

        $locations = [];

        foreach ($lines as $index => $line) {
            if (! is_string($line) || trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line);

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            if (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }

            $assoc = array_combine($headers, $row);

            if (! is_array($assoc)) {
                continue;
            }

            $normalized = $this->normalizeLocation(
                $assoc,
                $sourceLabel,
                'dataset',
                sha1($sourceLabel.'|'.$index.'|'.(string) data_get($assoc, 'name', ''))
            );

            if ($normalized !== null) {
                $locations[] = $normalized;
            }
        }

        return $locations;
    }

    private function parseJsonDataset(string $storagePath, string $sourceLabel): array
    {
        $decoded = json_decode(Storage::disk('public')->get($storagePath), true);

        if (! is_array($decoded)) {
            return [];
        }

        $rows = array_is_list($decoded) ? $decoded : [$decoded];
        $locations = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = $this->normalizeLocation(
                $row,
                $sourceLabel,
                'dataset',
                sha1($sourceLabel.'|'.$index.'|'.(string) data_get($row, 'name', ''))
            );

            if ($normalized !== null) {
                $locations[] = $normalized;
            }
        }

        return $locations;
    }

    private function normalizeLocation(array $row, string $sourceLabel, string $sourceType, string $idSeed): ?array
    {
        $lat = $this->toFloat($this->pickValue($row, ['lat', 'latitude', 'Latitud']));
        $lng = $this->toFloat($this->pickValue($row, ['lng', 'lon', 'long', 'longitude', 'Longitud']));

        if ($lat === null || $lng === null) {
            return null;
        }

        $name = trim((string) $this->pickValue($row, ['name', 'Nombre Comercial', 'Nombre', 'title']));

        if ($name === '') {
            $name = 'Ubicacion sin nombre';
        }

        $category = trim((string) $this->pickValue($row, ['category', 'Categoria', 'Categoría', 'Actividad', 'Giro']));
        $city = trim((string) $this->pickValue($row, ['city', 'Ciudad', 'municipio', 'Municipio']));
        $address = trim((string) $this->pickValue($row, ['address', 'Direccion', 'Dirección', 'Calle']));
        $phone = trim((string) $this->pickValue($row, ['phone', 'telefono', 'Teléfono']));
        $email = trim((string) $this->pickValue($row, ['email', 'correo', 'Email']));
        $website = trim((string) $this->pickValue($row, ['website', 'url', 'Página web', 'Pagina web', 'web']));
        $description = trim((string) $this->pickValue($row, ['description', 'Descripcion', 'Descripción', 'Actividad']));

        $tagsFromRow = $this->pickValue($row, ['tags', 'tag', 'etiquetas']);
        $tags = $this->normalizeTags($tagsFromRow);

        if ($category !== '' && ! in_array($category, $tags, true)) {
            $tags[] = $category;
        }

        if ($city !== '' && ! in_array($city, $tags, true)) {
            $tags[] = $city;
        }

        return [
            'id' => 'dataset-'.substr(sha1($idSeed.'|'.$lat.'|'.$lng.'|'.$name), 0, 16),
            'name' => $name,
            'category' => $category,
            'city' => $city,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'description' => $description,
            'lat' => $lat,
            'lng' => $lng,
            'tags' => array_values(array_unique($tags)),
            'source' => trim((string) data_get($row, 'source', $sourceLabel)),
            'source_type' => $sourceType,
            'editable' => false,
            'raw' => $row,
        ];
    }

    private function applyFilters(array $locations, array $filters): array
    {
        $search = Str::lower(trim((string) data_get($filters, 'q', '')));
        $category = trim((string) data_get($filters, 'category', ''));
        $city = trim((string) data_get($filters, 'city', ''));
        $tag = Str::lower(trim((string) data_get($filters, 'tag', '')));
        $sourceType = trim((string) data_get($filters, 'source_type', ''));

        return array_values(array_filter($locations, function (array $location) use ($search, $category, $city, $tag, $sourceType) {
            if ($category !== '' && (string) data_get($location, 'category', '') !== $category) {
                return false;
            }

            if ($city !== '' && (string) data_get($location, 'city', '') !== $city) {
                return false;
            }

            if ($sourceType !== '' && (string) data_get($location, 'source_type', '') !== $sourceType) {
                return false;
            }

            $locationTags = array_map(fn ($item) => Str::lower((string) $item), (array) data_get($location, 'tags', []));

            if ($tag !== '' && ! in_array($tag, $locationTags, true)) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $searchable = [
                (string) data_get($location, 'name', ''),
                (string) data_get($location, 'category', ''),
                (string) data_get($location, 'city', ''),
                (string) data_get($location, 'address', ''),
                (string) data_get($location, 'description', ''),
                implode(' ', (array) data_get($location, 'tags', [])),
                implode(' ', array_map(fn ($value) => is_scalar($value) ? (string) $value : '', (array) data_get($location, 'raw', []))),
            ];

            return Str::contains(Str::lower(implode(' ', $searchable)), $search);
        }));
    }

    private function pickValue(array $row, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            foreach ($row as $key => $value) {
                if (Str::lower(trim((string) $key)) === Str::lower(trim($candidate))) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            return $this->sanitizeTagArray($tags);
        }

        $tagsString = trim((string) $tags);

        if ($tagsString === '') {
            return [];
        }

        $decodedJson = json_decode($tagsString, true);

        if (is_array($decodedJson)) {
            return $this->sanitizeTagArray($decodedJson);
        }

        $parts = preg_split('/[,|;]/', $tagsString);

        if (! is_array($parts)) {
            return [];
        }

        return $this->sanitizeTagArray($parts);
    }

    private function sanitizeTagArray(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $tag) {
            $value = trim((string) $tag, " \t\n\r\0\x0B\"'");

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
