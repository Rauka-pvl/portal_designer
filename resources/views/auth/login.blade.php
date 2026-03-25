@extends('layouts.auth')

@section('title', __('auth_labels.login'))

@section('heading', __('auth_labels.login'))

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium mb-2">{{ __('auth_labels.email') }}</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            required
            autofocus
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

    <div class="mb-6 flex items-center">
        <input
            type="checkbox"
            id="remember"
            name="remember"
            class="mr-2"
        >
        <label for="remember" class="text-sm">{{ __('auth_labels.remember_me') }}</label>
    </div>

    <div class="flex flex-col gap-3">
        <button
            type="submit"
            class="w-full px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] dark:text-[#1C1C1A] text-white rounded-sm hover:bg-black dark:hover:bg-white dark:hover:border-white border border-black dark:border-[#eeeeec] transition-colors"
        >
            {{ __('auth_labels.login_button') }}
        </button>

        @if (Route::has('password.request'))
            <a
                href="{{ route('password.request') }}"
                class="text-sm text-center text-[#706f6c] dark:text-[#A1A09A] hover:underline"
            >
                {{ __('auth_labels.forgot_password_link') }}
            </a>
        @endif
    </div>
</form>
@endsection

@section('footer')
<p class="text-sm text-center text-[#706f6c] dark:text-[#A1A09A]">
    {{ __('auth_labels.no_account') }}
    <a href="{{ route('register') }}" class="text-[#f53003] dark:text-[#FF4433] hover:underline font-medium">
        {{ __('auth_labels.register') }}
    </a>
</p>
@endsection
