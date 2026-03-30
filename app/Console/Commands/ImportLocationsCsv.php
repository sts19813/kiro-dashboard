<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ImportLocationsCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:import-csv
        {--path= : Ruta absoluta o relativa del CSV}
        {--append : Si se envia, no vacia la tabla antes de importar}
        {--batch=500 : Tamano de lote para insercion masiva}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa ubicaciones desde CSV hacia location_points';

    public function handle(): int
    {
        if (! Schema::hasTable('location_points')) {
            $this->error('La tabla location_points no existe. Ejecuta primero: php artisan migrate');

            return self::FAILURE;
        }

        $csvPathOption = trim((string) $this->option('path'));
        $csvPath = $csvPathOption !== ''
            ? $this->resolvePath($csvPathOption)
            : storage_path('app/public/agent-datasets/BD-Restaurantes.csv');

        if (! is_file($csvPath)) {
            $this->error("CSV no encontrado: {$csvPath}");

            return self::FAILURE;
        }

        $batchSize = (int) $this->option('batch');

        if ($batchSize < 1) {
            $batchSize = 500;
        }

        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('No se pudo abrir el archivo CSV.');

            return self::FAILURE;
        }

        $headers = fgetcsv($handle);

        if (! is_array($headers) || count($headers) === 0) {
            fclose($handle);
            $this->error('CSV sin encabezados validos.');

            return self::FAILURE;
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0] ?? '');

        $appendMode = (bool) $this->option('append');
        $sourceDefault = basename($csvPath);

        $total = 0;
        $inserted = 0;
        $skipped = 0;
        $batch = [];
        $now = now();

        try {
            if (! $appendMode) {
                DB::table('location_points')->truncate();
            }

            while (($rowData = fgetcsv($handle)) !== false) {
                $total++;

                if (count($rowData) === 1 && trim((string) $rowData[0]) === '') {
                    continue;
                }

                if (count($rowData) < count($headers)) {
                    $rowData = array_pad($rowData, count($headers), '');
                }

                if (count($rowData) > count($headers)) {
                    $rowData = array_slice($rowData, 0, count($headers));
                }

                $row = array_combine($headers, $rowData);

                if (! is_array($row)) {
                    $skipped++;
                    continue;
                }

                $lat = $this->toFloat($this->pick($row, ['lat', 'latitude', 'Latitud']));
                $lng = $this->toFloat($this->pick($row, ['lng', 'lon', 'long', 'longitude', 'Longitud']));

                if ($lat === null || $lng === null) {
                    $skipped++;
                    continue;
                }

                $name = trim((string) $this->pick($row, ['name', 'Nombre Comercial', 'Nombre']));

                if ($name === '') {
                    $name = 'Ubicacion sin nombre';
                }

                $category = trim((string) $this->pick($row, ['category', 'Categoria', 'Categoría', 'Actividad', 'Giro']));
                $city = trim((string) $this->pick($row, ['city', 'Ciudad', 'municipio', 'Municipio']));
                $address = trim((string) $this->pick($row, ['address', 'Direccion', 'Dirección', 'Calle']));
                $phone = trim((string) $this->pick($row, ['phone', 'telefono', 'Teléfono']));
                $email = trim((string) $this->pick($row, ['email', 'correo', 'Email']));
                $website = trim((string) $this->pick($row, ['website', 'url', 'Página web', 'Pagina web', 'web']));
                $description = trim((string) $this->pick($row, ['description', 'Descripcion', 'Descripción', 'Actividad']));

                $tags = $this->normalizeTags($this->pick($row, ['tags', 'tag', 'etiquetas']));

                if ($category !== '' && ! in_array($category, $tags, true)) {
                    $tags[] = $category;
                }

                if ($city !== '' && ! in_array($city, $tags, true)) {
                    $tags[] = $city;
                }

                $batch[] = [
                    'name' => $name,
                    'category' => $category !== '' ? $category : null,
                    'city' => $city !== '' ? $city : null,
                    'address' => $address !== '' ? $address : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'email' => $email !== '' ? $email : null,
                    'website' => $website !== '' ? $website : null,
                    'description' => $description !== '' ? $description : null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'tags' => $this->encodeJson(array_values(array_unique($tags))),
                    'metadata' => $this->encodeJson($row),
                    'source' => trim((string) ($row['source'] ?? $sourceDefault)),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('location_points')->insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                DB::table('location_points')->insert($batch);
                $inserted += count($batch);
            }
        } catch (Throwable $e) {
            fclose($handle);
            $this->error('La importacion fallo: '.$e->getMessage());

            return self::FAILURE;
        }

        fclose($handle);

        $this->newLine();
        $this->info('Importacion completada.');
        $this->line("CSV: {$csvPath}");
        $this->line("Filas leidas: {$total}");
        $this->line("Filas insertadas: {$inserted}");
        $this->line("Filas descartadas: {$skipped}");
        $this->line('Total actual en location_points: '.DB::table('location_points')->count());

        return self::SUCCESS;
    }

    private function pick(array $row, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            foreach ($row as $key => $value) {
                if (mb_strtolower(trim((string) $key)) === mb_strtolower(trim((string) $candidate))) {
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

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $stringValue);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
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

    private function sanitizeTagItems(array $items): array
    {
        $tags = [];

        foreach ($items as $item) {
            $tag = trim((string) $item, " \t\n\r\0\x0B\"'");

            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function encodeJson(array $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return is_string($encoded) ? $encoded : '[]';
    }
}