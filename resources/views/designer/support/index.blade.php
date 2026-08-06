@extends('layouts.dashboard')

@section('title', __('support.title'))
@section('header_title', __('support.title'))

@push('styles')
    <style>
        .sup-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; line-height:1.4; white-space:nowrap; }
        .sup-badge-new { background:#fef3c7; color:#92400e; }
        .sup-badge-in_progress { background:#dbeafe; color:#1e40af; }
        .sup-badge-waiting_for_user { background:#ede9fe; color:#5b21b6; }
        .sup-badge-resolved { background:#dcfce7; color:#166534; }
        .sup-badge-closed { background:#e5e7eb; color:#4b5563; }
        .sup-badge-priority { background:#fee2e2; color:#b91c1c; }
        .sup-row { display:flex; align-items:center; gap:14px; padding:14px 16px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; transition:border-color .15s, box-shadow .15s; }
        .sup-row:hover { border-color:#f59e0b; box-shadow:0 2px 10px rgba(0,0,0,.05); }
    </style>
@endpush

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ __('support.title') }}</h1>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">{{ __('support.subtitle') }}</p>
            </div>
            <a href="{{ route('support.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('support.new_ticket') }}
            </a>
        </div>

        <form method="GET" class="mb-4 flex items-center gap-2">
            <select name="status" onchange="this.form.submit()"
                class="border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm bg-white dark:bg-[#161615] dark:text-[#EDEDEC]">
                <option value="">{{ __('support.all') }}</option>
                @foreach (\App\Enums\SupportTicketStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected($statusFilter === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
        </form>

        @if ($tickets->isEmpty())
            <div class="text-center py-16 border border-dashed border-[#e2e8f0] rounded-2xl">
                <svg class="w-12 h-12 mx-auto text-[#cbd5e1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8m-8 4h5m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="mt-3 text-base font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ __('support.empty_title') }}</h3>
                <p class="mt-1 text-sm text-[#706f6c]">{{ __('support.empty_body') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($tickets as $ticket)
                    <a href="{{ route('support.show', $ticket) }}" class="sup-row">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs text-[#94a3b8] font-mono">{{ $ticket->number }}</span>
                                <span class="sup-badge sup-badge-{{ $ticket->statusEnum()->value }}">{{ $ticket->statusEnum()->label() }}</span>
                                @if ($ticket->is_priority)
                                    <span class="sup-badge sup-badge-priority">{{ __('support.priority_badge') }}</span>
                                @endif
                            </div>
                            <div class="mt-1 font-medium text-[#1b1b18] dark:text-[#EDEDEC] truncate">{{ $ticket->subject }}</div>
                            <div class="mt-0.5 text-xs text-[#706f6c]">
                                {{ $ticket->category->label() }} · {{ __('support.last_message') }}:
                                {{ $ticket->last_message_at?->format('d.m.Y H:i') ?? $ticket->created_at->format('d.m.Y H:i') }}
                                @if ((int) $ticket->created_by !== (int) auth()->id())
                                    · {{ $ticket->author?->name }}
                                @endif
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-[#cbd5e1] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
