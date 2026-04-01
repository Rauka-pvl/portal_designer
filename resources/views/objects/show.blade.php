@extends('layouts.dashboard')

@section('title', $object->address)

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .dark .panel {
            background: #161615;
            border-color: #3E3E3A;
        }

        .field-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        .dark .field-label {
            color: #A1A09A;
        }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 600;
        }

        .btn:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .dark .btn {
            background: #0a0a0a;
            border-color: #3E3E3A;
            color: #A1A09A;
        }

        .btn-primary {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .btn-danger {
            border-color: rgba(239, 68, 68, 0.35);
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
        }

        .dark .btn-danger {
            color: #f87171;
        }

        .form-control:disabled,
        .modal-input:disabled,
        textarea:disabled {
            opacity: 0.85;
            cursor: not-allowed;
        }

        .object-map {
            height: 280px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .dark .object-map {
            border-color: #3E3E3A;
        }

        .address-suggest {
            position: relative;
        }

        .address-suggest-list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 60;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            max-height: 220px;
            overflow-y: auto;
        }

        .dark .address-suggest-list {
            background: #161615;
            border-color: #3E3E3A;
        }

        .address-suggest-item {
            width: 100%;
            text-align: left;
            padding: 0.55rem 0.75rem;
            font-size: 0.875rem;
            color: #0f172a;
            border: 0;
            background: transparent;
        }

        .address-suggest-item:hover {
            background: #f8fafc;
        }

        .dark .address-suggest-item {
            color: #EDEDEC;
        }

        .dark .address-suggest-item:hover {
            background: #0a0a0a;
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-700/40 bg-emerald-50 dark:bg-emerald-900/10 px-4 py-3 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                {{ $object->address }}
            </h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                {{ __('objects.client') }}: {{ $object->client?->full_name ?? $object->client_id }}
            </p>
        </div>
        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">
                {{ __('objects.edit') }}
            </button>
            <a href="{{ route('objects.index') }}" class="btn">
                {{ __('objects.close') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <form id="object-details-form" method="POST" action="{{ route('objects.add_object') }}" enctype="multipart/form-data" autocomplete="off">
            @csrf
            <input type="hidden" name="object_id" value="{{ $object->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="field-label mb-2">{{ __('objects.city') }}</div>
                    <select id="object_city" name="city" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input" disabled required>
                        @foreach (['Алматы', 'Астана', 'Шымкент', 'Караганда', 'Актобе', 'Тараз', 'Павлодар', 'Усть-Каменогорск', 'Семей', 'Атырау', 'Костанай', 'Кызылорда', 'Уральск', 'Петропавловск', 'Актау', 'Темиртау', 'Туркестан', 'Кокшетау', 'Талдыкорган', 'Экибастуз'] as $city)
                            <option value="{{ $city }}" @selected($object->city === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 address-suggest">
                    <div class="field-label mb-2">{{ __('objects.address') }}</div>
                    <input id="object_address" name="address" type="text"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->address }}" disabled required autocomplete="off" autocorrect="off" spellcheck="false">
                    <div id="object-address-suggest-list" class="address-suggest-list hidden"></div>
                </div>

                <div id="object_floor_wrap" class="object-apartment-field {{ $object->type === 'apartment' ? '' : 'hidden' }}">
                    <div class="field-label mb-2">{{ __('objects.apartment_floor') }}</div>
                    <input id="object_apartment_floor" name="apartment_floor" type="text"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->apartment_floor }}" disabled autocomplete="off" autocorrect="off" spellcheck="false">
                </div>

                <div id="object_entrance_wrap" class="object-apartment-field {{ $object->type === 'apartment' ? '' : 'hidden' }}">
                    <div class="field-label mb-2">{{ __('objects.apartment_entrance') }}</div>
                    <input id="object_apartment_entrance" name="apartment_entrance" type="text"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->apartment_entrance }}" disabled autocomplete="off" autocorrect="off" spellcheck="false">
                </div>

                <div id="object_apartment_wrap" class="object-apartment-field {{ $object->type === 'apartment' ? '' : 'hidden' }}">
                    <div class="field-label mb-2">{{ __('objects.apartment_number') }}</div>
                    <input id="object_apartment" name="apartment" type="text"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->apartment }}" disabled autocomplete="off" autocorrect="off" spellcheck="false">
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.type') }}</div>
                    <select id="object_type" name="type" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input" disabled required>
                        <option value="apartment" @selected($object->type === 'apartment')>{{ __('objects.apartment') }}</option>
                        <option value="house" @selected($object->type === 'house')>{{ __('objects.house') }}</option>
                        <option value="commercial" @selected($object->type === 'commercial')>{{ __('objects.commercial') }}</option>
                        <option value="other" @selected($object->type === 'other')>{{ __('objects.other') }}</option>
                    </select>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.status') }}</div>
                    <select id="object_status" name="status" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input" disabled required>
                        <option value="new" @selected($object->status === 'new')>{{ __('objects.new') }}</option>
                        <option value="in_work" @selected($object->status === 'in_work')>{{ __('objects.in_work') }}</option>
                        <option value="not_working" @selected($object->status === 'not_working')>{{ __('objects.not_working') }}</option>
                    </select>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.area') }} ({{ __('objects.area_m2') }})</div>
                    <input id="object_area" name="area" type="number" step="0.01"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->area }}" disabled required>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.planned') }}</div>
                    <input id="repair_budget_planned" name="repair_budget_planned" type="number" step="0.01"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->repair_budget_planned }}" disabled>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.actual') }}</div>
                    <input id="repair_budget_actual" name="repair_budget_actual" type="number" step="0.01"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->repair_budget_actual }}" disabled>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.repair_budget_per_m2') }} - {{ __('objects.planned') }}</div>
                    <input id="repair_budget_per_m2_planned" name="repair_budget_per_m2_planned" type="number" step="0.01"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->repair_budget_per_m2_planned }}" disabled>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('objects.repair_budget_per_m2') }} - {{ __('objects.actual') }}</div>
                    <input id="repair_budget_per_m2_actual" name="repair_budget_per_m2_actual" type="number" step="0.01"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input"
                        value="{{ $object->repair_budget_per_m2_actual }}" disabled>
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('objects.map_point') }}</div>
                    <p class="text-xs mb-2 text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.map_hint') }}</p>
                    <div id="object-map" class="object-map"></div>
                    <input type="hidden" name="latitude" id="object_latitude" value="{{ $object->latitude }}">
                    <input type="hidden" name="longitude" id="object_longitude" value="{{ $object->longitude }}">
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('objects.links') }}</div>
                    <textarea id="links_text" name="links_text" rows="4"
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] modal-input resize-none"
                        disabled>{{ is_array($object->links) ? implode("\n", $object->links) : '' }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('objects.files') }}</div>
                    <input id="files" name="files[]" type="file" multiple
                        class="w-full text-sm text-[#64748b] dark:text-[#A1A09A] form-control"
                        disabled>

                    @php
                        $filePaths = is_array($object->file_paths) ? $object->file_paths : [];
                        if (empty($filePaths) && !empty($object->file_paths)) {
                            $decoded = json_decode((string)$object->file_paths, true);
                            $filePaths = is_array($decoded) ? $decoded : [];
                        }
                    @endphp

                    @if (!empty($filePaths))
                        <div class="mt-3 flex flex-col gap-2">
                            @foreach ($filePaths as $path)
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] break-all">
                                        {{ basename($path) }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] dark:hover:border-[#f59e0b] transition-colors">
                                            {{ __('objects.view') }}
                                        </a>
                                        <a href="{{ asset('storage/' . $path) }}" download="{{ basename($path) }}"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors">
                                            {{ __('objects.download') }}
                                        </a>
                                        <button type="button"
                                            onclick="window.deleteObjectFileFromShow({{ $object->id }}, {{ $loop->index }})"
                                            class="edit-only-control hidden p-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors"
                                            title="{{ __('objects.delete_file') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs mt-3 text-[#64748b] dark:text-[#A1A09A]">-</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <div class="flex gap-3">
                    <button id="btn-save" type="submit" class="btn btn-primary hidden">
                        {{ __('objects.save') }}
                    </button>
                    <button id="btn-cancel" type="button" class="btn hidden">
                        {{ __('objects.cancel') }}
                    </button>
                </div>

                <button type="submit" form="delete-object-form"
                    onclick="return confirm('{{ __('objects.delete_confirm') }}')"
                    class="btn btn-danger">
                    {{ __('objects.delete') }}
                </button>
            </div>
        </form>
    </div>

    <form id="delete-object-form" method="POST" action="{{ route('objects.delete_object', $object->id) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function() {
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');
            const form = document.getElementById('object-details-form');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const typeSelect = document.getElementById('object_type');
            const apartmentFields = document.querySelectorAll('.object-apartment-field');
            const apartmentInput = document.getElementById('object_apartment');
            const floorInput = document.getElementById('object_apartment_floor');
            const entranceInput = document.getElementById('object_apartment_entrance');
            const latInput = document.getElementById('object_latitude');
            const lngInput = document.getElementById('object_longitude');
            const citySelect = document.getElementById('object_city');
            const addressInput = document.getElementById('object_address');
            const suggestList = document.getElementById('object-address-suggest-list');

            const defaultMapCenter = [48.0196, 66.9237];
            const defaultMapZoom = 5;
            let map = null;
            let marker = null;

            function syncApartmentVisibility() {
                const isApartment = typeSelect?.value === 'apartment';
                apartmentFields.forEach(el => el.classList.toggle('hidden', !isApartment));
                if (apartmentInput) {
                    apartmentInput.required = !!isApartment;
                    if (!isApartment) apartmentInput.value = '';
                }
                if (floorInput) {
                    floorInput.required = !!isApartment;
                    if (!isApartment) floorInput.value = '';
                }
                if (entranceInput) {
                    entranceInput.required = !!isApartment;
                    if (!isApartment) entranceInput.value = '';
                }
            }

            function updateMarker(lat, lng) {
                if (!map) return;
                const hasPoint = Number.isFinite(lat) && Number.isFinite(lng);
                if (hasPoint) {
                    if (!marker) {
                        marker = L.marker([lat, lng]).addTo(map);
                    } else {
                        marker.setLatLng([lat, lng]);
                    }
                    map.setView([lat, lng], 15);
                } else if (marker) {
                    map.removeLayer(marker);
                    marker = null;
                    map.setView(defaultMapCenter, defaultMapZoom);
                }
            }

            function initMap() {
                if (typeof L === 'undefined') return;
                map = L.map('object-map').setView(defaultMapCenter, defaultMapZoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(map);

                updateMarker(Number(latInput?.value), Number(lngInput?.value));

                map.on('click', function(e) {
                    if (typeSelect?.disabled) return;
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    if (latInput) latInput.value = String(lat);
                    if (lngInput) lngInput.value = String(lng);
                    updateMarker(lat, lng);
                    reverseGeocodeAndFillAddress(lat, lng).catch(() => {});
                    hideAddressSuggestions();
                });
            }

            function hideAddressSuggestions() {
                if (!suggestList) return;
                suggestList.classList.add('hidden');
                suggestList.innerHTML = '';
            }

            let addressFieldInternalUpdate = false;

            function setAddressValue(v) {
                if (!addressInput) return;
                addressFieldInternalUpdate = true;
                addressInput.value = String(v || '').slice(0, 255);
                queueMicrotask(() => {
                    addressFieldInternalUpdate = false;
                });
            }

            function clearMapCoords() {
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                updateMarker(NaN, NaN);
            }

            function applyAddressPickFromGeocoder(lat, lon, displayName) {
                setAddressValue(displayName);
                if (latInput) latInput.value = Number.isFinite(lat) ? String(lat) : '';
                if (lngInput) lngInput.value = Number.isFinite(lon) ? String(lon) : '';
                hideAddressSuggestions();
                setTimeout(() => {
                    if (map) map.invalidateSize();
                    updateMarker(lat, lon);
                }, 80);
            }

            let lastAddressSuggestionRows = [];
            let addressSearchTimer = null;
            let addressSearchAbort = null;
            let reverseGeocodeAbort = null;

            async function searchAddressSuggestions(query) {
                if (!suggestList) return;
                if (addressSearchAbort) addressSearchAbort.abort();
                addressSearchAbort = new AbortController();
                const cityPart = citySelect?.value ? `, ${citySelect.value}` : '';
                const q = `${query}${cityPart}, Kazakhstan`;
                const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6&countrycodes=kz&q=${encodeURIComponent(q)}`;
                const r = await fetch(url, {
                    signal: addressSearchAbort.signal,
                    headers: { 'Accept': 'application/json' },
                });
                const rows = await r.json().catch(() => []);
                if (!Array.isArray(rows) || !rows.length) {
                    hideAddressSuggestions();
                    return;
                }

                lastAddressSuggestionRows = rows;
                suggestList.innerHTML = rows.map((row, idx) => {
                    const titleRaw = String(row.display_name || row.name || '').slice(0, 255);
                    const title = titleRaw.replace(/[&<>"']/g, (c) => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                    }[c]));
                    return `<button type="button" class="address-suggest-item" data-idx="${idx}">${title}</button>`;
                }).join('');
                suggestList.classList.remove('hidden');

                suggestList.querySelectorAll('.address-suggest-item').forEach(btn => {
                    btn.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.idx, 10);
                        const row = lastAddressSuggestionRows[idx];
                        if (!row) return;
                        const lat = parseFloat(row.lat);
                        const lon = parseFloat(row.lon);
                        const titleRaw = String(row.display_name || row.name || '').slice(0, 255);
                        applyAddressPickFromGeocoder(lat, lon, titleRaw);
                    });
                });
            }

            async function reverseGeocodeAndFillAddress(lat, lng) {
                if (reverseGeocodeAbort) reverseGeocodeAbort.abort();
                reverseGeocodeAbort = new AbortController();
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(String(lat))}&lon=${encodeURIComponent(String(lng))}&zoom=18&addressdetails=1`;
                const r = await fetch(url, {
                    signal: reverseGeocodeAbort.signal,
                    headers: { 'Accept': 'application/json' },
                });
                const data = await r.json().catch(() => ({}));
                if (data?.display_name) setAddressValue(data.display_name);
            }

            window.deleteObjectFileFromShow = async function(objectId, fileIndex) {
                if (!confirm('{{ __('objects.delete_file_confirm') }}')) return;
                try {
                    const r = await fetch(`/objects/${objectId}/files/${fileIndex}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                        alert(data.message || '{{ __('objects.error') }}');
                        return;
                    }
                    location.reload();
                } catch (e) {
                    console.error(e);
                    alert('{{ __('objects.error') }}');
                }
            };

            const toggleEdit = (enabled) => {
                const controls = form.querySelectorAll('input, select, textarea');
                controls.forEach(el => {
                    if (el.type === 'hidden') return;
                    el.disabled = !enabled;
                });

                if (btnSave) btnSave.classList.toggle('hidden', !enabled);
                if (btnCancel) btnCancel.classList.toggle('hidden', !enabled);
                if (btnEdit) btnEdit.classList.toggle('hidden', enabled);
                document.querySelectorAll('.edit-only-control').forEach((el) => {
                    el.classList.toggle('hidden', !enabled);
                });
                if (map) {
                    setTimeout(() => map.invalidateSize(), 80);
                }
            };

            if (btnEdit) {
                btnEdit.addEventListener('click', () => toggleEdit(true));
            }

            if (btnCancel) {
                btnCancel.addEventListener('click', () => location.reload());
            }

            typeSelect?.addEventListener('change', syncApartmentVisibility);
            addressInput?.addEventListener('input', function() {
                if (addressInput.disabled) return;
                if (!addressFieldInternalUpdate) {
                    clearMapCoords();
                }
                const q = this.value.trim();
                clearTimeout(addressSearchTimer);
                if (q.length < 3) {
                    hideAddressSuggestions();
                    return;
                }
                addressSearchTimer = setTimeout(() => {
                    searchAddressSuggestions(q).catch(() => hideAddressSuggestions());
                }, 300);
            });
            addressInput?.addEventListener('blur', () => setTimeout(hideAddressSuggestions, 220));
            citySelect?.addEventListener('change', () => {
                hideAddressSuggestions();
                clearMapCoords();
            });

            form?.addEventListener('submit', function(e) {
                const lat = parseFloat(latInput?.value || '');
                const lng = parseFloat(lngInput?.value || '');
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    e.preventDefault();
                    alert('{{ __('objects.map_point_required') }}');
                }
            });
            document.addEventListener('click', function(e) {
                if (!suggestList || !addressInput) return;
                if (e.target === addressInput || suggestList.contains(e.target)) return;
                hideAddressSuggestions();
            });
            syncApartmentVisibility();
            initMap();
        })();
    </script>
@endsection

