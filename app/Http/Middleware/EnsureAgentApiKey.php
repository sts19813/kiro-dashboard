<?php

namespace App\Http\Middleware;

use App\Models\AgentConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedApiKey = (string) data_get(AgentConfig::activeConfig(), 'security.api_key', '');
        $providedApiKey = (string) $request->header('X-API-KEY', '');

        if (! hash_equals($expectedApiKey, $providedApiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
