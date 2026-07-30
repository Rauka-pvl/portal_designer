@php
    $cities = $cities ?? collect();
    $spheres = $spheres ?? collect();
    $brands = $brands ?? collect();
    $totalSeed = is_countable($suppliersData ?? null) ? count($suppliersData) : 0;
@endphp

<div class="crm-workspace" id="crm-suppliers-workspace" data-locale="{{ str_replace('_', '-', app()->getLocale()) }}">
    <div class="crm-toolbar" role="toolbar" aria-label="{{ __('suppliers.suppliers') }}">
        <div class="crm-toolbar-left">
            <div class="crm-view-switch" role="group" aria-label="{{ __('suppliers.view_mode') }}">
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="table"
                    aria-pressed="true" title="{{ __('suppliers.table') }}">{{ __('suppliers.table') }}</button>
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="cards"
                    aria-pressed="false" title="{{ __('suppliers.cards') }}">{{ __('suppliers.cards') }}</button>
            </div>
        </div>
        <div class="crm-toolbar-right">
            <div class="crm-toolbar-search-wrap">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position:absolute;left:0.7rem;pointer-events:none;color:var(--crm-muted)">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="suppliers-search" class="crm-input crm-toolbar-search"
                    placeholder="{{ __('suppliers.search_placeholder') }}"
                    aria-label="{{ __('suppliers.search') }}"
                    autocomplete="off" value="{{ request('search') }}">
                <button type="button" id="suppliers-search-clear" class="crm-search-clear" aria-label="{{ __('suppliers.clear_search') }}" title="{{ __('suppliers.clear_search') }}">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="crm-filters-wrap">
                <button type="button" id="suppliers-filters-btn" class="crm-btn crm-btn-secondary crm-btn-sm crm-filters-btn"
                    aria-expanded="false" aria-controls="suppliers-filters-panel" aria-haspopup="true">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                    </svg>
                    <span>{{ __('suppliers.filters') }}</span>
                    <span id="suppliers-filters-badge" class="crm-filters-badge" hidden>0</span>
                </button>
                <div id="suppliers-filters-backdrop" class="crm-filters-backdrop" hidden></div>
                <div id="suppliers-filters-panel" class="crm-filters-panel" role="dialog" aria-label="{{ __('suppliers.filters') }}" aria-hidden="true">
                    <label class="crm-label" for="suppliers-filter-type">{{ __('suppliers.filter_type') }}</label>
                    <select id="suppliers-filter-type" class="crm-input crm-select">
                        <option value="all">{{ __('suppliers.filter_all') }}</option>
                        <option value="recommended">{{ __('suppliers.filter_recommended') }}</option>
                        <option value="favorites">{{ __('suppliers.filter_favorites') }}</option>
                    </select>

                    <label class="crm-label" for="suppliers-filter-city">{{ __('suppliers.city_filter') }}</label>
                    <select id="suppliers-filter-city" class="crm-input crm-select">
                        <option value="">{{ __('suppliers.all_cities') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>

                    <label class="crm-label" for="suppliers-filter-sphere">{{ __('suppliers.sphere_filter') }}</label>
                    <select id="suppliers-filter-sphere" class="crm-input crm-select">
                        <option value="">{{ __('suppliers.all_spheres') }}</option>
                        @foreach ($spheres as $sphere)
                            @php
                                $sphereTranslation = is_string($sphere) ? __('supplier_spheres.' . $sphere) : '';
                                $sphereLabel = is_string($sphere) && $sphereTranslation !== 'supplier_spheres.' . $sphere
                                    ? $sphereTranslation
                                    : $sphere;
                            @endphp
                            <option value="{{ $sphere }}">{{ $sphereLabel }}</option>
                        @endforeach
                    </select>

                    <label class="crm-label" for="suppliers-filter-brand">{{ __('suppliers.brand_filter') }}</label>
                    <select id="suppliers-filter-brand" class="crm-input crm-select">
                        <option value="">{{ __('suppliers.all_brands') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                        @endforeach
                    </select>

                    <div class="crm-filters-actions">
                        <button type="button" id="suppliers-filters-reset" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('suppliers.reset_filters') }}</button>
                        <button type="button" id="suppliers-filters-apply" class="crm-btn crm-btn-primary crm-btn-sm">{{ __('suppliers.apply_filters') }}</button>
                    </div>
                </div>
            </div>

            <button type="button" id="add-supplier-btn" class="crm-btn crm-btn-primary crm-btn-sm">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                {{ __('suppliers.create_supplier') }}
            </button>
        </div>
    </div>

    <div id="suppliers-filter-chips" class="crm-filter-chips" aria-live="polite"></div>

    <div id="suppliers-table-panel" class="crm-suppliers-panel" role="region" aria-label="{{ __('suppliers.table') }}">
        <div class="overflow-x-auto">
            <table class="crm-table" id="suppliers-table">
                <thead>
                    <tr>
                        <th data-sort="name">{{ __('suppliers.supplier_col') }}</th>
                        <th data-sort="phone">{{ __('suppliers.contacts') }}</th>
                        <th data-sort="city" class="hidden md:table-cell">{{ __('suppliers.city') }}</th>
                        <th data-sort="sphere" class="hidden lg:table-cell">{{ __('suppliers.direction') }}</th>
                        <th>{{ __('suppliers.rating') }}</th>
                        <th>{{ __('moderation.status') }}</th>
                        <th>{{ __('suppliers.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="suppliers-table-body"></tbody>
            </table>
        </div>
        <div class="crm-suppliers-pagination" id="suppliers-pagination-table-wrap">
            <div class="text-xs text-[var(--crm-muted)]" id="suppliers-result-meta-table"></div>
            <div class="inline-flex items-center gap-2 flex-wrap justify-end">
                <label class="inline-flex items-center gap-2 text-xs text-[var(--crm-muted)]">
                    {{ __('objects.per_page') }}
                    <select id="suppliers-per-page" class="crm-input" style="width:auto;min-height:32px;height:32px;padding:0.2rem 0.5rem" aria-label="{{ __('objects.per_page') }}">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
                <div class="crm-suppliers-pagination-pages" id="suppliers-pagination-table"></div>
            </div>
        </div>
    </div>

    <div id="suppliers-cards-panel" class="crm-suppliers-panel is-hidden" role="region" aria-label="{{ __('suppliers.cards') }}">
        <div class="crm-suppliers-cards" id="suppliers-cards-body"></div>
        <div class="crm-suppliers-pagination" id="suppliers-pagination-cards-wrap">
            <div class="text-xs text-[var(--crm-muted)]" id="suppliers-result-meta-cards"></div>
            <div class="crm-suppliers-pagination-pages" id="suppliers-pagination-cards"></div>
        </div>
    </div>

    <div id="suppliers-empty" class="crm-suppliers-panel crm-suppliers-empty is-hidden" hidden>
        <h3>{{ __('suppliers.empty_title') }}</h3>
        <p id="suppliers-empty-body">{{ __('suppliers.empty_body') }}</p>
        <div class="crm-suppliers-empty-actions">
            <button type="button" id="suppliers-empty-create" class="crm-btn crm-btn-primary crm-btn-sm">{{ __('suppliers.create_supplier') }}</button>
            <button type="button" id="suppliers-empty-reset" class="crm-btn crm-btn-secondary crm-btn-sm is-hidden" hidden>{{ __('suppliers.reset_all_filters') }}</button>
        </div>
    </div>
</div>

<script>
window.__suppliersHeaderCountInit = {{ (int) $totalSeed }};
</script>
