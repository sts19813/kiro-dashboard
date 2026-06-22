@extends('layouts.app')

@section('title', 'Configuracion de WhatsApp')

@section('content')
    @php
        $whatsapp = data_get($config, 'whatsapp', []);
        $currency = data_get($stats, 'currency', data_get($whatsapp, 'currency_code', 'MXN'));
        $unitCost = (float) data_get($stats, 'unit_cost', 0);
    @endphp

    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Configuracion de WhatsApp</h1>
                <div class="text-muted">Administra API, credenciales, webhooks y costo estimado de mensajes salientes.</div>
            </div>
            <a href="{{ route('settings.agent') }}" class="btn btn-light">
                Volver al agente
            </a>
        </div>

        @if (session('status') === 'whatsapp-config-saved')
            <div class="alert alert-success d-flex align-items-center p-5 mb-5">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-success">Configuracion guardada</h4>
                    <span>La configuracion de WhatsApp se actualizo correctamente.</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-5">
                <h4 class="mb-2">Revisa los siguientes errores:</h4>
                <ul class="mb-0 ps-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-5 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Mensajes salientes</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format((int) data_get($stats, 'total_messages', 0)) }}</div>
                        <div class="text-muted fs-7">Total enviado por el sistema</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Costo estimado total</div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            ${{ number_format((float) data_get($stats, 'estimated_total_cost', 0), 2) }}
                        </div>
                        <div class="text-muted fs-7">{{ $currency }} a ${{ number_format($unitCost, 4) }} por mensaje</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Este mes</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format((int) data_get($stats, 'month_messages', 0)) }}</div>
                        <div class="text-muted fs-7">
                            ${{ number_format((float) data_get($stats, 'estimated_month_cost', 0), 2) }} {{ $currency }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-2">Usuarios alcanzados</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format((int) data_get($stats, 'users_reached', 0)) }}</div>
                        <div class="text-muted fs-7">{{ number_format((int) data_get($stats, 'today_messages', 0)) }} mensajes hoy</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('settings.whatsapp.store') }}">
                    @csrf

                    <div class="card card-flush mb-5">
                        <div class="card-header">
                            <h3 class="card-title">API de WhatsApp</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-5 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label d-block">Canal activo</label>
                                    <input type="hidden" name="whatsapp[enabled]" value="0">
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="whatsapp_enabled"
                                            name="whatsapp[enabled]"
                                            @checked((bool) old('whatsapp.enabled', data_get($whatsapp, 'enabled', true)))>
                                        <label class="form-check-label" for="whatsapp_enabled">Habilitar WhatsApp</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_provider" class="form-label required">Proveedor</label>
                                    @php($provider = old('whatsapp.provider', data_get($whatsapp, 'provider', 'meta_cloud_api')))
                                    <select id="whatsapp_provider" name="whatsapp[provider]" class="form-select" required>
                                        <option value="meta_cloud_api" @selected($provider === 'meta_cloud_api')>Meta Cloud API</option>
                                        <option value="custom_gateway" @selected($provider === 'custom_gateway')>Gateway propio</option>
                                        <option value="other" @selected($provider === 'other')>Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_api_base_url" class="form-label">API base URL</label>
                                    <input type="url" id="whatsapp_api_base_url" name="whatsapp[api_base_url]"
                                        class="form-control"
                                        value="{{ old('whatsapp.api_base_url', data_get($whatsapp, 'api_base_url', 'https://graph.facebook.com')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_api_version" class="form-label">Version API</label>
                                    <input type="text" id="whatsapp_api_version" name="whatsapp[api_version]"
                                        class="form-control" placeholder="Ej. v20.0"
                                        value="{{ old('whatsapp.api_version', data_get($whatsapp, 'api_version', '')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_phone_number_id" class="form-label">Phone number ID</label>
                                    <input type="text" id="whatsapp_phone_number_id" name="whatsapp[phone_number_id]"
                                        class="form-control"
                                        value="{{ old('whatsapp.phone_number_id', data_get($whatsapp, 'phone_number_id', '')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_business_account_id" class="form-label">Business account ID</label>
                                    <input type="text" id="whatsapp_business_account_id" name="whatsapp[business_account_id]"
                                        class="form-control"
                                        value="{{ old('whatsapp.business_account_id', data_get($whatsapp, 'business_account_id', '')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_default_country_code" class="form-label">Codigo pais default</label>
                                    <input type="text" id="whatsapp_default_country_code" name="whatsapp[default_country_code]"
                                        class="form-control"
                                        value="{{ old('whatsapp.default_country_code', data_get($whatsapp, 'default_country_code', '52')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Credenciales y webhook</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label for="whatsapp_access_token" class="form-label">Access token</label>
                                    <input type="password" id="whatsapp_access_token" name="whatsapp[access_token]"
                                        class="form-control" autocomplete="new-password"
                                        placeholder="{{ data_get($whatsapp, 'access_token') ? 'Token guardado; escribe uno nuevo para reemplazarlo' : 'Pega el token de acceso' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp_webhook_verify_token" class="form-label">Webhook verify token</label>
                                    <input type="password" id="whatsapp_webhook_verify_token"
                                        name="whatsapp[webhook_verify_token]" class="form-control" autocomplete="new-password"
                                        placeholder="{{ data_get($whatsapp, 'webhook_verify_token') ? 'Token guardado; escribe uno nuevo para reemplazarlo' : 'Token para validar webhook' }}">
                                </div>
                                <div class="col-12">
                                    <label for="whatsapp_webhook_callback_url" class="form-label">Webhook callback URL</label>
                                    <input type="url" id="whatsapp_webhook_callback_url"
                                        name="whatsapp[webhook_callback_url]" class="form-control"
                                        value="{{ old('whatsapp.webhook_callback_url', data_get($whatsapp, 'webhook_callback_url', data_get($config, 'integrations.webhook_url', ''))) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Costos</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-5">
                                <div class="col-md-4">
                                    <label for="whatsapp_currency_code" class="form-label required">Moneda</label>
                                    <input type="text" id="whatsapp_currency_code" name="whatsapp[currency_code]"
                                        maxlength="3" class="form-control text-uppercase"
                                        value="{{ old('whatsapp.currency_code', data_get($whatsapp, 'currency_code', 'MXN')) }}"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <label for="whatsapp_message_unit_cost" class="form-label">Costo por mensaje saliente</label>
                                    <input type="number" min="0" step="0.0001" id="whatsapp_message_unit_cost"
                                        name="whatsapp[message_unit_cost]" class="form-control"
                                        value="{{ old('whatsapp.message_unit_cost', data_get($whatsapp, 'message_unit_cost', 0)) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="whatsapp_monthly_budget" class="form-label">Presupuesto mensual</label>
                                    <input type="number" min="0" step="0.01" id="whatsapp_monthly_budget"
                                        name="whatsapp[monthly_budget]" class="form-control"
                                        value="{{ old('whatsapp.monthly_budget', data_get($whatsapp, 'monthly_budget', 0)) }}">
                                </div>
                                <div class="col-12">
                                    <label for="whatsapp_notes" class="form-label">Notas internas</label>
                                    <textarea id="whatsapp_notes" name="whatsapp[notes]" class="form-control" rows="3">{{ old('whatsapp.notes', data_get($whatsapp, 'notes', '')) }}</textarea>
                                </div>
                            </div>

                            @if ((float) data_get($stats, 'monthly_budget', 0) > 0)
                                <div class="separator my-6"></div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-semibold text-gray-700">Uso estimado del presupuesto mensual</span>
                                    <span class="text-muted">
                                        ${{ number_format((float) data_get($stats, 'estimated_month_cost', 0), 2) }}
                                        / ${{ number_format((float) data_get($stats, 'monthly_budget', 0), 2) }} {{ $currency }}
                                    </span>
                                </div>
                                <div class="progress h-8px">
                                    <div class="progress-bar bg-primary"
                                        style="width: {{ number_format((float) data_get($stats, 'monthly_budget_usage', 0), 2, '.', '') }}%">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-10">
                        <button type="submit" class="btn btn-primary">
                            Guardar configuracion
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-xl-5">
                <div class="card card-flush">
                    <div class="card-header">
                        <h3 class="card-title">Mensajes por usuario</h3>
                    </div>
                    <div class="card-body pt-0">
                        @if ($userMessageStats->count())
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-4">
                                    <thead>
                                        <tr class="text-start text-gray-700 fw-bold fs-7 text-uppercase gs-0">
                                            <th>Usuario</th>
                                            <th class="text-end">Mensajes</th>
                                            <th class="text-end">Costo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @foreach ($userMessageStats as $row)
                                            @php($rowCost = ((int) $row->outbound_messages_count) * $unitCost)
                                            <tr>
                                                <td>
                                                    <div class="text-gray-900 fw-bold">{{ $row->name ?: 'Sin nombre' }}</div>
                                                    <div class="text-muted fs-7">{{ $row->phone }}</div>
                                                    @if ($row->last_outbound_at)
                                                        <div class="text-muted fs-8">Ultimo: {{ \Carbon\Carbon::parse($row->last_outbound_at)->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end">{{ number_format((int) $row->outbound_messages_count) }}</td>
                                                <td class="text-end">${{ number_format($rowCost, 2) }} {{ $currency }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-10">
                                Aun no hay mensajes salientes registrados para estimar costos.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
