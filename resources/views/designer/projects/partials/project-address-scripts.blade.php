<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    .address-suggest { position: relative; }
    .address-suggest-list {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 80;
        background: #fff;
        border: 1px solid #7c8799;
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
        cursor: pointer;
    }
    .address-suggest-item:hover { background: #f8fafc; }
    .dark .address-suggest-item { color: #EDEDEC; }
    .dark .address-suggest-item:hover { background: #0a0a0a; }
    .project-address-map {
        height: 240px;
        width: 100%;
        border-radius: 12px;
        border: 1px solid #7c8799;
        z-index: 1;
    }
    .dark .project-address-map { border-color: #3E3E3A; }
</style>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
window.CrmProjectAddress = (function () {
    const defaultCenter = [48.0196, 66.9237];
    const defaultZoom = 5;
    // Passport city options are stored in Russian — ask Nominatim for ru labels.
    const nominatimLang = 'ru';
    const cityAliases = {
        almaty: 'Алматы',
        almaata: 'Алматы',
        'alma-ata': 'Алматы',
        алматы: 'Алматы',
        алатау: 'Алматы',
        astana: 'Астана',
        nurusultan: 'Астана',
        'nur-sultan': 'Астана',
        'nur sultan': 'Астана',
        астана: 'Астана',
        'нур-султан': 'Астана',
        shymkent: 'Шымкент',
        chimkent: 'Шымкент',
        шымкент: 'Шымкент',
        karaganda: 'Караганда',
        qaraghandy: 'Караганда',
        караганда: 'Караганда',
        aktobe: 'Актобе',
        aktubinsk: 'Актобе',
        актобе: 'Актобе',
        taraz: 'Тараз',
        zhambyl: 'Тараз',
        тараз: 'Тараз',
        pavlodar: 'Павлодар',
        павлодар: 'Павлодар',
        ustkamenogorsk: 'Усть-Каменогорск',
        'ust-kamenogorsk': 'Усть-Каменогорск',
        oskemen: 'Усть-Каменогорск',
        'усть-каменогорск': 'Усть-Каменогорск',
        өскемен: 'Усть-Каменогорск',
        semey: 'Семей',
        semipalatinsk: 'Семей',
        семей: 'Семей',
        atyrau: 'Атырау',
        guriev: 'Атырау',
        атырау: 'Атырау',
        kostanay: 'Костанай',
        kostanai: 'Костанай',
        қостанай: 'Костанай',
        костанай: 'Костанай',
        kyzylorda: 'Кызылорда',
        qyzylorda: 'Кызылорда',
        қызылорда: 'Кызылорда',
        кызылорда: 'Кызылорда',
        oral: 'Уральск',
        uralsk: 'Уральск',
        уральск: 'Уральск',
        petropavl: 'Петропавловск',
        petropavlovsk: 'Петропавловск',
        петропавловск: 'Петропавловск',
        aktau: 'Актау',
        shevchenko: 'Актау',
        актау: 'Актау',
        temirtau: 'Темиртау',
        темиртау: 'Темиртау',
        turkistan: 'Туркестан',
        turkestan: 'Туркестан',
        туркестан: 'Туркестан',
        kokshetau: 'Кокшетау',
        kokchetav: 'Кокшетау',
        кокшетау: 'Кокшетау',
        taldykorgan: 'Талдыкорган',
        'taldy-kurgan': 'Талдыкорган',
        талдыкорган: 'Талдыкорган',
        ekibastuz: 'Экибастуз',
        экибастуз: 'Экибастуз',
    };
    let map = null;
    let marker = null;
    let suggestTimer = null;
    let searchAbort = null;
    let reverseAbort = null;
    let lastRows = [];
    let cities = [];
    let onChange = null;
    let ready = false;

    function els() {
        return {
            city: document.getElementById('ov-city'),
            address: document.getElementById('ov-address'),
            suggest: document.getElementById('ov-address-suggest'),
            type: document.getElementById('ov-object-type'),
            floor: document.getElementById('ov-apartment-floor'),
            entrance: document.getElementById('ov-apartment-entrance'),
            apartment: document.getElementById('ov-apartment'),
            area: document.getElementById('ov-area'),
            lat: document.getElementById('ov-latitude'),
            lng: document.getElementById('ov-longitude'),
            mapEl: document.getElementById('ov-map'),
            aptWrap: document.getElementById('ov-apartment-fields'),
        };
    }

    function hideSuggest() {
        const { suggest } = els();
        if (suggest) {
            suggest.classList.add('hidden');
            suggest.innerHTML = '';
        }
    }

    function normalizeCityName(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/^г\.?\s*/i, '')
            .replace(/[ё]/g, 'е')
            .replace(/[-–—]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function aliasCity(name) {
        const key = normalizeCityName(name).replace(/\s+/g, '');
        const spaced = normalizeCityName(name);
        return cityAliases[spaced] || cityAliases[key] || name;
    }

    function matchCity(name) {
        const aliased = aliasCity(name);
        const n = normalizeCityName(aliased);
        if (!n) return '';
        const exact = cities.find((c) => normalizeCityName(c) === n);
        if (exact) return exact;
        return cities.find((c) => {
            const option = normalizeCityName(c);
            return option.includes(n) || n.includes(option);
        }) || '';
    }

    function resolveCityFromGeocoderRow(row) {
        const a = row?.address || {};
        const candidates = [
            a.city,
            a.town,
            a.village,
            a.municipality,
            a.city_district,
            a.county,
            a.state,
            a.region,
        ].map((v) => String(v || '').trim()).filter(Boolean);

        for (const candidate of candidates) {
            if (matchCity(candidate)) return candidate;
        }
        return candidates[0] || '';
    }

    function setCity(name) {
        const { city } = els();
        if (!city) return;
        const matched = matchCity(name);
        city.value = matched || (name ? 'other' : '');
    }

    function nominatimUrl(path, params) {
        const q = new URLSearchParams({ ...params, 'accept-language': nominatimLang });
        return `https://nominatim.openstreetmap.org/${path}?${q.toString()}`;
    }

    function updateMarker(lat, lng) {
        if (!map) return;
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            return;
        }
        if (!marker) {
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }
        map.setView([lat, lng], Math.max(map.getZoom(), 15));
    }

    function setCoords(lat, lng, { silent = false } = {}) {
        const { lat: latInput, lng: lngInput } = els();
        if (latInput) latInput.value = Number.isFinite(lat) ? String(lat) : '';
        if (lngInput) lngInput.value = Number.isFinite(lng) ? String(lng) : '';
        updateMarker(lat, lng);
        if (!silent && typeof onChange === 'function') onChange();
    }

    function clearCoords({ silent = false } = {}) {
        setCoords(NaN, NaN, { silent });
    }

    function syncApartmentVisibility() {
        const { type, aptWrap } = els();
        if (!aptWrap) return;
        aptWrap.classList.toggle('hidden', (type?.value || '') !== 'apartment');
    }

    async function searchSuggestions(query) {
        const { city, suggest, address } = els();
        if (!suggest || !address) return;
        if (searchAbort) searchAbort.abort();
        searchAbort = new AbortController();
        const cityPart = city?.value && city.value !== 'other' ? `, ${city.value}` : '';
        const q = `${query}${cityPart}, Kazakhstan`;
        const url = nominatimUrl('search', {
            format: 'json',
            addressdetails: '1',
            limit: '6',
            countrycodes: 'kz',
            q,
        });
        const r = await fetch(url, { signal: searchAbort.signal, headers: { Accept: 'application/json' } });
        const rows = await r.json().catch(() => []);
        if (!Array.isArray(rows) || !rows.length) {
            hideSuggest();
            return;
        }
        lastRows = rows;
        suggest.innerHTML = rows.map((row, idx) => {
            const title = String(row.display_name || row.name || '').slice(0, 255)
                .replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            return `<button type="button" class="address-suggest-item" data-idx="${idx}">${title}</button>`;
        }).join('');
        suggest.classList.remove('hidden');
        suggest.querySelectorAll('.address-suggest-item').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const row = lastRows[Number(btn.dataset.idx)];
                if (!row) return;
                applyPick(parseFloat(row.lat), parseFloat(row.lon), String(row.display_name || row.name || ''), row);
            });
        });
    }

    function applyPick(lat, lon, displayName, row = null) {
        const { address } = els();
        if (address) address.value = displayName.slice(0, 500);
        const cityName = resolveCityFromGeocoderRow(row);
        if (cityName) setCity(cityName);
        setCoords(lat, lon);
        hideSuggest();
    }

    async function reverseFill(lat, lng) {
        if (reverseAbort) reverseAbort.abort();
        reverseAbort = new AbortController();
        const url = nominatimUrl('reverse', {
            format: 'json',
            lat: String(lat),
            lon: String(lng),
            zoom: '18',
            addressdetails: '1',
        });
        const r = await fetch(url, { signal: reverseAbort.signal, headers: { Accept: 'application/json' } });
        const data = await r.json().catch(() => ({}));
        if (data?.display_name) {
            const { address } = els();
            if (address) address.value = String(data.display_name).slice(0, 500);
            const cityName = resolveCityFromGeocoderRow(data);
            if (cityName) setCity(cityName);
            if (typeof onChange === 'function') onChange();
        }
    }

    async function centerToCity(cityName) {
        if (!map) return;
        const matched = matchCity(cityName);
        if (!matched) {
            map.setView(defaultCenter, defaultZoom);
            return;
        }
        const url = nominatimUrl('search', {
            format: 'json',
            addressdetails: '1',
            limit: '1',
            countrycodes: 'kz',
            q: `${matched}, Kazakhstan`,
        });
        const r = await fetch(url, { headers: { Accept: 'application/json' } });
        const rows = await r.json().catch(() => []);
        const row = Array.isArray(rows) ? rows[0] : null;
        if (!row) return;
        const lat = parseFloat(row.lat);
        const lon = parseFloat(row.lon);
        if (Number.isFinite(lat) && Number.isFinite(lon)) {
            map.setView([lat, lon], 12);
        }
    }

    function ensureMap() {
        const { mapEl } = els();
        if (!mapEl || typeof L === 'undefined') return;
        if (map) {
            setTimeout(() => map.invalidateSize(), 50);
            return;
        }
        map = L.map(mapEl, { scrollWheelZoom: true }).setView(defaultCenter, defaultZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            setCoords(lat, lng);
            reverseFill(lat, lng).catch(() => {});
        });
        setTimeout(() => map.invalidateSize(), 80);
    }

    function bind() {
        if (ready) return;
        ready = true;
        const { address, city, type } = els();
        address?.addEventListener('input', () => {
            clearCoords({ silent: true });
            if (typeof onChange === 'function') onChange();
            const q = address.value.trim();
            clearTimeout(suggestTimer);
            if (q.length < 3) {
                hideSuggest();
                return;
            }
            suggestTimer = setTimeout(() => searchSuggestions(q).catch(() => hideSuggest()), 350);
        });
        address?.addEventListener('blur', () => setTimeout(hideSuggest, 150));
        city?.addEventListener('change', () => {
            clearCoords();
            centerToCity(city.value).catch(() => {});
            if (typeof onChange === 'function') onChange();
        });
        type?.addEventListener('change', () => {
            syncApartmentVisibility();
            if (typeof onChange === 'function') onChange();
        });
        ['ov-apartment-floor', 'ov-apartment-entrance', 'ov-apartment', 'ov-area'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', () => {
                if (typeof onChange === 'function') onChange();
            });
        });
    }

    function read() {
        const e = els();
        return {
            city: e.city?.value || null,
            object_type: e.type?.value || null,
            object_address: e.address?.value?.trim() || null,
            apartment_floor: e.floor?.value || null,
            apartment_entrance: e.entrance?.value || null,
            apartment: e.apartment?.value || null,
            area: e.area?.value || null,
            latitude: e.lat?.value || null,
            longitude: e.lng?.value || null,
        };
    }

    function apply(p) {
        const e = els();
        if (e.city) e.city.value = p?.city || p?.object_city || '';
        if (e.type) e.type.value = p?.object_type || '';
        if (e.address) e.address.value = p?.object_address_field || p?.object_address || '';
        if (e.floor) e.floor.value = p?.apartment_floor || '';
        if (e.entrance) e.entrance.value = p?.apartment_entrance || '';
        if (e.apartment) e.apartment.value = p?.apartment || '';
        if (e.area) e.area.value = p?.area ?? '';
        syncApartmentVisibility();
        const lat = p?.latitude != null && p?.latitude !== '' ? parseFloat(p.latitude) : NaN;
        const lng = p?.longitude != null && p?.longitude !== '' ? parseFloat(p.longitude) : NaN;
        ensureMap();
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            setCoords(lat, lng, { silent: true });
        } else {
            clearCoords({ silent: true });
            if (e.city?.value) centerToCity(e.city.value).catch(() => {});
        }
        hideSuggest();
    }

    function init(opts = {}) {
        cities = Array.isArray(opts.cities) ? opts.cities : [];
        onChange = typeof opts.onChange === 'function' ? opts.onChange : null;
        bind();
        ensureMap();
        syncApartmentVisibility();
    }

    function onModalOpened() {
        ensureMap();
        setTimeout(() => map?.invalidateSize(), 120);
    }

    return { init, read, apply, onModalOpened, ensureMap };
})();
</script>
