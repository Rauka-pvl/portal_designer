@php
    $name = trim((string) ($name ?? ''));
    $initials = collect(preg_split('/\s+/u', $name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $initials = $initials !== '' ? $initials : '?';
    $size = $size ?? 'w-10 h-10';
    $textSize = $textSize ?? 'text-sm';
@endphp
<div class="{{ $size }} rounded-full bg-[#f59e0b]/20 text-[#f59e0b] flex items-center justify-center font-semibold {{ $textSize }} shrink-0" aria-hidden="true">
    {{ $initials }}
</div>
