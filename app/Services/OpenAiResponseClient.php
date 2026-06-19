<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OpenAiResponseClient
{
    private const REASONING_MODEL_MIN_OUTPUT_TOKENS = 4096;

    private const MAX_OUTPUT_TOKEN_RETRY_LIMIT = 12000;

    public function createText(string $instructions, string $input, array $options = []): string
    {
        $apiKey = trim((string) config('services.openai.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY en tu archivo .env para usar el chat IA.');
        }

        $model = (string) data_get($options, 'model', 'gpt-5');
        $payload = array_filter([
            'model' => $model,
            'instructions' => $instructions,
            'input' => $input,
            'temperature' => data_get($options, 'temperature'),
            'max_output_tokens' => $this->resolvedMaxOutputTokens($model, data_get($options, 'max_output_tokens')),
            'reasoning' => data_get($options, 'reasoning', $this->defaultReasoning($model)),
            'text' => data_get($options, 'text'),
            'store' => false,
        ], fn ($value) => $value !== null && $value !== '');

        $requestId = (string) str()->uuid();
        $timeout = (int) data_get($options, 'timeout', 45);

        $this->logRequest($requestId, $payload);

        $response = $this->postResponse($apiKey, $payload, $timeout);
        $this->logResponse($requestId, $response);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $message = (string) data_get($response->json(), 'error.message', 'No se pudo obtener respuesta de OpenAI.');

            if ($response->failed()) {
                $unsupportedParameter = $this->unsupportedOptionalParameter($message, $payload);

                if ($unsupportedParameter !== null) {
                    unset($payload[$unsupportedParameter]);

                    $stage = 'retry_without_'.$unsupportedParameter;
                    $this->logRequest($requestId, $payload, $stage);
                    $response = $this->postResponse($apiKey, $payload, $timeout);
                    $this->logResponse($requestId, $response, $stage);

                    continue;
                }

                throw new RuntimeException($message);
            }

            $responsePayload = (array) $response->json();
            $outputText = $this->extractOutputText($responsePayload);

            if ($outputText !== null) {
                return $outputText;
            }

            if ($this->isMaxOutputTokensIncomplete($responsePayload) && $this->canRaiseMaxOutputTokens($payload)) {
                $payload['max_output_tokens'] = $this->nextMaxOutputTokens((int) data_get($payload, 'max_output_tokens', 0));

                $stage = 'retry_max_output_tokens_'.$payload['max_output_tokens'];
                $this->logRequest($requestId, $payload, $stage);
                $response = $this->postResponse($apiKey, $payload, max($timeout, 90));
                $this->logResponse($requestId, $response, $stage);

                continue;
            }

            $this->throwNoUsableText($responsePayload, $requestId);
        }

        $this->throwNoUsableText((array) $response->json(), $requestId);
    }

    private function postResponse(string $apiKey, array $payload, int $timeout): Response
    {
        try {
            return Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/responses', $payload);
        } catch (Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con OpenAI: '.$exception->getMessage());
        }
    }

    private function logRequest(string $requestId, array $payload, string $stage = 'initial'): void
    {
        Log::debug('OpenAI request', [
            'request_id' => $requestId,
            'stage' => $stage,
            'payload' => $payload,
        ]);
    }

    private function logResponse(string $requestId, Response $response, string $stage = 'initial'): void
    {
        Log::info('OpenAI response', [
            'request_id' => $requestId,
            'stage' => $stage,
            'http_status' => $response->status(),
            'json' => $response->json(),
            'body' => $response->json() === null ? $response->body() : null,
        ]);
    }

    private function unsupportedOptionalParameter(string $message, array $payload): ?string
    {
        foreach (['temperature', 'reasoning'] as $parameter) {
            if (array_key_exists($parameter, $payload) && $this->isUnsupportedParameterError($message, $parameter)) {
                return $parameter;
            }
        }

        return null;
    }

    private function isUnsupportedParameterError(string $message, string $parameter): bool
    {
        return str_contains($message, "Unsupported parameter: '{$parameter}'")
            || str_contains($message, "Unsupported parameter: \"{$parameter}\"")
            || str_contains($message, "Unsupported parameter: {$parameter}");
    }

    private function resolvedMaxOutputTokens(string $model, mixed $configuredTokens): int
    {
        $tokens = (int) ($configuredTokens ?: 0);

        if ($this->isReasoningModel($model)) {
            return max($tokens, self::REASONING_MODEL_MIN_OUTPUT_TOKENS);
        }

        return $tokens > 0 ? $tokens : 1000;
    }

    private function defaultReasoning(string $model): ?array
    {
        if (! $this->isReasoningModel($model)) {
            return null;
        }

        return ['effort' => 'minimal'];
    }

    private function isReasoningModel(string $model): bool
    {
        $normalized = strtolower($model);

        return str_starts_with($normalized, 'gpt-5')
            || str_starts_with($normalized, 'o1')
            || str_starts_with($normalized, 'o3')
            || str_starts_with($normalized, 'o4');
    }

    private function isMaxOutputTokensIncomplete(array $payload): bool
    {
        return data_get($payload, 'status') === 'incomplete'
            && data_get($payload, 'incomplete_details.reason') === 'max_output_tokens';
    }

    private function canRaiseMaxOutputTokens(array $payload): bool
    {
        return (int) data_get($payload, 'max_output_tokens', 0) < self::MAX_OUTPUT_TOKEN_RETRY_LIMIT;
    }

    private function nextMaxOutputTokens(int $current): int
    {
        if ($current <= 0) {
            return self::REASONING_MODEL_MIN_OUTPUT_TOKENS;
        }

        return min(max($current * 2, self::REASONING_MODEL_MIN_OUTPUT_TOKENS), self::MAX_OUTPUT_TOKEN_RETRY_LIMIT);
    }

    private function extractOutputText(array $payload): ?string
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

        if ($text !== '') {
            return $text;
        }

        return null;
    }

    private function throwNoUsableText(array $payload, string $requestId): never
    {
        Log::warning('OpenAI response without usable text', [
            'request_id' => $requestId,
            'id' => data_get($payload, 'id'),
            'status' => data_get($payload, 'status'),
            'incomplete_details' => data_get($payload, 'incomplete_details'),
            'usage' => data_get($payload, 'usage'),
            'full_response' => $payload,
        ]);

        $status = (string) data_get($payload, 'status', 'sin status');
        $reason = (string) data_get($payload, 'incomplete_details.reason', '');
        $suffix = $reason !== '' ? " Status: {$status}. Motivo: {$reason}." : " Status: {$status}.";

        throw new RuntimeException("OpenAI respondió sin texto utilizable. Revisa storage/logs/laravel.log con request_id {$requestId}.{$suffix}");
    }
}
