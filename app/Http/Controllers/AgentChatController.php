<?php

namespace App\Http\Controllers;

use App\Models\TourismUser;
use App\Services\TourismAgentChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AgentChatController extends Controller
{
    public function index(): View
    {
        return view('config.agente-chat');
    }

    public function send(Request $request, TourismAgentChatService $chat): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'location_source' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            return response()->json([
                'status' => 'success',
                'data' => $chat->reply([...$validated, 'source' => 'agent_playground']),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $user = TourismUser::query()
            ->where('phone', (string) $validated['phone'])
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => null,
                    'messages' => [],
                ],
            ]);
        }

        $messages = $user->chatMessages()
            ->orderBy('sent_at')
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'messages' => $messages,
            ],
        ]);
    }
}
