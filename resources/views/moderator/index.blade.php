@extends('layouts.dashboard')

@section('title', __('moderation.moderator_cabinet'))

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('moderation.moderator_cabinet') }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('moderation.queue_hint') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Suppliers queue -->
        <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.suppliers_queue') }}</h2>
                <span class="px-2 py-1 rounded-lg text-xs bg-[#f1f5f9] dark:bg-[#0a0a0a] text-[#f59e0b]">
                    {{ count($pendingSuppliers) }}
                </span>
            </div>

            @if($pendingSuppliers->isEmpty())
                <div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.empty') }}</div>
            @else
                <div class="space-y-3">
                    @foreach($pendingSuppliers as $s)
                        <div class="p-4 border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $s->name }}</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">
                                        {{ $s->city ?? '' }} {{ $s->address ?? '' }}
                                    </div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">
                                        {{ __('moderation.designer') }}: {{ $s->createdBy?->name ?? '-' }}
                                    </div>
                                </div>
                                <a href="{{ route('moderator.suppliers.show', $s->id) }}" class="px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors text-sm whitespace-nowrap">
                                    {{ __('moderation.review') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Objects (duplicate apartment) queue -->
        <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.objects_queue') }}</h2>
                <span class="px-2 py-1 rounded-lg text-xs bg-[#f1f5f9] dark:bg-[#0a0a0a] text-[#f59e0b]">
                    {{ count($pendingObjects) }}
                </span>
            </div>

            @if($pendingObjects->isEmpty())
                <div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('moderation.empty') }}</div>
            @else
                <div class="space-y-3">
                    @foreach($pendingObjects as $o)
                        @php
                            $addr = trim(($o->city ?? '') . ' ' . ($o->address ?? '') . ' ' . ($o->apartment ?? ''));
                        @endphp
                        <div class="p-4 border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $addr ?: '#' . $o->id }}</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">
                                        {{ __('moderation.duplicate_designer_requesting') }}: {{ $o->user?->name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">
                                        {{ __('moderation.duplicate_designer_existing') }}: {{ $o->moderationDuplicateOf?->user?->name ?? '-' }}
                                    </div>
                                </div>
                                <a href="{{ route('moderator.objects.show', $o->id) }}" class="px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors text-sm whitespace-nowrap">
                                    {{ __('moderation.review') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

