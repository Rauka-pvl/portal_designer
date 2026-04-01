@extends('layouts.dashboard')

@section('title', __('settings.settings'))

@section('content')
    @php
        $activeTab = $activeTab ?? 'profile';
    @endphp

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-700/40 bg-emerald-50 dark:bg-emerald-900/10 px-4 py-3 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 dark:border-red-700/40 bg-red-50 dark:bg-red-900/10 px-4 py-3 text-red-700 dark:text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        <aside class="w-full lg:w-72 shrink-0">
            <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-xl p-3">
                <a href="{{ route('settings.index', ['tab' => 'profile']) }}"
                    class="flex items-center px-3 py-2 rounded-lg mb-1 {{ $activeTab === 'profile' ? 'bg-[#fef3c7] dark:bg-[#1D0002] text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]' }}">
                    {{ __('settings.profile_settings') }}
                </a>
                <a href="{{ route('settings.index', ['tab' => 'security']) }}"
                    class="flex items-center px-3 py-2 rounded-lg mb-1 {{ $activeTab === 'security' ? 'bg-[#fef3c7] dark:bg-[#1D0002] text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]' }}">
                    {{ __('settings.security') }}
                </a>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg mb-1 text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a]" disabled>{{ __('settings.roles_and_access') }}</button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg mb-1 text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a]" disabled>{{ __('settings.team') }}</button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg mb-1 text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a]" disabled>{{ __('settings.subscriptions') }}</button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg mb-1 text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a]" disabled>{{ __('settings.payment_methods') }}</button>
                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a]" disabled>{{ __('settings.integrations') }}</button>
            </div>
        </aside>

        <section class="flex-1">
            @if ($activeTab === 'security')
                <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-xl p-6">
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ __('settings.security') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-5">{{ __('settings.change_password') }}</p>

                    <form method="POST" action="{{ route('settings.password.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @csrf
                        @method('PUT')
                        <div class="md:col-span-2">
                            <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.current_password') }}</label>
                            <input type="password" name="current_password" required class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        </div>
                        <div>
                            <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.new_password') }}</label>
                            <input type="password" name="password" required class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        </div>
                        <div>
                            <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.new_password_confirmation') }}</label>
                            <input type="password" name="password_confirmation" required class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="add-btn">{{ __('settings.save_changes') }}</button>
                        </div>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('settings.profile.update') }}"
                    class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-xl p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('settings.profile_settings') }}</h2>
                        <h3 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-3 uppercase">{{ __('settings.main_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.name') }}</label>
                                <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.short_description') }}</label>
                                <input type="text" name="short_description" value="{{ old('short_description', $user->short_description) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.city') }}</label>
                                <input type="text" name="city" value="{{ old('city', $user->city) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.work_regions') }}</label>
                                <input type="text" name="work_regions" value="{{ old('work_regions', $user->work_regions) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.about_designer') }}</label>
                                <textarea name="about_designer" rows="4" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ old('about_designer', $user->about_designer) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-3 uppercase">{{ __('settings.contact_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            @php
                                $contacts = [
                                    ['phone', 'phone'],
                                    ['email', 'email'],
                                    ['website_portfolio', 'website_portfolio'],
                                    ['telegram', 'telegram'],
                                    ['whatsapp', 'whatsapp'],
                                    ['vk', 'vk'],
                                    ['instagram', 'instagram'],
                                ];
                            @endphp
                            @foreach ($contacts as [$field, $label])
                                <div>
                                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.' . $label) }}</label>
                                    <input type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}" value="{{ old($field, $user->{$field}) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-3 uppercase">{{ __('settings.professional_information') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.experience') }}</label>
                                <input type="text" name="experience" value="{{ old('experience', $user->experience) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.price_per_m2') }}</label>
                                <input type="number" step="0.01" min="0" name="price_per_m2" value="{{ old('price_per_m2', $user->price_per_m2) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.education') }}</label>
                                <textarea name="education" rows="3" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ old('education', $user->education) }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.awards') }}</label>
                                <textarea name="awards" rows="3" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ old('awards', $user->awards) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.specialization') }}</label>
                                <input type="text" name="specialization" value="{{ old('specialization', $user->specialization) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                            <div>
                                <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.styles') }}</label>
                                <input type="text" name="styles" value="{{ old('styles', $user->styles) }}" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="add-btn">{{ __('settings.save_changes') }}</button>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection

