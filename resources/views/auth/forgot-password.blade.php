@extends('layouts.auth')

@section('title', __('auth_labels.forgot_password'))

@section('heading', __('auth_labels.forgot_password'))

@section('content')
<div class="mb-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
    {{ __('auth_labels.forgot_password_description') }}
</div>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-6">
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

    <div class="flex flex-col gap-3">
        <button 
            type="submit"
            class="w-full px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] dark:text-[#1C1C1A] text-white rounded-sm hover:bg-black dark:hover:bg-white dark:hover:border-white border border-black dark:border-[#eeeeec] transition-colors"
        >
            {{ __('auth_labels.send_reset_link') }}
        </button>

        @if (Route::has('login'))
            <a 
                href="{{ route('login') }}"
                class="text-sm text-center text-[#706f6c] dark:text-[#A1A09A] hover:underline"
            >
                {{ __('auth_labels.back_to_login') }}
            </a>
        @endif
    </div>
</form>
@endsection
