{{--
  Unified Back control.
  Props:
    - fallback (string, required): parent URL if history / ?from= unavailable
    - label (string): button text
    - class (string): CSS classes
    - variant: 'link' | 'btn'  (btn keeps page-local .btn styles)
    - icon (bool): show chevron, default true
--}}
@php
    use App\Support\BackNavigation;

    $fallback = $fallback ?? route('dashboard');
    $label = $label ?? __('nav.back');
    $variant = $variant ?? 'link';
    $showIcon = $icon ?? true;
    $href = BackNavigation::resolve($fallback, request()->query(BackNavigation::FROM_PARAM));

    if (! isset($class) || trim((string) $class) === '') {
        $class = $variant === 'btn'
            ? 'btn'
            : 'inline-flex items-center gap-1.5 text-sm text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] transition-colors';
    }
@endphp

<a href="{{ $href }}"
    class="{{ $class }}"
    data-back-nav
    data-back-fallback="{{ $fallback }}"
    @if (request()->filled(BackNavigation::FROM_PARAM))
        data-back-from="{{ BackNavigation::resolve($fallback, request()->query(BackNavigation::FROM_PARAM)) }}"
    @endif
>
    @if ($showIcon)
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    @endif
    <span>{{ $label }}</span>
</a>
