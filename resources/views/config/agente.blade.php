@extends('layouts.app')

@section('title', 'Configuracion del Agente')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-5">
            <div>
                <h1 class="fw-bold mb-1">Configuracion del Agente</h1>
                <div class="text-muted">Administra los parametros principales del chatbot.</div>
            </div>
        </div>

        @if (session('status') === 'agent-config-saved')
            <div class="alert alert-success d-flex align-items-center p-5 mb-5">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-success">Configuracion guardada</h4>
                    <span>La configuracion del bot se actualizo correctamente.</span>
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

        <form method="POST" action="{{ route('settings.agent.store') }}">
            @csrf

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">1. General</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label for="general_name" class="form-label required">Nombre</label>
                            <input type="text" id="general_name" name="general[name]" class="form-control"
                                value="{{ old('general.name', data_get($config, 'general.name', '')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="general_purpose" class="form-label">Proposito</label>
                            <input type="text" id="general_purpose" name="general[purpose]" class="form-control"
                                value="{{ old('general.purpose', data_get($config, 'general.purpose', '')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="general_language" class="form-label">Idioma</label>
                            <select id="general_language" name="general[language]" class="form-select">
                                @php($language = old('general.language', data_get($config, 'general.language', 'es')))
                                <option value="es" @selected($language === 'es')>es</option>
                                <option value="en" @selected($language === 'en')>en</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="general_tone" class="form-label">Tono</label>
                            <select id="general_tone" name="general[tone]" class="form-select">
                                @php($tone = old('general.tone', data_get($config, 'general.tone', 'friendly')))
                                <option value="friendly" @selected($tone === 'friendly')>friendly</option>
                                <option value="professional" @selected($tone === 'professional')>professional</option>
                                <option value="formal" @selected($tone === 'formal')>formal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="general_welcome_message" class="form-label">Mensaje de bienvenida</label>
                            <textarea id="general_welcome_message" name="general[welcome_message]" class="form-control" rows="3">{{ old('general.welcome_message', data_get($config, 'general.welcome_message', '')) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="general_fallback_message" class="form-label">Mensaje fallback</label>
                            <textarea id="general_fallback_message" name="general[fallback_message]" class="form-control" rows="3">{{ old('general.fallback_message', data_get($config, 'general.fallback_message', '')) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="general_system_prompt" class="form-label">System prompt</label>
                            <textarea id="general_system_prompt" name="general[system_prompt]" class="form-control" rows="4">{{ old('general.system_prompt', data_get($config, 'general.system_prompt', '')) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">2. Modelo</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label for="model_model_name" class="form-label required">Model name</label>
                            <input type="text" id="model_model_name" name="model[model_name]" class="form-control"
                                value="{{ old('model.model_name', data_get($config, 'model.model_name', 'gpt-5')) }}"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label for="model_temperature" class="form-label required">Temperature (0 - 1)</label>
                            <input type="number" step="0.1" min="0" max="1" id="model_temperature"
                                name="model[temperature]" class="form-control"
                                value="{{ old('model.temperature', data_get($config, 'model.temperature', 0.7)) }}"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label for="model_max_tokens" class="form-label required">Max tokens</label>
                            <input type="number" min="1" id="model_max_tokens" name="model[max_tokens]"
                                class="form-control"
                                value="{{ old('model.max_tokens', data_get($config, 'model.max_tokens', 500)) }}"
                                required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">3. Memoria</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label d-block">Memoria habilitada</label>
                            <input type="hidden" name="memory[enabled]" value="0">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" id="memory_enabled"
                                    name="memory[enabled]"
                                    @checked((bool) old('memory.enabled', data_get($config, 'memory.enabled', true)))>
                                <label class="form-check-label" for="memory_enabled">Activar memoria conversacional</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="memory_max_messages" class="form-label">Max messages</label>
                            <input type="number" min="1" id="memory_max_messages" name="memory[max_messages]"
                                class="form-control"
                                value="{{ old('memory.max_messages', data_get($config, 'memory.max_messages', 15)) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">4. RAG</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label d-block">RAG habilitado</label>
                            <input type="hidden" name="rag[enabled]" value="0">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" id="rag_enabled"
                                    name="rag[enabled]"
                                    @checked((bool) old('rag.enabled', data_get($config, 'rag.enabled', false)))>
                                <label class="form-check-label" for="rag_enabled">Activar contexto RAG</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="rag_top_k" class="form-label">Top K</label>
                            <input type="number" min="1" id="rag_top_k" name="rag[top_k]" class="form-control"
                                value="{{ old('rag.top_k', data_get($config, 'rag.top_k', 3)) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">5. Comportamiento</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5 align-items-end">
                        <div class="col-md-6">
                            <label for="behavior_response_mode" class="form-label">Response mode</label>
                            @php($responseMode = old('behavior.response_mode', data_get($config, 'behavior.response_mode', 'medium')))
                            <select id="behavior_response_mode" name="behavior[response_mode]" class="form-select">
                                <option value="short" @selected($responseMode === 'short')>short</option>
                                <option value="medium" @selected($responseMode === 'medium')>medium</option>
                                <option value="long" @selected($responseMode === 'long')>long</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Human fallback</label>
                            <input type="hidden" name="behavior[human_fallback]" value="0">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" id="behavior_human_fallback"
                                    name="behavior[human_fallback]"
                                    @checked((bool) old('behavior.human_fallback', data_get($config, 'behavior.human_fallback', true)))>
                                <label class="form-check-label" for="behavior_human_fallback">Escalar a humano</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">6. Integraciones</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label d-block">WhatsApp</label>
                            <input type="hidden" name="integrations[whatsapp_enabled]" value="0">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" id="integrations_whatsapp_enabled"
                                    name="integrations[whatsapp_enabled]"
                                    @checked((bool) old('integrations.whatsapp_enabled', data_get($config, 'integrations.whatsapp_enabled', true)))>
                                <label class="form-check-label" for="integrations_whatsapp_enabled">Habilitar WhatsApp</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="integrations_webhook_url" class="form-label">Webhook URL</label>
                            <input type="url" id="integrations_webhook_url" name="integrations[webhook_url]"
                                class="form-control"
                                value="{{ old('integrations.webhook_url', data_get($config, 'integrations.webhook_url', '')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">7. Seguridad</h3>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label for="security_api_key" class="form-label">API Key</label>
                            <input type="text" id="security_api_key" name="security[api_key]" class="form-control"
                                value="{{ old('security.api_key', data_get($config, 'security.api_key', '')) }}">
                            <div class="form-text">Usada por el header <code>X-API-KEY</code> en la API.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-10">
                <button type="submit" class="btn btn-primary">
                    Guardar configuracion
                </button>
            </div>
        </form>
    </div>
@endsection
