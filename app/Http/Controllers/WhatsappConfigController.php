<?php

namespace App\Http\Controllers;

use App\Models\AgentConfig;
use App\Models\TourismUserChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhatsappConfigController extends Controller
{
    private const OUTBOUND_ROLES = ['assistant', 'system'];

    public function index(): View
    {
        $config = AgentConfig::activeConfig();

        return view('config.whatsapp', [
            'config' => $config,
            'stats' => $this->messageStats($config),
            'userMessageStats' => $this->userMessageStats(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $currentConfig = AgentConfig::activeConfig();
        $currentWhatsapp = (array) data_get($currentConfig, 'whatsapp', []);
        $incomingWhatsapp = (array) data_get($validated, 'whatsapp', []);

        $whatsapp = [
            'enabled' => $request->boolean('whatsapp.enabled'),
            'provider' => trim((string) data_get($incomingWhatsapp, 'provider', 'meta_cloud_api')),
            'api_base_url' => trim((string) data_get($incomingWhatsapp, 'api_base_url', 'https://graph.facebook.com')),
            'api_version' => trim((string) data_get($incomingWhatsapp, 'api_version', '')),
            'phone_number_id' => trim((string) data_get($incomingWhatsapp, 'phone_number_id', '')),
            'business_account_id' => trim((string) data_get($incomingWhatsapp, 'business_account_id', '')),
            'access_token' => $this->secretValue($incomingWhatsapp, $currentWhatsapp, 'access_token'),
            'webhook_verify_token' => $this->secretValue($incomingWhatsapp, $currentWhatsapp, 'webhook_verify_token'),
            'webhook_callback_url' => trim((string) data_get($incomingWhatsapp, 'webhook_callback_url', '')),
            'default_country_code' => trim((string) data_get($incomingWhatsapp, 'default_country_code', '52')),
            'message_unit_cost' => (float) data_get($incomingWhatsapp, 'message_unit_cost', 0),
            'currency_code' => strtoupper(trim((string) data_get($incomingWhatsapp, 'currency_code', 'MXN'))),
            'monthly_budget' => (float) data_get($incomingWhatsapp, 'monthly_budget', 0),
            'notes' => trim((string) data_get($incomingWhatsapp, 'notes', '')),
        ];

        AgentConfig::saveAsActive(array_replace_recursive($currentConfig, [
            'integrations' => [
                'whatsapp_enabled' => $whatsapp['enabled'],
                'webhook_url' => $whatsapp['webhook_callback_url'],
            ],
            'whatsapp' => $whatsapp,
        ]));

        return redirect()
            ->route('settings.whatsapp')
            ->with('status', 'whatsapp-config-saved');
    }

    private function messageStats(array $config): array
    {
        $unitCost = (float) data_get($config, 'whatsapp.message_unit_cost', 0);
        $currency = (string) data_get($config, 'whatsapp.currency_code', 'MXN');
        $monthlyBudget = (float) data_get($config, 'whatsapp.monthly_budget', 0);

        $totalMessages = $this->outboundMessagesQuery()->count();
        $todayMessages = $this->sentSince(now()->startOfDay())->count();
        $monthMessages = $this->sentSince(now()->startOfMonth())->count();
        $usersReached = $this->outboundMessagesQuery()
            ->distinct('tourism_user_id')
            ->count('tourism_user_id');
        $monthCost = $monthMessages * $unitCost;

        return [
            'total_messages' => $totalMessages,
            'today_messages' => $todayMessages,
            'month_messages' => $monthMessages,
            'users_reached' => $usersReached,
            'estimated_total_cost' => $totalMessages * $unitCost,
            'estimated_today_cost' => $todayMessages * $unitCost,
            'estimated_month_cost' => $monthCost,
            'monthly_budget' => $monthlyBudget,
            'monthly_budget_usage' => $monthlyBudget > 0 ? min(100, ($monthCost / $monthlyBudget) * 100) : 0,
            'unit_cost' => $unitCost,
            'currency' => $currency,
        ];
    }

    private function userMessageStats()
    {
        return TourismUserChatMessage::query()
            ->join('tourism_users', 'tourism_users.id', '=', 'tourism_user_chat_messages.tourism_user_id')
            ->whereIn('tourism_user_chat_messages.role', self::OUTBOUND_ROLES)
            ->select([
                'tourism_users.id',
                'tourism_users.phone',
                'tourism_users.name',
                'tourism_users.email',
                DB::raw('COUNT(*) as outbound_messages_count'),
                DB::raw('MAX(COALESCE(tourism_user_chat_messages.sent_at, tourism_user_chat_messages.created_at)) as last_outbound_at'),
            ])
            ->groupBy('tourism_users.id', 'tourism_users.phone', 'tourism_users.name', 'tourism_users.email')
            ->orderByDesc('outbound_messages_count')
            ->limit(20)
            ->get();
    }

    private function sentSince($date): Builder
    {
        return $this->outboundMessagesQuery()
            ->where(function (Builder $query) use ($date): void {
                $query->where('sent_at', '>=', $date)
                    ->orWhere(function (Builder $query) use ($date): void {
                        $query->whereNull('sent_at')
                            ->where('created_at', '>=', $date);
                    });
            });
    }

    private function outboundMessagesQuery(): Builder
    {
        return TourismUserChatMessage::query()
            ->whereIn('role', self::OUTBOUND_ROLES);
    }

    private function secretValue(array $incoming, array $current, string $key): string
    {
        $value = trim((string) data_get($incoming, $key, ''));

        return $value !== '' ? $value : (string) data_get($current, $key, '');
    }

    private function rules(): array
    {
        return [
            'whatsapp.enabled' => ['nullable', 'boolean'],
            'whatsapp.provider' => ['required', 'string', 'max:50'],
            'whatsapp.api_base_url' => ['nullable', 'url', 'max:255'],
            'whatsapp.api_version' => ['nullable', 'string', 'max:20'],
            'whatsapp.phone_number_id' => ['nullable', 'string', 'max:100'],
            'whatsapp.business_account_id' => ['nullable', 'string', 'max:100'],
            'whatsapp.access_token' => ['nullable', 'string', 'max:2000'],
            'whatsapp.webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'whatsapp.webhook_callback_url' => ['nullable', 'url', 'max:255'],
            'whatsapp.default_country_code' => ['nullable', 'string', 'max:8'],
            'whatsapp.message_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'whatsapp.currency_code' => ['required', 'string', 'size:3'],
            'whatsapp.monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'whatsapp.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
