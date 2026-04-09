@extends('layouts.supplier')

@section('title', __('supplier-portal.title'))

@section('content')
    <style>
        .supplier-step-section {
            display: none;
        }

        .supplier-step-section.active {
            display: block;
        }

        .supplier-steps-track {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .supplier-step-chip {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            border-radius: 10px;
            padding: 0.55rem 0.6rem;
            font-size: 0.78rem;
            line-height: 1.15;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .supplier-step-chip.active {
            border-color: #f59e0b;
            color: #f59e0b;
            background: #fef3c7;
        }

        .supplier-step-chip.done {
            border-color: #10b981;
            color: #047857;
            background: #ecfdf5;
        }

        .dark .supplier-step-chip {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #A1A09A;
        }

        .dark .supplier-step-chip.active {
            border-color: #f59e0b;
            color: #f59e0b;
            background: #1D0002;
        }

        .dark .supplier-step-chip.done {
            border-color: #10b981;
            color: #6ee7b7;
            background: #052e25;
        }

        .supplier-modal-input {
            margin-top: 0.25rem;
            width: 100%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #7c8799;
            background: #ffffff;
        }

        .dark .supplier-modal-input {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #EDEDEC;
        }

        .logo-preview {
            position: relative;
            width: 6rem;
            height: 6rem;
            border-radius: 9999px;
            background: #f1f5f9;
            border: 2px dashed #7c8799;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            flex-shrink: 0;
        }

        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-preview-placeholder {
            font-size: 1.875rem;
            color: #7c8799;
            transition: color .2s ease;
        }

        .logo-preview:hover .logo-preview-placeholder {
            color: #f59e0b;
        }

        .logo-edit-hint {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            color: #fff;
            font-size: .75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s ease;
        }

        .logo-preview:hover .logo-edit-hint {
            opacity: 1;
        }

        .dark .logo-preview {
            background: #0a0a0a;
            border-color: #3E3E3A;
        }
    </style>

    @php
        $showOnboardingModal = !$supplier || (string) ($supplier->profile_status ?? 'draft') === 'draft';
    @endphp

    <div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-[#7c8799]/30 dark:border-[#3E3E3A] bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-fuchsia-500/10 dark:from-amber-500/5 dark:via-rose-500/5 dark:to-fuchsia-500/5">
            <h1 class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.subtitle') }}</p>
        </div>

        <div class="p-6">
            @if ($supplier)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.name') }}</dt>
                        <dd class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.profile_status') }}</dt>
                        <dd class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->profile_status ?? '—' }}</dd>
                    </div>
                    @if ($supplier->city)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.city') }}</dt>
                            <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->city }}</dd>
                        </div>
                    @endif
                    @if ($supplier->phone)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.phone') }}</dt>
                            <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->phone }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="rounded-lg border border-dashed border-[#f59e0b]/50 bg-amber-50/50 dark:bg-amber-950/20 px-4 py-5">
                    <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ __('supplier-portal.no_company_yet') }}</p>
                    <p class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A] leading-relaxed">{{ __('supplier-portal.no_company_hint') }}</p>
                </div>
            @endif
        </div>
    </div>

    @if ($showOnboardingModal)
        <div class="fixed inset-0 z-50 bg-black/55 backdrop-blur-[1px] flex items-center justify-center p-4">
            <div class="w-full max-w-2xl max-h-[90vh] rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-2xl overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-[#7c8799]/30 dark:border-[#3E3E3A]">
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.fill_profile_title') }}</h2>
                    <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.fill_profile_hint') }}</p>
                </div>
                <form method="POST" action="{{ route('supplier.profile.save') }}" id="supplier-onboarding-form" enctype="multipart/form-data" class="flex flex-col min-h-0 flex-1">
                    @csrf
                    <div class="p-6 border-b border-[#7c8799]/30 dark:border-[#3E3E3A]">
                        <div class="supplier-steps-track">
                            <div class="supplier-step-chip active" data-step-chip="1">1. {{ __('suppliers.main_info') }}</div>
                            <div class="supplier-step-chip" data-step-chip="2">2. {{ __('suppliers.requisites') }}</div>
                            <div class="supplier-step-chip" data-step-chip="3">3. {{ __('suppliers.bank_details') }}</div>
                        </div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-2" id="supplier-onboarding-caption">Шаг 1 из 3</p>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 space-y-5">
                        <section class="supplier-step-section active" data-step="1">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                <div class="flex items-start gap-4">
                                    <div id="logo-preview" class="logo-preview" title="{{ __('suppliers.upload') }}">
                                        <img id="logo-preview-img" src="" alt="" class="hidden">
                                        <span id="logo-preview-placeholder" class="logo-preview-placeholder">+</span>
                                        <span id="logo-edit-hint" class="logo-edit-hint hidden">{{ __('suppliers.edit') }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.logo') }}</label>
                                        <div class="flex items-center gap-3 mt-1">
                                            <input type="file" name="logo" id="logo-file-input" accept="image/jpeg,image/gif,image/png,image/webp" class="hidden">
                                            <label for="logo-file-input"
                                                class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm font-medium text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                                {{ __('suppliers.upload') }}
                                            </label>
                                            <button type="button" id="logo-remove-btn"
                                                class="text-sm text-red-500 hover:underline hidden">{{ __('suppliers.remove') }}</button>
                                        </div>
                                        <p class="mt-1 text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.logo_hint') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                    <input type="checkbox" name="recommend" value="1" @checked(old('recommend'))
                                        class="rounded border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                    <span>{{ __('suppliers.recommend_supplier') }}</span>
                                </label>
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.phone') }}</label>
                                <input name="phone" value="{{ old('phone', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">Telegram</label>
                                <input name="telegram" value="{{ old('telegram', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">WhatsApp</label>
                                <input name="whatsapp" value="{{ old('whatsapp', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.website') }}</label>
                                <input name="website" value="{{ old('website', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.city') }}</label>
                                <input name="city" value="{{ old('city', '') }}" class="supplier-modal-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.address') }}</label>
                                <input name="address" value="{{ old('address', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.sphere') }}</label>
                                <select name="sphere" class="supplier-modal-input">
                                    <option value="">{{ __('suppliers.sphere_placeholder') }}</option>
                                    @foreach ((trans('supplier_spheres') ?: []) as $key => $name)
                                        <option value="{{ $key }}" @selected(old('sphere') === $key)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.work_terms') }}</label>
                                <select name="work_terms_type" class="supplier-modal-input">
                                    <option value=""></option>
                                    <option value="percent" @selected(old('work_terms_type') === 'percent')>{{ __('suppliers.work_terms_percent') }}</option>
                                    <option value="amount" @selected(old('work_terms_type') === 'amount')>{{ __('suppliers.work_terms_amount') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.value') }}</label>
                                <input name="work_terms_value" value="{{ old('work_terms_value', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.brands') }}</label>
                                <input name="brands[]" value="{{ old('brands.0', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.cities_presence') }}</label>
                                <input name="cities_presence[]" value="{{ old('cities_presence.0', '') }}" class="supplier-modal-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.comment') }}</label>
                                <textarea name="comment_main" rows="3" class="supplier-modal-input">{{ old('comment_main') }}</textarea>
                            </div>
                        </div>
                        </section>

                        <section class="supplier-step-section" data-step="2">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.org_form') }}</label>
                                <select name="org_form" class="supplier-modal-input">
                                    <option value="ooo" @selected(old('org_form', 'ooo') === 'ooo')>{{ __('suppliers.org_too') }}</option>
                                    <option value="ip" @selected(old('org_form') === 'ip')>{{ __('suppliers.org_ip') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.inn') }}</label>
                                <input name="inn" value="{{ old('inn', '') }}" required class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.kpp') }}</label>
                                <input name="kpp" value="{{ old('kpp', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.ogrn') }}</label>
                                <input name="ogrn" value="{{ old('ogrn', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.okpo') }}</label>
                                <input name="okpo" value="{{ old('okpo', '') }}" class="supplier-modal-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.legal_address') }}</label>
                                <textarea name="legal_address" rows="2" class="supplier-modal-input">{{ old('legal_address') }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.actual_address') }}</label>
                                <textarea name="actual_address" rows="2" class="supplier-modal-input">{{ old('actual_address') }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                    <input type="checkbox" name="address_match" value="1" @checked(old('address_match'))
                                        class="rounded border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                    <span>{{ __('suppliers.match_legal') }}</span>
                                </label>
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.director') }}</label>
                                <input name="director" value="{{ old('director', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.accountant') }}</label>
                                <input name="accountant" value="{{ old('accountant', '') }}" class="supplier-modal-input">
                            </div>
                        </div>
                        </section>

                        <section class="supplier-step-section" data-step="3">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.bik') }}</label>
                                <input name="bik" value="{{ old('bik', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.bank') }}</label>
                                <input name="bank" value="{{ old('bank', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.checking_account') }}</label>
                                <input name="checking_account" value="{{ old('checking_account', '') }}" class="supplier-modal-input">
                            </div>
                            <div>
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.corr_account') }}</label>
                                <input name="corr_account" value="{{ old('corr_account', '') }}" class="supplier-modal-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.comment_bank') }}</label>
                                <textarea name="comment_bank" rows="3" class="supplier-modal-input">{{ old('comment_bank') }}</textarea>
                            </div>
                        </div>
                        </section>

                        <div>
                        @if ($errors->any())
                            <div class="rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-200 px-4 py-3 text-sm">
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 pt-2">
                        <button type="button" id="supplier-onboarding-prev" class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#0f172a] dark:text-[#EDEDEC] disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('objects.prev') }}
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" id="supplier-onboarding-next" class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white hover:bg-[#d97706] transition-colors">
                                {{ __('objects.next') }}
                            </button>
                            <button type="submit" id="supplier-onboarding-submit" class="hidden px-4 py-2 rounded-lg border border-[#0f172a] dark:border-[#EDEDEC] text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#0f172a] hover:text-white dark:hover:bg-[#EDEDEC] dark:hover:text-[#0f172a] transition-colors">
                                {{ __('supplier-portal.submit_profile') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('supplier-onboarding-form');
            if (!form) return;

            const maxStep = 3;
            let step = 1;

            const prevBtn = document.getElementById('supplier-onboarding-prev');
            const nextBtn = document.getElementById('supplier-onboarding-next');
            const submitBtn = document.getElementById('supplier-onboarding-submit');
            const caption = document.getElementById('supplier-onboarding-caption');

            function validateStep(currentStep) {
                const section = form.querySelector(`.supplier-step-section[data-step="${currentStep}"]`);
                if (!section) return true;
                const required = section.querySelectorAll('[required]');
                for (const input of required) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        return false;
                    }
                }
                return true;
            }

            function updateStepUi() {
                form.querySelectorAll('.supplier-step-section').forEach((section) => {
                    section.classList.toggle('active', Number(section.dataset.step) === step);
                });

                document.querySelectorAll('[data-step-chip]').forEach((chip) => {
                    const chipStep = Number(chip.dataset.stepChip);
                    chip.classList.toggle('active', chipStep === step);
                    chip.classList.toggle('done', chipStep < step);
                });

                if (caption) caption.textContent = `Шаг ${step} из ${maxStep}`;
                if (prevBtn) prevBtn.disabled = step === 1;
                if (nextBtn) nextBtn.classList.toggle('hidden', step === maxStep);
                if (submitBtn) submitBtn.classList.toggle('hidden', step !== maxStep);
            }

            prevBtn?.addEventListener('click', function() {
                step = Math.max(1, step - 1);
                updateStepUi();
            });

            nextBtn?.addEventListener('click', function() {
                if (!validateStep(step)) return;
                step = Math.min(maxStep, step + 1);
                updateStepUi();
            });

            updateStepUi();
        })();
    </script>
    <script>
        (function() {
            const input = document.getElementById('logo-file-input');
            const preview = document.getElementById('logo-preview');
            const img = document.getElementById('logo-preview-img');
            const placeholder = document.getElementById('logo-preview-placeholder');
            const removeBtn = document.getElementById('logo-remove-btn');
            const editHint = document.getElementById('logo-edit-hint');
            if (!input || !preview || !img || !placeholder || !removeBtn || !editHint) return;

            function resetLogoPreview() {
                input.value = '';
                img.src = '';
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
                removeBtn.classList.add('hidden');
                editHint.classList.add('hidden');
            }

            function setLogoPreview(file) {
                const reader = new FileReader();
                reader.onload = function() {
                    img.src = String(reader.result || '');
                    img.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                    editHint.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            preview.addEventListener('click', function(e) {
                if (e.target instanceof HTMLElement && e.target.closest('#logo-remove-btn')) return;
                input.click();
            });

            input.addEventListener('change', function(e) {
                const file = e.target.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;
                setLogoPreview(file);
            });

            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                resetLogoPreview();
            });
        })();
    </script>
@endpush
