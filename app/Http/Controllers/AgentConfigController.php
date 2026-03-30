<?php

namespace App\Http\Controllers;

use App\Models\AgentConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgentConfigController extends Controller
{
    public function index(): View
    {
        return view('config.agente', [
            'config' => AgentConfig::activeConfig(),
        ]);
    }

    public function points()
    {
        $path = 'agent-datasets/BD-Restaurantes.csv';

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([]);
        }

        $file = Storage::disk('public')->get($path);
        $lines = explode("\n", $file);

        $headers = str_getcsv(array_shift($lines));

        $data = [];

        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;

            $row = array_combine($headers, str_getcsv($line));

            if (!isset($row['lat']) || !isset($row['lng']))
                continue;

            $data[] = [
                'name' => $row['name'],
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
                'category' => $row['category'],
                'price' => (int) $row['price_level'],
            ];
        }

        return response()->json($data);
    }

    public function store(Request $request): RedirectResponse
    {
        AgentConfig::saveAsActive($this->validatedConfig($request));

        return redirect()
            ->route('settings.agent')
            ->with('status', 'agent-config-saved');
    }

    public function apiShow(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => AgentConfig::activeConfig(),
        ]);
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        AgentConfig::saveAsActive($this->validatedConfig($request));

        return response()->json([
            'status' => 'success',
            'data' => AgentConfig::activeConfig(),
        ]);
    }

    private function validatedConfig(Request $request): array
    {
        $validated = $request->validate($this->rules());
        $activeConfig = AgentConfig::activeConfig();

        return AgentConfig::sanitizeConfig([
            'general' => [
                'name' => data_get($validated, 'general.name', ''),
                'purpose' => data_get($validated, 'general.purpose', ''),
                'language' => data_get($validated, 'general.language', 'es'),
                'tone' => data_get($validated, 'general.tone', 'friendly'),
                'welcome_message' => data_get($validated, 'general.welcome_message', ''),
                'fallback_message' => data_get($validated, 'general.fallback_message', ''),
                'system_prompt' => data_get($validated, 'general.system_prompt', ''),
            ],
            'model' => [
                'model_name' => data_get($validated, 'model.model_name', 'gpt-5'),
                'temperature' => data_get($validated, 'model.temperature', 0.7),
                'max_tokens' => data_get($validated, 'model.max_tokens', 500),
            ],
            'memory' => [
                'enabled' => $request->boolean('memory.enabled', true),
                'max_messages' => data_get($validated, 'memory.max_messages', 15),
            ],
            'rag' => [
                'enabled' => $request->boolean('rag.enabled', false),
                'top_k' => data_get($validated, 'rag.top_k', 3),
            ],
            'behavior' => [
                'response_mode' => data_get($validated, 'behavior.response_mode', 'medium'),
                'human_fallback' => $request->boolean('behavior.human_fallback', true),
            ],
            'integrations' => [
                'whatsapp_enabled' => $request->boolean('integrations.whatsapp_enabled', true),
                'webhook_url' => data_get($validated, 'integrations.webhook_url', ''),
            ],
            'datasets' => $this->resolvedDatasets(
                $request,
                data_get($activeConfig, 'datasets', [])
            ),
            'security' => [
                'api_key' => data_get($validated, 'security.api_key', ''),
            ],
        ]);
    }

    private function rules(): array
    {
        return [
            'general.name' => ['required', 'string', 'max:255'],
            'general.purpose' => ['nullable', 'string'],
            'general.language' => ['nullable', 'string', 'max:10'],
            'general.tone' => ['nullable', 'string', 'max:50'],
            'general.welcome_message' => ['nullable', 'string'],
            'general.fallback_message' => ['nullable', 'string'],
            'general.system_prompt' => ['nullable', 'string'],

            'model.model_name' => ['required', 'string', 'max:100'],
            'model.temperature' => ['required', 'numeric', 'between:0,1'],
            'model.max_tokens' => ['required', 'numeric', 'min:1'],

            'memory.enabled' => ['nullable', 'boolean'],
            'memory.max_messages' => ['nullable', 'integer', 'min:1'],

            'rag.enabled' => ['nullable', 'boolean'],
            'rag.top_k' => ['nullable', 'integer', 'min:1'],

            'behavior.response_mode' => ['nullable', 'in:short,medium,long'],
            'behavior.human_fallback' => ['nullable', 'boolean'],

            'integrations.whatsapp_enabled' => ['nullable', 'boolean'],
            'integrations.webhook_url' => ['nullable', 'url'],

            'datasets' => ['nullable', 'array'],
            'datasets.remove_ids' => ['nullable', 'array'],
            'datasets.remove_ids.*' => ['string'],
            'datasets.files' => ['nullable', 'array'],
            'datasets.files.*' => ['file', 'mimes:csv,json', 'max:20480'],

            'security.api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function resolvedDatasets(Request $request, array $currentDatasets): array
    {
        $removeIds = collect((array) $request->input('datasets.remove_ids', []))
            ->filter(fn($id) => is_scalar($id) && (string) $id !== '')
            ->map(fn($id) => (string) $id)
            ->values();

        $currentCollection = collect($currentDatasets)
            ->filter(fn($dataset) => is_array($dataset));

        $this->deleteRemovedDatasets($currentCollection, $removeIds);

        $keptDatasets = $currentCollection
            ->reject(fn(array $dataset) => $removeIds->contains((string) data_get($dataset, 'id')))
            ->values();

        foreach ((array) $request->file('datasets.files', []) as $datasetFile) {
            if (!$datasetFile) {
                continue;
            }

            $storagePath = $datasetFile->store('agent-datasets', 'public');

            $keptDatasets->push([
                'id' => (string) Str::uuid(),
                'file_name' => (string) $datasetFile->getClientOriginalName(),
                'file_type' => strtolower((string) $datasetFile->getClientOriginalExtension()),
                'file_size' => (int) $datasetFile->getSize(),
                'url' => url(Storage::disk('public')->url($storagePath)),
                'uploaded_at' => now()->toISOString(),
                'storage_path' => $storagePath,
            ]);
        }

        return $keptDatasets->values()->all();
    }

    private function deleteRemovedDatasets(Collection $currentDatasets, Collection $removeIds): void
    {
        if ($removeIds->isEmpty()) {
            return;
        }

        $datasetsToDelete = $currentDatasets
            ->filter(fn(array $dataset) => $removeIds->contains((string) data_get($dataset, 'id')))
            ->values();

        foreach ($datasetsToDelete as $dataset) {
            $storagePath = (string) data_get($dataset, 'storage_path', '');

            if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }
        }
    }
}
