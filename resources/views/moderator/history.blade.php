@extends('layouts.dashboard')

@section('title', __('moderation.history_title'))

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('moderation.history_title') }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('moderation.history_hint') }}</p>
        </div>
        <a href="{{ route('moderator.index') }}"
            class="px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] text-sm transition-colors">
            {{ __('moderation.queue_link') }}
        </a>
    </div>

    <form method="get" action="{{ route('moderator.history') }}"
        class="mb-6 flex flex-col lg:flex-row flex-wrap gap-3 items-stretch lg:items-end bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-4">
        <div class="flex flex-col gap-1 min-w-[140px]">
            <label class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.filter_type') }}</label>
            <select name="type"
                class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-sm">
                <option value="all" @selected($filters['type'] === 'all')>{{ __('moderation.filter_type_all') }}</option>
                <option value="suppliers" @selected($filters['type'] === 'suppliers')>{{ __('moderation.filter_type_suppliers') }}</option>
                <option value="objects" @selected($filters['type'] === 'objects')>{{ __('moderation.filter_type_objects') }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
            <label class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.search') }}</label>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('moderation.search_placeholder') }}"
                class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-sm w-full">
        </div>
        <div class="flex flex-col gap-1 min-w-[140px]">
            <label class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.filter_status') }}</label>
            <select name="status"
                class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-sm">
                <option value="all" @selected($filters['status'] === 'all')>{{ __('moderation.filter_status_all') }}</option>
                <option value="approved" @selected($filters['status'] === 'approved')>{{ __('moderation.approved') }}</option>
                <option value="rejected" @selected($filters['status'] === 'rejected')>{{ __('moderation.rejected') }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-1 min-w-[200px]">
            <label class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.sort_label') }}</label>
            <select name="sort"
                class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-sm">
                <option value="reviewed_desc" @selected($filters['sort'] === 'reviewed_desc')>{{ __('moderation.sort_reviewed_desc') }}</option>
                <option value="reviewed_asc" @selected($filters['sort'] === 'reviewed_asc')>{{ __('moderation.sort_reviewed_asc') }}</option>
                <option value="type_asc" @selected($filters['sort'] === 'type_asc')>{{ __('moderation.sort_type_asc') }}</option>
                <option value="type_desc" @selected($filters['sort'] === 'type_desc')>{{ __('moderation.sort_type_desc') }}</option>
                <option value="status_asc" @selected($filters['sort'] === 'status_asc')>{{ __('moderation.sort_status_asc') }}</option>
                <option value="status_desc" @selected($filters['sort'] === 'status_desc')>{{ __('moderation.sort_status_desc') }}</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">
                {{ __('moderation.apply_filters') }}
            </button>
            <a href="{{ route('moderator.history') }}"
                class="px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b]">
                {{ __('moderation.reset_filters') }}
            </a>
        </div>
    </form>

    @if (session('status'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-500/30">
            {{ session('status') }}
        </div>
    @endif

    <div class="hidden md:block overflow-x-auto bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg">
        <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase text-[#64748b] dark:text-[#A1A09A] border-b border-[#e2e8f0] dark:border-[#3E3E3A]">
                <tr>
                    <th class="px-4 py-3">{{ __('moderation.col_type') }}</th>
                    <th class="px-4 py-3">{{ __('moderation.col_record') }}</th>
                    <th class="px-4 py-3">{{ __('moderation.designer') }}</th>
                    <th class="px-4 py-3">{{ __('moderation.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('moderation.col_reviewed') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]">
                @forelse ($items as $row)
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($row['kind'] === 'supplier')
                                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">{{ __('moderation.kind_supplier') }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-sky-50 text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">{{ __('moderation.kind_object') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $row['label'] }}</div>
                            @if (!empty($row['line2']))
                                <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ $row['line2'] }}</div>
                            @endif
                            @if ($row['kind'] === 'object' && !empty($row['trashed']))
                                <div class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ __('moderation.object_soft_deleted') }}</div>
                            @endif
                            <div class="mt-1 flex flex-wrap gap-2">
                                @if ($row['kind'] === 'supplier')
                                    <a href="{{ route('moderator.suppliers.show', $row['id']) }}"
                                        class="text-xs text-[#f59e0b] hover:underline">{{ __('moderation.open_card') }}</a>
                                @else
                                    <a href="{{ route('moderator.objects.show', $row['id']) }}"
                                        class="text-xs text-[#f59e0b] hover:underline">{{ __('moderation.open_card') }}</a>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-[#64748b] dark:text-[#A1A09A]">{{ $row['designer'] ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if ($row['status'] === 'approved') bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300
                                @else bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300 @endif">
                                {{ __('moderation.' . $row['status']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-[#64748b] dark:text-[#A1A09A] whitespace-nowrap">
                            {{ $row['reviewed_at'] ? $row['reviewed_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.history_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden space-y-4">
        @forelse ($items as $row)
            <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-4">
                <div class="flex items-start justify-between gap-2 mb-2">
                    @if ($row['kind'] === 'supplier')
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">{{ __('moderation.kind_supplier') }}</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded text-xs bg-sky-50 text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">{{ __('moderation.kind_object') }}</span>
                    @endif
                    <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ $row['reviewed_at'] ? $row['reviewed_at']->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}</span>
                </div>
                <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $row['label'] }}</div>
                @if ($row['kind'] === 'object' && !empty($row['trashed']))
                    <div class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ __('moderation.object_soft_deleted') }}</div>
                @endif
                <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('moderation.designer') }}: {{ $row['designer'] ?? '—' }}</div>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                        @if ($row['status'] === 'approved') bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300
                        @else bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300 @endif">
                        {{ __('moderation.' . $row['status']) }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if ($row['kind'] === 'supplier')
                        <a href="{{ route('moderator.suppliers.show', $row['id']) }}"
                            class="text-xs text-[#f59e0b] hover:underline">{{ __('moderation.open_card') }}</a>
                    @else
                        <a href="{{ route('moderator.objects.show', $row['id']) }}"
                            class="text-xs text-[#f59e0b] hover:underline">{{ __('moderation.open_card') }}</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.history_empty') }}</div>
        @endforelse
    </div>

    @if ($items->hasPages())
        <div class="mt-6">
            {{ $items->links() }}
        </div>
    @endif
@endsection
