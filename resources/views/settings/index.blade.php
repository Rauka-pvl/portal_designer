@extends('layouts.dashboard')

@section('title', __('settings.settings'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('settings.settings') }}</h1>
</div>

<!-- Контент будет добавлен позже -->
<div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
    <p class="text-[#64748b] dark:text-[#A1A09A]">{{ __('settings.settings') }}</p>
</div>
@endsection
