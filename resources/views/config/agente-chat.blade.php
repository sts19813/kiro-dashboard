@extends('layouts.app')

@section('title', 'Chat de prueba del Agente')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-5">
            <div>
                <h1 class="fw-bold mb-1">Chat de prueba del Agente</h1>
                <div class="text-muted">Simula una conversacion tipo WhatsApp con memoria, usuario y ubicacion.</div>
            </div>
            <div class="d-flex gap-3">
                <a href="{{ route('settings.agent') }}" class="btn btn-light">Volver a configuracion</a>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Usuario de prueba</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-5">
                            <label for="agent_chat_phone" class="form-label required">Telefono / WhatsApp</label>
                            <input type="text" id="agent_chat_phone" class="form-control" value="+529990000000"
                                maxlength="30">
                            <div class="form-text">Este valor identifica al usuario y carga su historial.</div>
                        </div>

                        <div class="mb-5">
                            <label for="agent_chat_name" class="form-label">Nombre</label>
                            <input type="text" id="agent_chat_name" class="form-control" value="Usuario demo"
                                maxlength="150">
                        </div>

                        <div class="separator my-6"></div>

                        <div class="mb-5">
                            <label class="form-label">Ubicacion</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <input type="number" step="0.000001" id="agent_chat_lat" class="form-control"
                                        placeholder="Lat">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.000001" id="agent_chat_lng" class="form-control"
                                        placeholder="Lng">
                                </div>
                            </div>
                            <button type="button" id="agent_chat_location_btn" class="btn btn-sm btn-light-primary mt-3">
                                Usar mi ubicacion
                            </button>
                            <div id="agent_chat_location_status" class="form-text mt-3">
                                La ubicacion precisa requiere permiso del navegador y HTTPS o localhost.
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="agent_chat_budget" class="form-label">Presupuesto opcional</label>
                            <input type="number" min="0" step="1" id="agent_chat_budget" class="form-control"
                                placeholder="Ej. 500">
                            <div class="form-text">Se usa para recomendaciones cuando aplique.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Conversacion</h3>
                        <div class="card-toolbar">
                            <button type="button" id="agent_chat_reload_btn" class="btn btn-sm btn-light">
                                Recargar historial
                            </button>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column p-0" style="min-height: 620px;">
                        <div id="agent_chat_messages" class="scroll-y px-6 py-5 flex-grow-1"
                            style="max-height: 560px;">
                            <div class="text-center text-muted py-20" id="agent_chat_empty">
                                Escribe un mensaje para iniciar la prueba.
                            </div>
                        </div>
                        <div class="border-top p-5">
                            <form id="agent_chat_form" class="d-flex gap-3 align-items-end">
                                <div class="flex-grow-1">
                                    <label for="agent_chat_message" class="form-label">Mensaje</label>
                                    <textarea id="agent_chat_message" class="form-control" rows="2"
                                        placeholder="Ej. Estoy en Merida centro, recomiendame restaurantes cercanos para cenar"></textarea>
                                </div>
                                <button type="submit" id="agent_chat_send_btn" class="btn btn-primary">
                                    Enviar
                                </button>
                            </form>
                            <div class="form-text mt-3">
                                El agente consulta la base solo cuando detecta que necesita recomendar lugares concretos.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const endpoints = {
                send: @json(route('settings.agent.chat.send')),
                history: @json(route('settings.agent.chat.history')),
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const messagesEl = document.getElementById('agent_chat_messages');
            const emptyEl = document.getElementById('agent_chat_empty');
            const form = document.getElementById('agent_chat_form');
            const sendBtn = document.getElementById('agent_chat_send_btn');
            const messageInput = document.getElementById('agent_chat_message');
            const phoneInput = document.getElementById('agent_chat_phone');
            const nameInput = document.getElementById('agent_chat_name');
            const latInput = document.getElementById('agent_chat_lat');
            const lngInput = document.getElementById('agent_chat_lng');
            const budgetInput = document.getElementById('agent_chat_budget');
            const reloadBtn = document.getElementById('agent_chat_reload_btn');
            const locationBtn = document.getElementById('agent_chat_location_btn');
            const locationStatus = document.getElementById('agent_chat_location_status');
            let currentLocationAccuracy = null;
            let currentLocationLabel = null;
            let currentLocationSource = null;

            function scrollToBottom() {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            function clearMessages() {
                messagesEl.innerHTML = '';
            }

            function setEmpty(visible) {
                if (!visible) {
                    return;
                }

                clearMessages();
                messagesEl.appendChild(emptyEl);
            }

            function appendMessage(role, text, metadata = {}) {
                if (emptyEl.parentElement) {
                    emptyEl.remove();
                }

                const isUser = role === 'user';
                const wrapper = document.createElement('div');
                wrapper.className = `d-flex mb-5 ${isUser ? 'justify-content-end' : 'justify-content-start'}`;

                const bubble = document.createElement('div');
                bubble.className = `rounded p-4 mw-75 ${isUser ? 'bg-primary text-white' : 'bg-light text-gray-800'}`;

                const label = document.createElement('div');
                label.className = `fw-bold fs-7 mb-2 ${isUser ? 'text-white-50' : 'text-muted'}`;
                label.textContent = isUser ? 'Usuario' : 'Agente IA';

                const body = document.createElement('div');
                body.className = 'fs-6';
                body.style.whiteSpace = 'pre-wrap';
                body.textContent = text;

                bubble.appendChild(label);
                bubble.appendChild(body);

                const catalog = metadata.catalog || null;

                if (catalog && catalog.consulted) {
                    const meta = document.createElement('div');
                    meta.className = `fs-8 mt-3 ${isUser ? 'text-white-50' : 'text-muted'}`;
                    meta.textContent = `Base consultada: ${catalog.count || 0} candidatos (${catalog.basis || 'catalogo'})`;
                    bubble.appendChild(meta);
                }

                wrapper.appendChild(bubble);
                messagesEl.appendChild(wrapper);
                scrollToBottom();
            }

            function payloadValue(input) {
                const value = input.value.trim();

                return value === '' ? null : value;
            }

            async function loadHistory() {
                const phone = payloadValue(phoneInput);

                if (!phone) {
                    setEmpty(true);
                    return;
                }

                const url = new URL(endpoints.history, window.location.origin);
                url.searchParams.set('phone', phone);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const json = await response.json();
                const messages = json.data?.messages || [];

                if (!messages.length) {
                    setEmpty(true);
                    return;
                }

                clearMessages();
                messages.forEach((message) => appendMessage(message.role, message.message, message.metadata || {}));
            }

            async function sendMessage(event) {
                event.preventDefault();

                const message = payloadValue(messageInput);

                if (!message) {
                    return;
                }

                const payload = {
                    phone: payloadValue(phoneInput),
                    name: payloadValue(nameInput),
                    message,
                    lat: payloadValue(latInput),
                    lng: payloadValue(lngInput),
                    accuracy_meters: currentLocationAccuracy,
                    location_label: currentLocationLabel,
                    location_source: currentLocationSource,
                    budget: payloadValue(budgetInput),
                };

                sendBtn.disabled = true;
                appendMessage('user', message);
                messageInput.value = '';

                try {
                    const response = await fetch(endpoints.send, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await response.json();

                    if (!response.ok) {
                        appendMessage('assistant', json.message || 'No se pudo procesar el mensaje.');
                        return;
                    }

                    appendMessage('assistant', json.data.reply, {
                        catalog: json.data.catalog || {},
                    });
                } catch (error) {
                    appendMessage('assistant', 'No se pudo conectar con el servidor del chat.');
                } finally {
                    sendBtn.disabled = false;
                    messageInput.focus();
                }
            }

            function setLocationStatus(message, type = 'muted') {
                locationStatus.className = `form-text mt-3 text-${type}`;
                locationStatus.textContent = message;
            }

            function fillLocation(lat, lng, accuracyMeters = null, sourceLabel = 'navegador', readableLabel = null) {
                latInput.value = Number(lat).toFixed(7);
                lngInput.value = Number(lng).toFixed(7);
                currentLocationAccuracy = accuracyMeters;
                currentLocationSource = sourceLabel;
                currentLocationLabel = readableLabel;

                const accuracyText = accuracyMeters
                    ? `Precision aprox.: ${Math.round(accuracyMeters).toLocaleString()} m.`
                    : 'Precision no informada.';
                const labelText = readableLabel ? ` Zona detectada: ${readableLabel}.` : '';

                setLocationStatus(`Ubicacion cargada por ${sourceLabel}. ${accuracyText}${labelText}`, 'success');
            }

            function geolocationErrorMessage(error) {
                if (!window.isSecureContext && !['localhost', '127.0.0.1', '::1'].includes(window.location.hostname)) {
                    return 'El navegador bloquea ubicacion precisa porque esta pagina no esta en HTTPS o localhost.';
                }

                if (!error) {
                    return 'El navegador no entrego la ubicacion precisa.';
                }

                if (error.code === error.PERMISSION_DENIED) {
                    return 'Permiso denegado. Revisa permisos de ubicacion del navegador y del sistema operativo.';
                }

                if (error.code === error.POSITION_UNAVAILABLE) {
                    return 'La ubicacion precisa no esta disponible en este equipo o red.';
                }

                if (error.code === error.TIMEOUT) {
                    return 'La ubicacion precisa tardo demasiado en responder.';
                }

                return error.message || 'No pude obtener la ubicacion precisa.';
            }

            function getBrowserLocation() {
                if (!navigator.geolocation) {
                    return Promise.reject(new Error('Tu navegador no permite obtener ubicacion automaticamente.'));
                }

                return new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 20000,
                        maximumAge: 0,
                    });
                });
            }

            async function getNetworkLocation() {
                const providers = [
                    {
                        url: 'https://ipapi.co/json/',
                        parse: (data) => ({
                            lat: data.latitude,
                            lng: data.longitude,
                            label: [data.city, data.region, data.country_name].filter(Boolean).join(', '),
                        }),
                    },
                    {
                        url: 'https://ipwho.is/',
                        parse: (data) => ({
                            lat: data.latitude,
                            lng: data.longitude,
                            label: [data.city, data.region, data.country].filter(Boolean).join(', '),
                        }),
                    },
                ];

                for (const provider of providers) {
                    try {
                        const response = await fetch(provider.url, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            continue;
                        }

                        const data = await response.json();
                        const parsed = provider.parse(data);

                        if (Number.isFinite(Number(parsed.lat)) && Number.isFinite(Number(parsed.lng))) {
                            return parsed;
                        }
                    } catch (error) {
                        // Try the next provider.
                    }
                }

                return null;
            }

            locationBtn.addEventListener('click', async function () {
                locationBtn.disabled = true;
                setLocationStatus('Solicitando ubicacion precisa al navegador...', 'muted');

                try {
                    const position = await getBrowserLocation();

                    fillLocation(
                        position.coords.latitude,
                        position.coords.longitude,
                        position.coords.accuracy,
                        'navegador',
                        'coordenadas precisas del navegador'
                    );

                    locationBtn.disabled = false;
                    return;
                } catch (error) {
                    const reason = geolocationErrorMessage(error);
                    setLocationStatus(`${reason} Intentando ubicacion aproximada por red...`, 'warning');
                }

                try {
                    const networkLocation = await getNetworkLocation();

                    if (networkLocation) {
                        fillLocation(
                            networkLocation.lat,
                            networkLocation.lng,
                            25000,
                            'red/IP',
                            networkLocation.label || 'ubicacion aproximada por red'
                        );

                        return;
                    }

                    setLocationStatus(
                        'No pude obtener ubicacion por navegador ni por red. Puedes escribir lat/lng manualmente.',
                        'danger'
                    );
                    appendMessage('assistant', 'No pude obtener tu ubicacion automaticamente. Revisa permisos del navegador o escribe lat/lng manualmente.');
                } finally {
                    locationBtn.disabled = false;
                }
            });

            [latInput, lngInput].forEach((input) => {
                input.addEventListener('input', function () {
                    currentLocationAccuracy = null;
                    currentLocationSource = 'manual';
                    currentLocationLabel = 'coordenadas escritas manualmente';
                    setLocationStatus('Usando coordenadas escritas manualmente.', 'muted');
                });
            });

            form.addEventListener('submit', sendMessage);
            reloadBtn.addEventListener('click', loadHistory);
            phoneInput.addEventListener('change', loadHistory);

            loadHistory();
        });
    </script>
@endpush
