<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponseClient
{
    public function createText(string $instructions, string $input, array $options = []): string
    {
        $apiKey = trim((string) config('services.openai.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY en tu archivo .env para usar el chat IA.');
        }

        $payload = array_filter([
            'model' => (string) data_get($options, 'model', 'gpt-5'),
            'instructions' => $instructions,
            'input' => $input,
            'temperature' => data_get($options, 'temperature'),
            'max_output_tokens' => data_get($options, 'max_output_tokens'),
            'text' => data_get($options, 'text'),
            'store' => false,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->postResponse($apiKey, $payload, (int) data_get($options, 'timeout', 45));

        if ($response->failed()) {
            $message = (string) data_get($response->json(), 'error.message', 'No se pudo obtener respuesta de OpenAI.');

            if ($this->isUnsupportedParameterError($message, 'temperature') && array_key_exists('temperature', $payload)) {
                unset($payload['temperature']);
                $response = $this->postResponse($apiKey, $payload, (int) data_get($options, 'timeout', 45));

                if (! $response->failed()) {
                    return $this->extractOutputText((array) $response->json());
                }

                $message = (string) data_get($response->json(), 'error.message', $message);
            }

            throw new RuntimeException($message);
        }

        return $this->extractOutputText((array) $response->json());
    }

    private function postResponse(string $apiKey, array $payload, int $timeout)
    {
        try {
            return Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/responses', $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con OpenAI: '.$exception->getMessage());
        }
    }

    private function isUnsupportedParameterError(string $message, string $parameter): bool
    {
        return str_contains($message, "Unsupported parameter: '{$parameter}'")
            || str_contains($message, "Unsupported parameter: \"{$parameter}\"")
            || str_contains($message, "Unsupported parameter: {$parameter}");
    }

    private function extractOutputText(array $payload): string
    {
        $outputText = trim((string) data_get($payload, 'output_text', ''));

        if ($outputText !== '') {
            return $outputText;
        }

        $parts = [];

        foreach ((array) data_get($payload, 'output', []) as $item) {
            foreach ((array) data_get($item, 'content', []) as $content) {
                $text = (string) data_get($content, 'text', '');

                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        $text = trim(implode("\n", $parts));

        if ($text === '') {
            throw new RuntimeException('OpenAI respondió sin texto utilizable.');
        }

        return $text;
    }
}
