@extends('layouts.dashboard')

@section('title', __('objects.object_passport') . ' #' . $object->id)

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #7c8799;
            border-radius: 12px;
            padding: 1.25rem;
        }
        .dark .panel { background: #161615; border-color: #3E3E3A; }
        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #7c8799;
            background: #ffffff;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 500;
        }
        .btn:hover { border-color: #f59e0b; color: #f59e0b; }
        .dark .btn { background: #0a0a0a; border-color: #3E3E3A; color: #A1A09A; }
    </style>
@endpush

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('moderation.objects_queue') }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('objects.object_passport') }} #{{ $object->id }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('moderator.index') }}" class="btn">{{ __('suppliers.close') }}</a>
        </div>
    </div>

    <div class="panel space-y-6">
        @php
            $status = (string) ($object->moderation_status ?? 'pending');
            $existing = $existingObject;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('moderation.duplicate_designer_requesting') }}</div>
                <div class="text-sm text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ $object->user?->name ?? '-' }}</div>
            </div>
            <div class="p-4 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('moderation.duplicate_designer_existing') }}</div>
                <div class="text-sm text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ $existing?->user?->name ?? '-' }}</div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                @if($status === 'approved') bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                @elseif($status === 'rejected') bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300
                @else bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200
                @endif">
                {{ __('moderation.' . $status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</div>
                <input disabled value="{{ $object->city ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</div>
                <input disabled value="{{ $object->address ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.type') }}</div>
                <input disabled value="{{ $object->type ?? '-' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.apartment_number') }}</div>
                <input disabled value="{{ $object->apartment ?? '-' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.apartment_entrance') }}</div>
                <input disabled value="{{ $object->apartment_entrance ?? '-' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.apartment_floor') }}</div>
                <input disabled value="{{ $object->apartment_floor ?? '-' }}" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
        </div>

        <hr class="border-[#7c8799] dark:border-[#3E3E3A]">

        @if (($object->moderation_status ?? '') === 'pending' && $object->moderation_duplicate_of_object_id)
            {{-- Решение по дубликату: только «одобрить» / «отклонить» (варианта «не требуется» нет). --}}
            <form id="moderation-form" method="POST" action="{{ route('moderator.objects.decision', $object->id) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('moderation.decision') }}</div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('moderation.decision_duplicate_object_hint') }}</p>
                        <select name="decision" required class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            <option value="approved">{{ __('moderation.approved') }}</option>
                            <option value="rejected">{{ __('moderation.rejected') }}</option>
                        </select>
                    </div>
                    <div>
                        <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('moderation.comment') }}</div>
                        <textarea name="comment" rows="4" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ old('comment') }}</textarea>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn">{{ __('suppliers.save') }}</button>
                </div>
            </form>
        @else
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.object_decision_done_hint') }}</p>
            @if (Route::has('moderator.history'))
                <a href="{{ route('moderator.history', ['type' => 'objects']) }}" class="inline-block mt-3 text-sm text-[#f59e0b] hover:underline">{{ __('moderation.history_title') }}</a>
            @endif
        @endif
    </div>
@endsection
