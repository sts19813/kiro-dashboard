@extends('layouts.app')

@section('title', 'Mapa de Proveedores')

@section('content')
    <style>
        .map-panel,
        .list-panel {
            position: relative;
        }

        #locations_map {
            height: 640px;
            border-radius: 0.75rem;
            background: #f1f8ff;
        }

        .location-list-scroll {
            height: 640px;
            overflow: auto;
            position: relative;
        }

        .location-list-canvas {
            position: relative;
            width: 100%;
        }

        .location-list-items {
            position: absolute;
            inset-inline: 0;
            top: 0;
            will-change: transform;
        }

        .location-card {
            border: 1px solid #e4e6ef;
            transition: all .2s ease;
            cursor: pointer;
            min-height: 176px;
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

        .location-card-title,
        .location-address {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .location-card-title {
            -webkit-line-clamp: 2;
        }

        .location-address {
            -webkit-line-clamp: 2;
            min-height: 32px;
        }

        .map-message,
        .list-empty-state,
        .surface-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .map-message,
        .list-empty-state {
            height: 100%;
            color: #3f4254;
            padding: 24px;
        }

        .surface-loader {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .82);
            border-radius: 0.75rem;
            backdrop-filter: blur(2px);
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .surface-loader.is-visible {
            opacity: 1;
            pointer-events: auto;
        }

        .surface-loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            color: #3f4254;
            font-weight: 500;
            padding: 1.5rem;
        }

        .list-status-text {
            min-height: 20px;
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
                <h1 class="fw-bold mb-1">Mapa de Proveedores</h1>
                <div class="text-muted">Explora proveedores turísticos, filtra por tags y administra puntos recomendados.</div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <div class="w-225px">
                    <label class="form-label fs-8 mb-1">Categoría</label>
                    <select id="filter_category" class="form-select form-select-sm">
                        <option value="">Todas las categorías</option>
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
                                <div class="text-muted fs-7">La carga del mapa es progresiva para evitar bloqueos cuando hay miles de puntos.</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="map-panel">
                            <div id="locations_map"></div>
                            <div id="map_loader" class="surface-loader is-visible">
                                <div class="surface-loader-content">
                                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                                    <div id="map_loader_text">Preparando mapa...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title d-flex flex-column w-100 gap-3">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <span class="fs-2 fw-bold">Proveedores (<span id="locations_count">0</span>)</span>
                                <span id="list_status_text" class="text-muted fs-8 list-status-text"></span>
                            </div>
                            <input id="filter_search" type="text" class="form-control form-control-sm"
                                placeholder="Buscar por nombre, dirección o tags">
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="list-panel">
                            <div id="list_loader" class="surface-loader is-visible">
                                <div class="surface-loader-content">
                                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                                    <div id="list_loader_text">Cargando proveedores...</div>
                                </div>
                            </div>
                            <div id="locations_list" class="location-list-scroll">
                                <div id="locations_list_canvas" class="location-list-canvas">
                                    <div id="locations_list_items" class="location-list-items"></div>
                                </div>
                            </div>
                        </div>
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
                                <label class="form-label">CategorÍa</label>
                                <input type="text" name="category" class="form-control" placeholder="restaurante, cenote, museo...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ciudad / Municipio</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tags (coma separada)</label>
                                <input type="text" name="tags" class="form-control" placeholder="familiar, comida, rom�ntico">
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
                                <label class="form-label">Dirección</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono</label>
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
                                <label class="form-label">Descripción</label>
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
                    <h3 class="modal-title" id="location_detail_title">Detalle del proveedor</h3>
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
            const locationsIndexUrl = @json(route('api.locations.index'));
            const pointsUrl = @json(route('api.points.index'));
            const locationDetailsBaseUrl = @json(url('/api/locations'));
            const deletePointBaseUrl = @json(url('/provedores/puntos'));
            const LIST_ITEM_HEIGHT = 188;
            const LIST_OVERSCAN = 6;
            const MARKER_BATCH_SIZE = 250;

            const state = {
                map: null,
                cluster: null,
                markersById: new Map(),
                locationsById: new Map(),
                detailsById: new Map(),
                locations: [],
                activeLocationId: null,
                detailModal: null,
                requestController: null,
                requestSequence: 0,
                markerSequence: 0,
            };

            const elements = {
                category: document.getElementById('filter_category'),
                city: document.getElementById('filter_city'),
                tag: document.getElementById('filter_tag'),
                sourceType: document.getElementById('filter_source_type'),
                search: document.getElementById('filter_search'),
                list: document.getElementById('locations_list'),
                listCanvas: document.getElementById('locations_list_canvas'),
                listItems: document.getElementById('locations_list_items'),
                count: document.getElementById('locations_count'),
                listStatusText: document.getElementById('list_status_text'),
                listLoader: document.getElementById('list_loader'),
                listLoaderText: document.getElementById('list_loader_text'),
                mapLoader: document.getElementById('map_loader'),
                mapLoaderText: document.getElementById('map_loader_text'),
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
                initMap();
                loadInitialCatalog();
            }

            async function initMap() {
                if (!googleMapsApiKey) {
                    renderMapMessage('Configura GOOGLE_MAPS_API_KEY en el .env para habilitar Google Maps.');
                    setMapLoading(false);
                    return;
                }

                try {
                    setMapLoading(true, 'Cargando mapa...');
                    await loadGoogleMapsApi(googleMapsApiKey);
                    createMap();

                    if (state.locations.length) {
                        renderMarkers(state.locations);
                    } else {
                        setMapLoading(false);
                    }
                } catch (error) {
                    renderMapMessage('No se pudo cargar Google Maps API. Revisa la API key y las restricciones.');
                    setMapLoading(false);
                }
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
                    element.addEventListener('change', loadFilteredLocations);
                });

                elements.search.addEventListener('input', () => {
                    window.clearTimeout(searchDebounce);
                    searchDebounce = window.setTimeout(loadFilteredLocations, 300);
                });

                elements.list.addEventListener('scroll', renderVisibleList);
                window.addEventListener('resize', renderVisibleList);

                elements.list.addEventListener('click', (event) => {
                    const deleteButton = event.target.closest('[data-delete-id]');
                    if (deleteButton) {
                        event.preventDefault();
                        event.stopPropagation();

                        const deleteId = deleteButton.getAttribute('data-delete-id');
                        if (!deleteId) {
                            return;
                        }

                        if (!window.confirm('�Deseas eliminar este punto manual?')) {
                            return;
                        }

                        elements.deleteForm.setAttribute('action', `${deletePointBaseUrl}/${deleteId}`);
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

            async function loadInitialCatalog() {
                await fetchLocations({
                    url: locationsIndexUrl,
                    includeFilters: true,
                    listMessage: 'Cargando proveedores...',
                    mapMessage: 'Preparando mapa...'
                });
            }

            async function loadFilteredLocations() {
                await fetchLocations({
                    url: `${pointsUrl}?${buildQueryParams().toString()}`,
                    includeFilters: false,
                    listMessage: 'Aplicando filtros...',
                    mapMessage: 'Actualizando mapa...'
                });
            }

            async function fetchLocations({
                url,
                includeFilters,
                listMessage,
                mapMessage
            }) {
                if (state.requestController) {
                    state.requestController.abort();
                }

                const sequence = ++state.requestSequence;
                const controller = new AbortController();
                state.requestController = controller;

                setListLoading(true, listMessage);
                setMapLoading(!!state.map, mapMessage);
                elements.listStatusText.textContent = includeFilters ? 'Cargando datos...' : 'Actualizando resultados...';

                try {
                    const response = await fetch(url, {
                        signal: controller.signal,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();

                    if (sequence !== state.requestSequence) {
                        return;
                    }

                    if (includeFilters) {
                        renderFilterOptions(payload.filters || {});
                    }

                    applyLocations(Array.isArray(payload.data) ? payload.data : []);
                    elements.listStatusText.textContent = `${Number(payload.count || 0).toLocaleString('es-MX')} resultados`;
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    renderRequestError();
                } finally {
                    if (sequence === state.requestSequence) {
                        setListLoading(false);
                        if (!state.map) {
                            setMapLoading(false);
                        }
                    }
                }
            }

            function buildQueryParams() {
                const params = new URLSearchParams();

                if (elements.search.value.trim() !== '') params.set('q', elements.search.value.trim());
                if (elements.category.value !== '') params.set('category', elements.category.value);
                if (elements.city.value !== '') params.set('city', elements.city.value);
                if (elements.tag.value !== '') params.set('tag', elements.tag.value);
                if (elements.sourceType.value !== '') params.set('source_type', elements.sourceType.value);

                return params;
            }

            function renderFilterOptions(filters) {
                applyOptions(elements.category, ['Todas las categorías'].concat(filters.categories || []));
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

            function applyLocations(locations) {
                elements.count.textContent = String(locations.length);
                state.locations = locations;
                state.locationsById = new Map(locations.map((location) => [location.id, location]));

                if (state.activeLocationId && !state.locationsById.has(state.activeLocationId)) {
                    state.activeLocationId = null;
                }

                renderVisibleList();
                renderMarkers(locations);
            }

            function renderVisibleList() {
                const total = state.locations.length;

                if (!total) {
                    elements.listCanvas.style.height = '100%';
                    elements.listItems.style.transform = 'translateY(0px)';
                    elements.listItems.innerHTML = '<div class="list-empty-state text-muted py-10">No se encontraron proveedores con esos filtros.</div>';
                    return;
                }

                const viewportHeight = elements.list.clientHeight || 640;
                const visibleCount = Math.max(1, Math.ceil(viewportHeight / LIST_ITEM_HEIGHT));
                const startIndex = Math.max(0, Math.floor(elements.list.scrollTop / LIST_ITEM_HEIGHT) - LIST_OVERSCAN);
                const endIndex = Math.min(total, startIndex + visibleCount + (LIST_OVERSCAN * 2));
                const offsetY = startIndex * LIST_ITEM_HEIGHT;
                const visibleLocations = state.locations.slice(startIndex, endIndex);

                elements.listCanvas.style.height = `${total * LIST_ITEM_HEIGHT}px`;
                elements.listItems.style.transform = `translateY(${offsetY}px)`;
                elements.listItems.innerHTML = visibleLocations.map(buildLocationCard).join('');
            }

            async function renderMarkers(locations) {
                clearMarkers();

                if (!state.map) {
                    return;
                }

                const markerSequence = ++state.markerSequence;

                if (!locations.length) {
                    setMapLoading(false);
                    return;
                }

                setMapLoading(true, locations.length > MARKER_BATCH_SIZE ? 'Renderizando mapa por lotes...' : 'Actualizando mapa...');

                const markers = [];
                const bounds = new google.maps.LatLngBounds();

                for (let index = 0; index < locations.length; index += MARKER_BATCH_SIZE) {
                    if (markerSequence !== state.markerSequence) {
                        return;
                    }

                    const batch = locations.slice(index, index + MARKER_BATCH_SIZE);

                    batch.forEach((location) => {
                        const marker = new google.maps.Marker({
                            position: {
                                lat: Number(location.lat),
                                lng: Number(location.lng),
                            },
                            title: location.name,
                            icon: markerIcon(location.source_type),
                            optimized: true,
                        });

                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                                <div style="min-width:200px">
                                    <div style="font-weight:600;margin-bottom:4px;">${escapeHtml(location.name)}</div>
                                    <div>${escapeHtml(location.category || 'Sin categor�a')}</div>
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

                    await waitForNextPaint();
                }

                if (markerSequence !== state.markerSequence) {
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

                setMapLoading(false);
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
                const visibleTags = tags.slice(0, 3).map((tag) =>
                    `<span class="badge badge-light-primary me-1 mb-1">${escapeHtml(tag)}</span>`
                ).join('');
                const extraTags = tags.length > 3 ? `<span class="badge badge-light">+${tags.length - 3}</span>` : '';
                const websiteHost = safeHost(location.website);
                const websiteHtml = websiteHost ?
                    `<div class="text-muted fs-8">${escapeHtml(websiteHost)}</div>` :
                    '';
                const phoneHtml = location.phone ? `<div class="text-muted fs-8">${escapeHtml(location.phone)}</div>` : '';
                const manualBadge = location.source_type === 'manual' ? '<span class="badge badge-light-success">Manual</span>' :
                    '<span class="badge badge-light-info">Dataset</span>';
                const deleteButton = location.editable && location.delete_id ?
                    `<button type="button" class="btn btn-sm btn-light-danger" data-delete-id="${escapeHtml(String(location.delete_id))}">Eliminar</button>` :
                    '';
                const isActive = state.activeLocationId === location.id ? ' active' : '';

                return `
                    <div class="card location-card mb-3${isActive}" data-location-id="${escapeHtml(location.id)}">
                        <div class="card-body py-4 px-4 d-flex flex-column h-100">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold fs-5 mb-1 location-card-title">${escapeHtml(location.name)}</div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        ${manualBadge}
                                        <span class="badge badge-light">${escapeHtml(location.category || 'Sin categor�a')}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light-primary flex-shrink-0" data-detail-id="${escapeHtml(location.id)}">Detalle</button>
                            </div>
                            <div class="text-gray-700 mb-1">${escapeHtml(location.city || 'Sin ciudad')}</div>
                            <div class="text-muted fs-8 mb-2 location-address">${escapeHtml(location.address || 'Sin direcci�n registrada')}</div>
                            ${phoneHtml}
                            ${websiteHtml}
                            <div class="mt-auto pt-3 d-flex justify-content-between align-items-end gap-3">
                                <div class="flex-grow-1">${visibleTags}${extraTags}</div>
                                <div class="d-flex justify-content-end">${deleteButton}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            function focusLocation(locationId) {
                if (!locationId) {
                    return;
                }

                state.activeLocationId = locationId;
                scrollToLocation(locationId);
                renderVisibleList();

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

            function scrollToLocation(locationId) {
                const locationIndex = state.locations.findIndex((location) => location.id === locationId);
                if (locationIndex === -1) {
                    return;
                }

                const targetTop = Math.max(0, (locationIndex * LIST_ITEM_HEIGHT) - LIST_ITEM_HEIGHT);
                const currentTop = elements.list.scrollTop;
                const currentBottom = currentTop + elements.list.clientHeight;
                const itemTop = locationIndex * LIST_ITEM_HEIGHT;
                const itemBottom = itemTop + LIST_ITEM_HEIGHT;

                if (itemTop < currentTop || itemBottom > currentBottom) {
                    elements.list.scrollTo({
                        top: targetTop,
                        behavior: 'smooth',
                    });
                }
            }

            async function openDetails(locationId) {
                if (!locationId) {
                    return;
                }

                const summary = state.locationsById.get(locationId);
                elements.detailTitle.textContent = summary?.name || 'Detalle del proveedor';
                elements.detailRows.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center py-10">
                            <div class="d-flex flex-column align-items-center gap-3 text-muted">
                                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                                <span>Cargando detalle...</span>
                            </div>
                        </td>
                    </tr>
                `;
                state.detailModal.show();

                if (state.detailsById.has(locationId)) {
                    renderDetailRows(state.detailsById.get(locationId));
                    return;
                }

                try {
                    const response = await fetch(`${locationDetailsBaseUrl}/${encodeURIComponent(locationId)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    const detail = payload.data || null;

                    if (!detail) {
                        throw new Error('Empty payload');
                    }

                    state.detailsById.set(locationId, detail);
                    renderDetailRows(detail);
                } catch (error) {
                    elements.detailRows.innerHTML = `
                        <tr>
                            <td colspan="2" class="text-center text-muted py-10">No se pudo cargar el detalle del proveedor.</td>
                        </tr>
                    `;
                }
            }

            function renderDetailRows(location) {
                elements.detailTitle.textContent = location.name || 'Detalle del proveedor';

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
            }

            function renderRequestError() {
                state.locations = [];
                state.locationsById.clear();
                elements.count.textContent = '0';
                elements.listStatusText.textContent = 'No se pudo cargar la informaci�n';
                elements.listCanvas.style.height = '100%';
                elements.listItems.style.transform = 'translateY(0px)';
                elements.listItems.innerHTML = '<div class="list-empty-state text-muted py-10">No se pudo cargar la informaci�n. Intenta nuevamente.</div>';
                clearMarkers();
                setMapLoading(false);
            }

            function setListLoading(isLoading, message = 'Cargando proveedores...') {
                elements.listLoader.classList.toggle('is-visible', isLoading);
                elements.listLoaderText.textContent = message;
            }

            function setMapLoading(isLoading, message = 'Actualizando mapa...') {
                elements.mapLoader.classList.toggle('is-visible', isLoading);
                elements.mapLoaderText.textContent = message;
            }

            function waitForNextPaint() {
                return new Promise((resolve) => {
                    window.requestAnimationFrame(() => resolve());
                });
            }

            function safeHost(url) {
                try {
                    if (!url) {
                        return '';
                    }

                    const parsed = new URL(url);
                    return parsed.hostname.replace(/^www\./, '');
                } catch (error) {
                    return '';
                }
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
