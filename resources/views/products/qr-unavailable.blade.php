@php
    $layout = match (auth()->user()->role ?? null) {
        'supplier' => 'layouts.supplier',
        'designer', 'moderator' => 'layouts.dashboard',
        default => 'layouts.public',
    };
@endphp
@extends($layout)

@section('title', $title)
@section('header_title', $title)

@section('content')
    <div class="max-w-lg mx-auto rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#f59e0b]/15 text-[#f59e0b]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ $title }}</h1>
        <p class="mt-3 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $message }}</p>
        @auth
            <a href="{{ auth()->user()->role === 'supplier' ? route('supplier.index') : route('dashboard') }}"
               class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-[#f59e0b] px-5 text-sm font-medium text-white hover:bg-[#d97706]">
                {{ __('products.qr_go_home') }}
            </a>
        @else
            <a href="{{ route('login') }}"
               class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-[#f59e0b] px-5 text-sm font-medium text-white hover:bg-[#d97706]">
                {{ __('products.qr_login_hint') }}
            </a>
        @endauth
    </div>
@endsection
