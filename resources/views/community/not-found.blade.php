@extends($layout)

@section('title', __('community.title'))
@section('header_title', __('community.title'))

@section('content')
<div class="max-w-lg mx-auto rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-10 text-center">
    <h1 class="text-xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.not_found_title') }}</h1>
    <div class="mt-6">
        @include('partials.back-link', [
            'fallback' => route('community.index'),
            'label' => __('community.back_community'),
            'class' => 'inline-flex h-11 px-4 items-center rounded-xl bg-[#f59e0b] text-white text-sm hover:opacity-95',
            'icon' => false,
        ])
    </div>
</div>
@endsection
