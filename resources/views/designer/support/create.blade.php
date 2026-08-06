@extends('layouts.dashboard')

@section('title', __('support.new_ticket'))
@section('header_title', __('support.new_ticket'))

@section('content')
    <div class="max-w-2xl mx-auto px-4 py-6">
        <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('support.back_to_list') }}
        </a>

        <h1 class="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-6">{{ __('support.new_ticket') }}</h1>

        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data"
            class="space-y-5 bg-white dark:bg-[#161615] border border-[#e2e8f0] rounded-2xl p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">{{ __('support.subject') }}</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="200"
                    placeholder="{{ __('support.subject_placeholder') }}"
                    class="w-full border border-[#e2e8f0] rounded-lg px-3.5 py-2.5 text-sm bg-white dark:bg-[#0a0a0a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b] outline-none">
                @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">{{ __('support.category') }}</label>
                <select name="category" required
                    class="w-full border border-[#e2e8f0] rounded-lg px-3.5 py-2.5 text-sm bg-white dark:bg-[#0a0a0a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b] outline-none">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat['value'] }}" @selected(old('category') === $cat['value'])>{{ $cat['label'] }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">{{ __('support.message') }}</label>
                <textarea name="message" rows="6" required maxlength="5000"
                    placeholder="{{ __('support.message_placeholder') }}"
                    class="w-full border border-[#e2e8f0] rounded-lg px-3.5 py-2.5 text-sm bg-white dark:bg-[#0a0a0a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b] outline-none resize-y">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">{{ __('support.attachments') }}</label>
                <input type="file" name="attachments[]" multiple
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                    class="w-full text-sm text-[#706f6c] file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-[#f8fafc] file:text-[#1b1b18] hover:file:bg-[#f1f5f9]">
                <p class="mt-1 text-xs text-[#94a3b8]">{{ __('support.attachments_hint') }}</p>
                @error('attachments')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full sm:w-auto px-6 py-3 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">
                    {{ __('support.send') }}
                </button>
            </div>
        </form>
    </div>
@endsection
