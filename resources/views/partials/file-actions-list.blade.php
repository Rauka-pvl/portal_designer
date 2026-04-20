@php
    $items = [];
    foreach (($filePaths ?? []) as $path) {
        if (! is_string($path) || trim($path) === '') {
            continue;
        }

        $items[] = [
            'path' => $path,
            'name' => basename($path),
            'url' => asset('storage/' . ltrim($path, '/')),
        ];
    }

    $deleteCallback = $deleteCallback ?? null;
    $deleteEntityId = $deleteEntityId ?? null;
@endphp

@if (empty($items))
    <p class="text-xs mt-3 text-[#64748b] dark:text-[#A1A09A]">-</p>
@else
    <div class="mt-3 space-y-2">
        @foreach ($items as $index => $item)
            <div class="flex items-center gap-3 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white/60 dark:bg-[#0a0a0a] px-3 py-2">
                <div class="shrink-0 text-[#64748b] dark:text-[#A1A09A]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7V6a2 2 0 012-2h6a2 2 0 012 2v1m-9 4h8m-8 4h5m-7 5h10a2 2 0 002-2V7H6v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $item['name'] }}</div>
                </div>
                <div class="flex items-center gap-1.5">
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                        title="{{ __('projects.view') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </a>
                    <a href="{{ $item['url'] }}" download="{{ $item['name'] }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                        title="{{ __('objects.download') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
                        </svg>
                    </a>
                    @if ($deleteCallback && $deleteEntityId !== null)
                        <button type="button"
                            onclick="{{ $deleteCallback }}({{ $deleteEntityId }}, {{ $index }})"
                            class="edit-only-control hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors"
                            title="{{ __('objects.delete_file') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
