@extends('layouts.dashboard')

@section('title', __('support.admin_title'))
@section('header_title', __('support.admin_title'))

@push('styles')
    <style>
        .sup-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; }
        .sup-badge-new { background:#fef3c7; color:#92400e; }
        .sup-badge-in_progress { background:#dbeafe; color:#1e40af; }
        .sup-badge-waiting_for_user { background:#ede9fe; color:#5b21b6; }
        .sup-badge-resolved { background:#dcfce7; color:#166534; }
        .sup-badge-closed { background:#e5e7eb; color:#4b5563; }
        .sup-badge-priority { background:#fee2e2; color:#b91c1c; }
        .sup-table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; font-weight:600; padding:10px 12px; text-align:left; }
        .sup-table td { padding:12px; border-top:1px solid #f1f5f9; font-size:13px; vertical-align:middle; }
        .sup-table tr:hover td { background:#fafaf9; }
    </style>
@endpush

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ __('support.admin_title') }}</h1>
            <p class="text-sm text-[#706f6c] mt-1">{{ __('support.admin_subtitle') }}</p>
        </div>

        <form method="GET" class="mb-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 items-end">
            <div>
                <label class="block text-xs text-[#94a3b8] mb-1">{{ __('support.filter_status') }}</label>
                <select name="status" class="w-full border border-[#e2e8f0] rounded-lg px-2.5 py-2 text-sm bg-white">
                    <option value="">{{ __('support.all') }}</option>
                    @foreach ($statusOptions as $st)
                        <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-[#94a3b8] mb-1">{{ __('support.filter_category') }}</label>
                <select name="category" class="w-full border border-[#e2e8f0] rounded-lg px-2.5 py-2 text-sm bg-white">
                    <option value="">{{ __('support.all') }}</option>
                    @foreach ($categoryOptions as $cat)
                        <option value="{{ $cat['value'] }}" @selected(($filters['category'] ?? '') === $cat['value'])>{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-[#94a3b8] mb-1">{{ __('support.filter_plan') }}</label>
                <select name="plan" class="w-full border border-[#e2e8f0] rounded-lg px-2.5 py-2 text-sm bg-white">
                    <option value="">{{ __('support.all') }}</option>
                    @foreach ($planOptions as $planKey)
                        <option value="{{ $planKey }}" @selected(($filters['plan'] ?? '') === $planKey)>{{ $planKey }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-[#94a3b8] mb-1">{{ __('support.filter_search') }}</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    class="w-full border border-[#e2e8f0] rounded-lg px-2.5 py-2 text-sm bg-white">
            </div>
            <label class="flex items-center gap-2 text-sm text-[#1b1b18] pb-2">
                <input type="checkbox" name="priority" value="1" @checked(! empty($filters['priority'])) class="rounded border-[#e2e8f0]">
                {{ __('support.filter_priority') }}
            </label>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706]">{{ __('support.filter_apply') }}</button>
                <a href="{{ route('admin.support.index') }}" class="px-4 py-2 rounded-lg border border-[#e2e8f0] text-sm text-[#706f6c] hover:bg-[#f8fafc]">{{ __('support.filter_reset') }}</a>
            </div>
        </form>

        @if ($tickets->isEmpty())
            <div class="text-center py-16 border border-dashed border-[#e2e8f0] rounded-2xl text-sm text-[#706f6c]">
                {{ __('support.empty_title') }}
            </div>
        @else
            <div class="bg-white rounded-2xl border border-[#e2e8f0] overflow-x-auto">
                <table class="sup-table w-full min-w-[760px]">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('support.subject') }}</th>
                            <th>{{ __('support.author_label') }}</th>
                            <th>{{ __('support.team_label') }}</th>
                            <th>{{ __('support.plan_label') }}</th>
                            <th>{{ __('support.filter_status') }}</th>
                            <th>{{ __('support.last_message') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('admin.support.show', $ticket) }}'">
                                <td class="font-mono text-xs text-[#706f6c]">{{ $ticket->number }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if ($ticket->is_priority)
                                            <span class="sup-badge sup-badge-priority">{{ __('support.priority_badge') }}</span>
                                        @endif
                                        <span class="font-medium text-[#1b1b18]">{{ \Illuminate\Support\Str::limit($ticket->subject, 48) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $ticket->author?->name ?? '—' }}</div>
                                    <div class="text-xs text-[#94a3b8]">{{ $ticket->author?->email }}</div>
                                </td>
                                <td>{{ $ticket->team?->name ?? '—' }}</td>
                                <td class="text-xs">{{ $ticket->plan_code_snapshot ?? '—' }}</td>
                                <td><span class="sup-badge sup-badge-{{ $ticket->statusEnum()->value }}">{{ $ticket->statusEnum()->label() }}</span></td>
                                <td class="text-xs text-[#706f6c]">{{ $ticket->last_message_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
