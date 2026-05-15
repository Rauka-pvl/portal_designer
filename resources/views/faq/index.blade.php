@extends($layout)

@section('title', __('faq.title'))
@section('heading', __('faq.heading'))
@section('header_title', __('faq.header_title'))

@section('content')
    @php
        $baseDirectory = trim((string) config('faq.storage_directory', 'video'), '/');
    @endphp

    <div class="w-full {{ $layout === 'layouts.auth' ? 'max-w-none' : 'max-w-5xl mx-auto' }}">
        <div class="rounded-3xl border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white/75 dark:bg-[#131312]/90 backdrop-blur-md shadow-[0_20px_50px_-30px_rgba(15,23,42,0.35)] p-4 sm:p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('faq.heading') }}</h2>
                <p class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('faq.subtitle') }}</p>
            </div>

            <div class="space-y-3">
                @foreach ($topics as $topic)
                    @php
                        $relativePath = trim($baseDirectory.'/'.$topic['video'], '/');
                        $encodedPath = collect(explode('/', $relativePath))
                            ->map(fn ($segment) => rawurlencode($segment))
                            ->implode('/');
                        $videoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)
                            ? asset('storage/'.$encodedPath)
                            : asset($encodedPath);
                    @endphp

                    <details class="group rounded-2xl border border-[#d6deea] bg-white dark:bg-[#161615] dark:border-[#3E3E3A] shadow-[0_8px_24px_-18px_rgba(15,23,42,0.35)] overflow-hidden transition-colors open:border-[#f59e0b]/45 open:shadow-[0_14px_30px_-18px_rgba(245,158,11,0.45)]">
                        <summary class="list-none cursor-pointer px-5 py-4 flex items-center justify-between gap-3 hover:bg-[#f8fafc] dark:hover:bg-[#1c1c1b] transition-colors">
                            <span class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __($topic['title_key']) }}</span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#f59e0b]/15 text-[#f59e0b] dark:bg-[#f59e0b]/20 transition-transform group-open:rotate-45">+</span>
                        </summary>

                        <div class="px-5 pb-5 pt-1 border-t border-[#ecf1f8] dark:border-[#2a2a28]">
                            <div class="mt-4 rounded-xl overflow-hidden border border-[#cad6e4] dark:border-[#3E3E3A] bg-black shadow-[0_14px_34px_-20px_rgba(0,0,0,0.6)]">
                                <video controls preload="metadata" class="w-full h-auto">
                                    <source src="{{ $videoUrl }}" type="video/mp4">
                                    {{ __('faq.video_not_supported') }}
                                </video>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-[#475569] dark:text-[#A1A09A]">
                                {{ __($topic['description_key']) }}
                            </p>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
@endsection
