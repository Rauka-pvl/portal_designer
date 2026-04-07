@extends('layouts.auth')

@section('title', __('referrals.page_title'))

@section('content')
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="rounded-xl border border-red-200 bg-red-50 p-6">
            <h1 class="text-xl font-semibold text-red-800">{{ __('referrals.invalid_signature_title') }}</h1>
            <p class="text-sm text-red-700 mt-2">{{ __('referrals.invalid_signature') }}</p>
        </div>
    </div>
@endsection
