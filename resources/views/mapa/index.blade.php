@extends('layouts.app')

@section('title', 'Mapa de Ubicaciones')

@section('content')
    <style>
        #locations_map {
            height: 640px;
            border-radius: 0.75rem;
            background: #f1f8ff;
        }

        .location-list-scroll {
            max-height: 640px;
            overflow: auto;
        }

        .location-card {
            border: 1px solid #e4e6ef;
            transition: all .2s ease;
            cursor: pointer;
        }

        .location-card:hover {
            border-color: #c2d4ff;
            box-shadow: 0 0 0 1px rgba(62, 119, 255, .08);
        }

        .location-card.active {
            border-color: #3e97ff;
            box-shadow: 0 0 0 2px rgba(62, 119, 255, .15);
            background-color: #f8fbff;
        }

        .map-message {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #3f4254;
            padding: 24px;
        }
    </style>

    <div class="container-fluid">
        @if (session('status') === 'location-created')
            <div class="alert alert-success mb-5">Punto agregado correctamente.</div>
        @endif

        @if (session('status') === 'location-deleted')
            <div class="alert alert-success mb-5">Punto eliminado correctamente.</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-5">
                <div class="fw-semibold mb-2">No se pudo guardar el punto:</div>
                <ul class="mb-0 ps-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-4 mb-5">
            <div>
                <h1 class="fw-bold mb-1">Mapa de Ubicaciones</h1>
                <div class="text-muted">Explora proveedores turisticos, filtra por tags y administra puntos recomendados.</div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <div class="w-225px">
                    <label class="form-label fs-8 mb-1">Categoria</label>
                    <select id="filter_category" class="form-select form-select-sm">
                        <option value="">Todas las categorias</option>
                    </select>
                </div>
                <div class="w-225px">
                    <label class="form-label fs-8 mb-1">Municipio / Ciudad</label>
                    <select id="filter_city" class="form-select form-select-sm">
                        <option value="">Todos los municipios</option>
                    </select>
                </div>
                <div class="w-225px">
                    <label class="form-label fs-8 mb-1">Tag</label>
                    <select id="filter_tag" class="form-select form-select-sm">
                        <option value="">Todos los tags</option>
                    </select>
                </div>
                <div class="w-175px">
                    <label class="form-label fs-8 mb-1">Origen</label>
                    <select id="filter_source_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="dataset">Dataset</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                <div class="d-flex align-items-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPointModal">
                        Agregar punto
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title">
                            <div>
                                <span class="fs-2 fw-bold">Mapa interactivo</span>
                                <div class="text-muted fs-7">Haz clic en el mapa para autollenar latitud y longitud del nuevo punto.</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="locations_map"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title d-flex flex-column w-100">
                            <span class="fs-2 fw-bold mb-3">Ubicaciones (<span id="locations_count">0</span>)</span>
                            <input id="filter_search" type="text" class="form-control form-control-sm"
                                placeholder="Buscar por nombre, descripcion o tags">
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="locations_list" class="location-list-scroll"></div>
                    </div>
                </div>
            </div>
        </div>

        <form id="delete_manual_point_form" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <div class="modal fade" id="addPointModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Agregar nuevo punto</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Nombre</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <input type="text" name="category" class="form-control" placeholder="restaurante, cenote, museo...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ciudad / Municipio</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tags (coma separada)</label>
                                <input type="text" name="tags" class="form-control" placeholder="familiar, comida, romantico">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Latitud</label>
                                <input type="number" step="0.000001" name="lat" id="new_point_lat" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Longitud</label>
                                <input type="number" step="0.000001" name="lng" id="new_point_lng" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Direccion</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Metadata extra (JSON opcional)</label>
                                <textarea name="metadata" class="form-control" rows="3"
                                    placeholder='{"horarios":"Lunes a Viernes", "destacado":true}'></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar punto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="locationDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="location_detail_title">Detalle de ubicacion</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-0 gy-2">
                            <tbody id="location_detail_rows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
    <script>
        (function() {
            const googleMapsApiKey = @json($googleMapsApiKey ?? '');
            const googleMapsMapId = @json($googleMapsMapId ?? '');

            const state = {
                map: null,
                cluster: null,
                markersById: new Map(),
                locationsById: new Map(),
                activeLocationId: null,
                detailModal: null,
            };

            const elements = {
                category: document.getElementById('filter_category'),
                city: document.getElementById('filter_city'),
                tag: document.getElementById('filter_tag'),
                sourceType: document.getElementById('filter_source_type'),
                search: document.getElementById('filter_search'),
                list: document.getElementById('locations_list'),
                count: document.getElementById('locations_count'),
                detailTitle: document.getElementById('location_detail_title'),
                detailRows: document.getElementById('location_detail_rows'),
                deleteForm: document.getElementById('delete_manual_point_form'),
                latInput: document.getElementById('new_point_lat'),
                lngInput: document.getElementById('new_point_lng'),
                map: document.getElementById('locations_map'),
            };

            let searchDebounce = null;

            function init() {
                state.detailModal = new bootstrap.Modal(document.getElementById('locationDetailModal'));
                setupEvents();

                if (!googleMapsApiKey) {
                    renderMapMessage('Configura GOOGLE_MAPS_API_KEY en el .env para habilitar Google Maps.');
                    loadLocations();
                    return;
                }

                loadGoogleMapsApi(googleMapsApiKey)
                    .then(() => {
                        createMap();
                        loadLocations();
                    })
                    .catch(() => {
                        renderMapMessage('No se pudo cargar Google Maps API. Revisa la API key y las restricciones.');
                        loadLocations();
                    });
            }

            function renderMapMessage(message) {
                elements.map.innerHTML = `<div class="map-message">${escapeHtml(message)}</div>`;
            }

            function loadGoogleMapsApi(apiKey) {
                return new Promise((resolve, reject) => {
                    if (window.google && window.google.maps) {
                        resolve();
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly`;
                    script.async = true;
                    script.defer = true;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            function createMap() {
                const options = {
                    center: {
                        lat: 20.966,
                        lng: -89.623
                    },
                    zoom: 8,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    gestureHandling: 'greedy',
                };

                if (googleMapsMapId) {
                    options.mapId = googleMapsMapId;
                }

                state.map = new google.maps.Map(elements.map, options);

                state.map.addListener('click', (event) => {
                    if (!event.latLng) {
                        return;
                    }

                    elements.latInput.value = event.latLng.lat().toFixed(6);
                    elements.lngInput.value = event.latLng.lng().toFixed(6);
                });
            }

            function setupEvents() {
                [elements.category, elements.city, elements.tag, elements.sourceType].forEach((element) => {
                    element.addEventListener('change', loadLocations);
                });

                elements.search.addEventListener('input', () => {
                    window.clearTimeout(searchDebounce);
                    searchDebounce = window.setTimeout(loadLocations, 250);
                });

                elements.list.addEventListener('click', (event) => {
                    const deleteButton = event.target.closest('[data-delete-id]');
                    if (deleteButton) {
                        event.preventDefault();
                        event.stopPropagation();

                        const deleteId = deleteButton.getAttribute('data-delete-id');
                        if (!deleteId) {
                            return;
                        }

                        if (!window.confirm('Deseas eliminar este punto manual?')) {
                            return;
                        }

                        elements.deleteForm.setAttribute('action', `/ubicaciones/puntos/${deleteId}`);
                        elements.deleteForm.submit();
                        return;
                    }

                    const detailButton = event.target.closest('[data-detail-id]');
                    if (detailButton) {
                        event.preventDefault();
                        event.stopPropagation();
                        openDetails(detailButton.getAttribute('data-detail-id'));
                        return;
                    }

                    const locationCard = event.target.closest('[data-location-id]');
                    if (locationCard) {
                        focusLocation(locationCard.getAttribute('data-location-id'));
                    }
                });
            }

            async function loadLocations() {
                const params = new URLSearchParams();

                if (elements.search.value.trim() !== '') params.set('q', elements.search.value.trim());
                if (elements.category.value !== '') params.set('category', elements.category.value);
                if (elements.city.value !== '') params.set('city', elements.city.value);
                if (elements.tag.value !== '') params.set('tag', elements.tag.value);
                if (elements.sourceType.value !== '') params.set('source_type', elements.sourceType.value);

                const response = await fetch(`/api/locations?${params.toString()}`);
                const payload = await response.json();

                const locations = Array.isArray(payload.data) ? payload.data : [];
                renderFilterOptions(payload.filters || {});
                renderLocations(locations);
            }

            function renderFilterOptions(filters) {
                applyOptions(elements.category, ['Todas las categorias'].concat(filters.categories || []));
                applyOptions(elements.city, ['Todos los municipios'].concat(filters.cities || []));
                applyOptions(elements.tag, ['Todos los tags'].concat(filters.tags || []));
            }

            function applyOptions(selectElement, options) {
                const currentValue = selectElement.value;
                selectElement.innerHTML = '';

                options.forEach((item, index) => {
                    const option = document.createElement('option');
                    option.value = index === 0 ? '' : item;
                    option.textContent = item;
                    selectElement.appendChild(option);
                });

                if ([...selectElement.options].some((option) => option.value === currentValue)) {
                    selectElement.value = currentValue;
                }
            }

            function renderLocations(locations) {
                elements.count.textContent = String(locations.length);
                state.locationsById.clear();

                clearMarkers();

                if (!locations.length) {
                    elements.list.innerHTML =
                        '<div class="text-muted py-10 text-center">No se encontraron ubicaciones con esos filtros.</div>';
                    return;
                }

                const cards = [];
                const markers = [];
                const bounds = state.map ? new google.maps.LatLngBounds() : null;

                locations.forEach((location) => {
                    state.locationsById.set(location.id, location);
                    cards.push(buildLocationCard(location));

                    if (!state.map) {
                        return;
                    }

                    const marker = new google.maps.Marker({
                        position: {
                            lat: Number(location.lat),
                            lng: Number(location.lng),
                        },
                        title: location.name,
                        icon: markerIcon(location.source_type),
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="min-width:200px">
                                <div style="font-weight:600;margin-bottom:4px;">${escapeHtml(location.name)}</div>
                                <div>${escapeHtml(location.category || 'Sin categoria')}</div>
                                <div>${escapeHtml(location.city || 'Sin ciudad')}</div>
                            </div>
                        `,
                    });

                    marker.addListener('click', () => {
                        focusLocation(location.id);
                    });

                    state.markersById.set(location.id, {
                        marker,
                        infoWindow,
                    });

                    markers.push(marker);
                    bounds.extend(marker.getPosition());
                });

                elements.list.innerHTML = cards.join('');

                if (!state.map) {
                    return;
                }

                if (window.markerClusterer && typeof window.markerClusterer.MarkerClusterer === 'function') {
                    state.cluster = new window.markerClusterer.MarkerClusterer({
                        map: state.map,
                        markers,
                    });
                } else {
                    markers.forEach((marker) => marker.setMap(state.map));
                }

                if (markers.length > 1) {
                    state.map.fitBounds(bounds, 60);
                } else if (markers.length === 1) {
                    state.map.setCenter(markers[0].getPosition());
                    state.map.setZoom(14);
                }
            }

            function clearMarkers() {
                if (state.cluster && typeof state.cluster.clearMarkers === 'function') {
                    state.cluster.clearMarkers();
                }

                state.markersById.forEach((entry) => {
                    entry.infoWindow.close();
                    entry.marker.setMap(null);
                });

                state.cluster = null;
                state.markersById.clear();
            }

            function markerIcon(sourceType) {
                const isManual = sourceType === 'manual';

                return {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: isManual ? '#17c653' : '#3e97ff',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 8,
                };
            }

            function buildLocationCard(location) {
                const tags = Array.isArray(location.tags) ? location.tags : [];
                const visibleTags = tags.slice(0, 4).map((tag) =>
                    `<span class="badge badge-light-primary me-1 mb-1">${escapeHtml(tag)}</span>`
                ).join('');
                const extraTags = tags.length > 4 ? `<span class="badge badge-light">+${tags.length - 4}</span>` : '';

                const websiteHtml = location.website ?
                    `<div class="text-muted fs-8"><a href="${escapeHtml(location.website)}" target="_blank" rel="noopener noreferrer">${escapeHtml(location.website)}</a></div>` :
                    '';

                const phoneHtml = location.phone ? `<div class="text-muted fs-8">${escapeHtml(location.phone)}</div>` : '';
                const manualBadge = location.source_type === 'manual' ? '<span class="badge badge-light-success">Manual</span>' :
                    '<span class="badge badge-light-info">Dataset</span>';
                const deleteButton = location.editable && location.raw && location.raw.id ?
                    `<button type="button" class="btn btn-sm btn-light-danger" data-delete-id="${escapeHtml(String(location.raw.id))}">Eliminar</button>` :
                    '';

                return `
                    <div class="card location-card mb-3" data-location-id="${escapeHtml(location.id)}">
                        <div class="card-body py-4 px-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold fs-5 mb-1">${escapeHtml(location.name)}</div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        ${manualBadge}
                                        <span class="badge badge-light">${escapeHtml(location.category || 'Sin categoria')}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light-primary" data-detail-id="${escapeHtml(location.id)}">Detalle</button>
                            </div>
                            <div class="text-gray-700 mb-1">${escapeHtml(location.city || 'Sin ciudad')}</div>
                            <div class="text-muted fs-8 mb-2">${escapeHtml(location.address || '')}</div>
                            ${phoneHtml}
                            ${websiteHtml}
                            <div class="mt-2">${visibleTags}${extraTags}</div>
                            <div class="mt-3 d-flex justify-content-end">${deleteButton}</div>
                        </div>
                    </div>
                `;
            }

            function focusLocation(locationId) {
                state.activeLocationId = locationId;

                document.querySelectorAll('[data-location-id]').forEach((node) => {
                    node.classList.toggle('active', node.getAttribute('data-location-id') === locationId);
                });

                if (!state.map) {
                    return;
                }

                const markerEntry = state.markersById.get(locationId);

                if (!markerEntry) {
                    return;
                }

                markerEntry.infoWindow.open({
                    map: state.map,
                    anchor: markerEntry.marker,
                });

                state.map.panTo(markerEntry.marker.getPosition());

                const currentZoom = state.map.getZoom() || 8;
                if (currentZoom < 14) {
                    state.map.setZoom(14);
                }
            }

            function openDetails(locationId) {
                const location = state.locationsById.get(locationId);
                if (!location) {
                    return;
                }

                elements.detailTitle.textContent = location.name || 'Detalle de ubicacion';

                const baseRows = {
                    name: location.name,
                    category: location.category,
                    city: location.city,
                    address: location.address,
                    lat: location.lat,
                    lng: location.lng,
                    phone: location.phone,
                    email: location.email,
                    website: location.website,
                    description: location.description,
                    tags: location.tags,
                    source: location.source,
                    source_type: location.source_type,
                };

                const mergedRows = Object.assign({}, location.raw || {}, baseRows);

                elements.detailRows.innerHTML = Object.entries(mergedRows)
                    .filter(([, value]) => value !== null && value !== '')
                    .map(([key, value]) => {
                        const printableValue = Array.isArray(value) || typeof value === 'object' ? JSON.stringify(value) : String(value);
                        return `
                            <tr>
                                <td class="w-250px fw-semibold text-gray-700">${escapeHtml(key)}</td>
                                <td class="text-gray-900">${escapeHtml(printableValue)}</td>
                            </tr>
                        `;
                    }).join('');

                state.detailModal.show();
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            init();
        })();
    </script>
@endpush
