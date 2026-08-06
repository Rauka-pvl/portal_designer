@extends('layouts.dashboard')

@section('title', __('support.ticket_number', ['number' => $ticket->number]))
@section('header_title', __('support.ticket_number', ['number' => $ticket->number]))

@push('styles')
    <style>
        .sup-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .sup-badge-new { background:#fef3c7; color:#92400e; }
        .sup-badge-in_progress { background:#dbeafe; color:#1e40af; }
        .sup-badge-waiting_for_user { background:#ede9fe; color:#5b21b6; }
        .sup-badge-resolved { background:#dcfce7; color:#166534; }
        .sup-badge-closed { background:#e5e7eb; color:#4b5563; }
        .sup-badge-priority { background:#fee2e2; color:#b91c1c; }
        .msg-bubble { max-width:85%; border-radius:14px; padding:12px 16px; }
        .msg-own { margin-left:auto; background:#fffbeb; border:1px solid #fde68a; }
        .msg-user { margin-right:auto; background:#fff; border:1px solid #e2e8f0; }
        .msg-system { margin:0 auto; background:#f8fafc; border:1px dashed #cbd5e1; color:#64748b; font-size:12px; text-align:center; max-width:100%; }
        .info-row { display:flex; justify-content:space-between; gap:10px; padding:7px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .info-row dt { color:#94a3b8; }
        .info-row dd { color:#1b1b18; text-align:right; font-weight:500; word-break:break-word; }
    </style>
@endpush

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-6">
        <a href="{{ route('admin.support.index') }}" class="inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('support.back_to_list') }}
        </a>

        <div class="grid lg:grid-cols-[1fr_300px] gap-6 items-start">
            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="text-xs text-[#94a3b8] font-mono">{{ $ticket->number }}</span>
                    <span class="sup-badge sup-badge-{{ $ticket->statusEnum()->value }}">{{ $ticket->statusEnum()->label() }}</span>
                    @if ($ticket->is_priority)
                        <span class="sup-badge sup-badge-priority">{{ __('support.priority_badge') }}</span>
                    @endif
                </div>
                <h1 class="text-xl font-semibold text-[#1b1b18] mb-5">{{ $ticket->subject }}</h1>

                <div class="space-y-4 mb-6">
                    @foreach ($ticket->messages as $message)
                        @if ($message->is_system)
                            <div class="msg-bubble msg-system">{{ $message->message }}</div>
                        @else
                            @php $fromStaff = $message->sender_role === 'admin'; @endphp
                            <div class="msg-bubble {{ $fromStaff ? 'msg-own' : 'msg-user' }}">
                                <div class="flex items-center gap-2 text-xs text-[#706f6c] mb-1">
                                    <span class="font-medium text-[#1b1b18]">{{ $message->sender?->name ?? '—' }}</span>
                                    <span>·</span>
                                    <span>{{ $fromStaff ? __('support.role_admin') : __('support.role_user') }}</span>
                                    <span>·</span>
                                    <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                @if ($message->message)
                                    <div class="text-sm text-[#1b1b18] whitespace-pre-wrap break-words">{{ $message->message }}</div>
                                @endif
                                @if ($message->attachments->isNotEmpty())
                                    <div class="mt-2 space-y-1.5">
                                        @foreach ($message->attachments as $attachment)
                                            @if ($attachment->isImage())
                                                <a href="{{ route('admin.support.attachments.download', $attachment) }}" target="_blank" rel="noopener">
                                                    <img src="{{ route('admin.support.attachments.download', ['attachment' => $attachment, 'preview' => 1]) }}"
                                                        alt="{{ $attachment->original_name }}"
                                                        class="max-h-40 rounded-lg border border-[#e2e8f0]">
                                                </a>
                                            @else
                                                <a href="{{ route('admin.support.attachments.download', $attachment) }}"
                                                    class="inline-flex items-center gap-2 text-xs px-3 py-2 rounded-lg border border-[#e2e8f0] bg-white hover:border-[#f59e0b]">
                                                    <svg class="w-4 h-4 text-[#94a3b8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                                    <span class="font-medium">{{ $attachment->original_name }}</span>
                                                    <span class="text-[#94a3b8]">{{ $attachment->sizeLabel() }}</span>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($ticket->isOpen())
                    <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data"
                        class="bg-white border border-[#e2e8f0] rounded-2xl p-4 space-y-3">
                        @csrf
                        <textarea name="message" rows="3" maxlength="5000" placeholder="{{ __('support.reply_placeholder') }}"
                            class="w-full border border-[#e2e8f0] rounded-lg px-3.5 py-2.5 text-sm bg-white focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b] outline-none resize-y">{{ old('message') }}</textarea>
                        @error('message')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <input type="file" name="attachments[]" multiple
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                                class="text-xs text-[#706f6c] file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-[#f8fafc] hover:file:bg-[#f1f5f9]">
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706]">
                                {{ __('support.reply_send') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center text-sm text-[#706f6c] border border-dashed border-[#e2e8f0] rounded-2xl py-4">
                        {{ __('support.closed_notice') }}
                    </div>
                @endif
            </div>

            <aside class="bg-white border border-[#e2e8f0] rounded-2xl p-4 lg:sticky lg:top-4">
                <dl>
                    <div class="info-row"><dt>{{ __('support.author_label') }}</dt><dd>{{ $ticket->author?->name ?? '—' }}</dd></div>
                    <div class="info-row"><dt>Email</dt><dd>{{ $ticket->author?->email ?? '—' }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.team_label') }}</dt><dd>{{ $ticket->team?->name ?? '—' }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.plan_label') }}</dt><dd>{{ $ticket->team?->owner?->subscription_plan ?? $ticket->author?->subscription_plan ?? '—' }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.plan_at_creation') }}</dt><dd>{{ $ticket->plan_code_snapshot ?? '—' }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.priority_badge') }}</dt><dd>{{ $ticket->is_priority ? __('support.priority_badge') : '—' }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.category') }}</dt><dd>{{ $ticket->category->label() }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.created_at_label') }}</dt><dd>{{ $ticket->created_at->format('d.m.Y H:i') }}</dd></div>
                    <div class="info-row"><dt>{{ __('support.last_message') }}</dt><dd>{{ $ticket->last_message_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="mt-4 space-y-2">
                    @csrf
                    @method('PATCH')
                    <label class="block text-xs text-[#94a3b8]">{{ __('support.change_status') }}</label>
                    <select name="status" class="w-full border border-[#e2e8f0] rounded-lg px-2.5 py-2 text-sm bg-white">
                        @foreach ($statusOptions as $st)
                            <option value="{{ $st->value }}" @selected($ticket->statusEnum() === $st)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b] text-sm font-medium hover:bg-[#fffbeb]">
                        {{ __('support.apply') }}
                    </button>
                </form>
            </aside>
        </div>
    </div>
@endsection
