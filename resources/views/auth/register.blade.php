@extends('layouts.auth')

@php($authAsSupplier = $authAsSupplier ?? false)

@section('title', $authAsSupplier ? __('auth_labels.register_supplier') : __('auth_labels.register_designer'))

@section('heading', $authAsSupplier ? __('auth_labels.register_supplier') : __('auth_labels.register_designer'))

@section('content')
@if ($authAsSupplier)
<a
        href="{{ route('register', ['as' => 'designer']) }}"
        class="group relative flex w-full items-center gap-3 rounded-xl px-4 py-3.5 mb-6 text-left transition-all duration-200
            bg-gradient-to-br from-amber-500/15 via-rose-500/10 to-fuchsia-500/15
            dark:from-amber-500/10 dark:via-rose-500/5 dark:to-fuchsia-500/10
            border border-transparent
            shadow-[0_0_0_1px_rgba(245,158,11,0.35),0_4px_14px_-4px_rgba(236,72,153,0.25)]
            dark:shadow-[0_0_0_1px_rgba(245,158,11,0.25),0_4px_20px_-6px_rgba(0,0,0,0.45)]
            hover:shadow-[0_0_0_1px_rgba(245,158,11,0.55),0_8px_24px_-6px_rgba(236,72,153,0.35)]
            hover:-translate-y-0.5"
    >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-rose-600 text-white shadow-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('auth_labels.im_designer') }}</span>
            <span class="block text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('auth_labels.register_designer') }} →</span>
        </span>
        <svg class="w-5 h-5 shrink-0 text-[#f59e0b] opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
@else
    <a
        href="{{ route('register', ['as' => 'supplier']) }}"
        class="group relative flex w-full items-center gap-3 rounded-xl px-4 py-3.5 mb-6 text-left transition-all duration-200
            bg-gradient-to-br from-amber-500/15 via-rose-500/10 to-fuchsia-500/15
            dark:from-amber-500/10 dark:via-rose-500/5 dark:to-fuchsia-500/10
            border border-transparent
            shadow-[0_0_0_1px_rgba(245,158,11,0.35),0_4px_14px_-4px_rgba(236,72,153,0.25)]
            dark:shadow-[0_0_0_1px_rgba(245,158,11,0.25),0_4px_20px_-6px_rgba(0,0,0,0.45)]
            hover:shadow-[0_0_0_1px_rgba(245,158,11,0.55),0_8px_24px_-6px_rgba(236,72,153,0.35)]
            hover:-translate-y-0.5"
    >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-rose-600 text-white shadow-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('auth_labels.im_supplier') }}</span>
            <span class="block text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('auth_labels.register_supplier') }} →</span>
        </span>
        <svg class="w-5 h-5 shrink-0 text-[#f59e0b] opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    <input type="hidden" name="portal" value="{{ $authAsSupplier ? 'supplier' : 'designer' }}">

    <div class="mb-4">
        <label for="name" class="block text-sm font-medium mb-2">{{ __('auth_labels.name') }}</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            value="{{ old('name') }}" 
            required 
            autofocus
            class="w-full px-4 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-black dark:focus:border-white"
        >
    </div>

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium mb-2">{{ __('auth_labels.email') }}</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            required
            class="w-full px-4 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-black dark:focus:border-white"
        >
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium mb-2">{{ __('auth_labels.password') }}</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            required
            class="w-full px-4 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-black dark:focus:border-white"
        >
    </div>

    <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium mb-2">{{ __('auth_labels.password_confirmation') }}</label>
        <input 
            type="password" 
            id="password_confirmation" 
            name="password_confirmation" 
            required
            class="w-full px-4 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:border-black dark:focus:border-white"
        >
    </div>

    <div class="flex flex-col gap-3">
        <button 
            type="submit"
            class="w-full px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] dark:text-[#1C1C1A] text-white rounded-sm hover:bg-black dark:hover:bg-white dark:hover:border-white border border-black dark:border-[#eeeeec] transition-colors"
        >
            {{ __('auth_labels.register_button') }}
        </button>
    </div>
</form>
@endsection

@section('footer')
<p class="text-sm text-center text-[#706f6c] dark:text-[#A1A09A]">
    {{ __('auth_labels.have_account') }}
    <a href="{{ $authAsSupplier ? route('login', ['as' => 'supplier']) : route('login') }}" class="text-[#f53003] dark:text-[#FF4433] hover:underline font-medium">
        {{ $authAsSupplier ? __('auth_labels.login_supplier') : __('auth_labels.login') }}
    </a>
</p>

@endsection
