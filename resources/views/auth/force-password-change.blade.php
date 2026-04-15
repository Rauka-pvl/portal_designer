@extends('layouts.auth')

@section('title', __('auth_labels.force_password_change'))

@section('heading', __('auth_labels.force_password_change'))

@section('content')
<div class="mb-5 rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700/40 dark:bg-amber-500/10 dark:text-amber-100">
    {{ __('auth_labels.force_password_change_description') }}
</div>

<form method="POST" action="{{ route('supplier.force-password.update') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium mb-2">{{ __('auth_labels.email') }}</label>
        <input
            type="email"
            id="email"
            value="{{ $user->email }}"
            readonly
            class="w-full px-4 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-[#f8fafc] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]"
        >
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium mb-2">{{ __('auth_labels.new_password') }}</label>
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

    <button
        type="submit"
        class="w-full px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] dark:text-[#1C1C1A] text-white rounded-sm hover:bg-black dark:hover:bg-white dark:hover:border-white border border-black dark:border-[#eeeeec] transition-colors"
    >
        {{ __('auth_labels.save_new_password') }}
    </button>
</form>
@endsection
