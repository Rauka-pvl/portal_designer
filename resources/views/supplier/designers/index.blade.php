@extends('layouts.supplier')

@section('title', __('designers.title'))
@section('header_title', __('designers.title'))

@push('styles')
    <style>
        .tab-btn {
            background: #ffffff;
            border: 1px solid #7c8799;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            color: #64748b;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .tab-btn:hover { border-color: #f59e0b; color: #f59e0b; }
        .tab-btn.active { background: #f1f5f9; border-color: #f59e0b; color: #f59e0b; }
        .dark .tab-btn { background: #161615; border-color: #3E3E3A; color: #A1A09A; }
        .dark .tab-btn.active { background: #0a0a0a; border-color: #f59e0b; color: #f59e0b; }
    </style>
@endpush

@section('content')
    <div class="mb-6">
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('designers.subtitle') }}</p>
    </div>

    <!-- Вкладки -->
    <div class="mb-6 flex gap-2">
        <button type="button" data-tab="table" class="tab-btn active">{{ __('suppliers.table') }}</button>
        <button type="button" data-tab="list" class="tab-btn">{{ __('suppliers.list') }}</button>
    </div>

    <!-- Поиск и фильтр -->
    <div class="mb-6 flex flex-col md:flex-row gap-3 md:items-center">
        <input type="text" id="designers-search" placeholder="{{ __('suppliers.search_placeholder') }}"
            class="w-full md:max-w-md px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
        <div class="flex gap-2">
            <button type="button" data-filter="all" class="filter-btn active">{{ __('designers.filter_all') }}</button>
            <button type="button" data-filter="worked" class="filter-btn">{{ __('designers.filter_worked') }}</button>
        </div>
    </div>

    @php
        $renderInitials = function ($name) {
            $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
            return $initials !== '' ? $initials : 'D';
        };
    @endphp

    <!-- Таблица -->
    <div id="table-view" class="tab-content">
        <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('designers.designer') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('designers.city') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('designers.specialization') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('reviews.rating_label') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7c8799] dark:divide-[#3E3E3A]">
                        @forelse ($designers as $designer)
                            <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-designer-row
                                data-worked="{{ $designer['worked_with'] ? '1' : '0' }}"
                                data-search="{{ mb_strtolower($designer['name'].' '.$designer['city'].' '.$designer['specialization']) }}">
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center text-xs font-semibold shrink-0">{{ $renderInitials($designer['name']) }}</div>
                                        <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ $designer['name'] ?: '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $designer['city'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $designer['specialization'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">@include('partials.stars', ['value' => $designer['rating']['average'] ?? 0, 'count' => $designer['rating']['count'] ?? 0])</td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('supplier.designers.show', $designer['id']) }}"
                                        class="p-1.5 inline-flex rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                        title="{{ __('suppliers.view') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('designers.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Карточки -->
    <div id="list-view" class="tab-content hidden">
        @if ($designers->isEmpty())
            <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-[#64748b] dark:text-[#A1A09A]">
                {{ __('designers.empty') }}
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($designers as $designer)
                    <a href="{{ route('supplier.designers.show', $designer['id']) }}"
                        class="group rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 transition-colors hover:border-[#f59e0b] block" data-designer-card
                        data-worked="{{ $designer['worked_with'] ? '1' : '0' }}"
                        data-search="{{ mb_strtolower($designer['name'].' '.$designer['city'].' '.$designer['specialization']) }}">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold shrink-0">{{ $renderInitials($designer['name']) }}</div>
                            <div class="min-w-0">
                                <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC] truncate group-hover:text-[#f59e0b] transition-colors">{{ $designer['name'] ?: '—' }}</div>
                                <div class="mt-1">@include('partials.stars', ['value' => $designer['rating']['average'] ?? 0, 'count' => $designer['rating']['count'] ?? 0])</div>
                            </div>
                        </div>
                        <div class="mt-4 space-y-1.5 text-sm text-[#64748b] dark:text-[#A1A09A]">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#94a3b8] dark:text-[#71717a]">{{ __('designers.city') }}:</span>
                                <span class="text-[#0f172a] dark:text-[#EDEDEC]">{{ $designer['city'] ?: '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[#94a3b8] dark:text-[#71717a]">{{ __('designers.specialization') }}:</span>
                                <span class="text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $designer['specialization'] ?: '—' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-tab]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('[data-tab]').forEach((b) => b.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach((c) => c.classList.add('hidden'));
                    document.getElementById(this.dataset.tab + '-view').classList.remove('hidden');
                });
            });

            const search = document.getElementById('designers-search');
            let activeFilter = 'all';

            function applyFilters() {
                const q = (search?.value || '').trim().toLowerCase();
                document.querySelectorAll('[data-designer-row], [data-designer-card]').forEach((el) => {
                    const hay = el.getAttribute('data-search') || '';
                    const worked = el.getAttribute('data-worked') === '1';
                    const matchSearch = !q || hay.includes(q);
                    const matchFilter = activeFilter === 'all' || worked;
                    el.style.display = (matchSearch && matchFilter) ? '' : 'none';
                });
            }

            search?.addEventListener('input', applyFilters);

            document.querySelectorAll('[data-filter]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('[data-filter]').forEach((b) => b.classList.remove('active'));
                    this.classList.add('active');
                    activeFilter = this.dataset.filter;
                    applyFilters();
                });
            });
        })();
    </script>
@endsection
