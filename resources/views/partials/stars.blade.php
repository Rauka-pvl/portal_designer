@php
    $value = (float) ($value ?? 0);
    $count = $count ?? null;
    $rounded = (int) round($value);
    $size = $size ?? 'w-3.5 h-3.5';
@endphp
<span class="inline-flex items-center gap-1.5 align-middle">
    <span class="inline-flex items-center text-[#f59e0b]">
        @for ($i = 1; $i <= 5; $i++)
            <svg class="{{ $size }} {{ $i <= $rounded ? '' : 'opacity-30' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/></svg>
        @endfor
    </span>
    <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
        {{ $value > 0 ? number_format($value, 1) : '—' }}@if ($count) <span class="opacity-70">({{ $count }})</span>@endif
    </span>
</span>
