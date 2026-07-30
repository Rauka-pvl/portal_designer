@extends('layouts.dashboard')

@section('title', __('suppliers.suppliers'))
@section('header_title')
    <span class="inline-flex items-center gap-2">
        {{ __('suppliers.suppliers') }}
        <span id="suppliers-header-count" class="crm-suppliers-count-badge">0</span>
    </span>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
    <style>
    @include('designer.suppliers.partials.crm-listing-styles')

        .accordion-section {
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 0.5rem;
            overflow: hidden;
            isolation: isolate;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-section:hover,
        .accordion-section:hover .accordion-header {
            background: #f1f5f9;
        }

        .dark .accordion-section {
            background: #161615;
        }

        .dark .accordion-section:hover,
        .dark .accordion-section:hover .accordion-header {
            background: #1a1a18;
        }

        .dark .accordion-header {
            background: #161615;
        }

        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
            background: #f8fafc;
            border: none;
            position: relative;
            z-index: 1;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-header:hover {
            color: #f59e0b;
            padding-left: 1.5rem;
        }

        .accordion-header svg {
            flex-shrink: 0;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.7;
        }

        .accordion-header:hover svg,
        .accordion-header[aria-expanded="true"] svg {
            opacity: 1;
        }

        .accordion-header[aria-expanded="true"] svg {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-content:not(.accordion-open) .accordion-body {
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s linear 0.2s, opacity 0.2s ease;
        }

        .accordion-content.accordion-open .accordion-body {
            visibility: visible;
            opacity: 1;
            transition: visibility 0s, opacity 0.25s ease;
        }

        .accordion-content.accordion-open {
            max-height: 1500px;
        }

        .accordion-body {
            overflow: hidden;
            padding: 0 1.25rem 1.25rem;
            animation: accordionFadeIn 0.35s ease-out;
            background: #f8fafc;
        }

        .dark .accordion-body {
            background: #161615;
        }

        @keyframes accordionFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dark .accordion-header {
            color: #EDEDEC;
        }

        .dark .accordion-header:hover {
            color: #f59e0b;
        }

        #supplier-modal .modal-content {
            animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #supplier-modal {
            transition: opacity 0.3s ease;
        }

        #supplier-modal.modal-closing {
            opacity: 0;
        }

        #supplier-modal.modal-closing .modal-content {
            transform: scale(0.97) translateY(8px);
            opacity: 0;
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #supplier-modal .accordion-section {
            animation: sectionStagger 0.4s ease-out backwards;
        }

        #supplier-modal .accordion-section:nth-child(1) {
            animation-delay: 0.05s;
        }

        #supplier-modal .accordion-section:nth-child(2) {
            animation-delay: 0.1s;
        }

        #supplier-modal .accordion-section:nth-child(3) {
            animation-delay: 0.15s;
        }

        @keyframes sectionStagger {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #supplier-modal .modal-input {
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .accordion-add-btn {
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .accordion-add-btn:hover {
            transform: translateX(3px);
            opacity: 0.9;
        }

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
            margin-bottom: 0.875rem;
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

        .supplier-step-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
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

        #supplier-step-submit.hidden {
            display: none;
        }
    </style>
@endpush

@section('content')
    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif

@include('designer.suppliers.partials.crm-listing')

    <!-- Модалка просмотра поставщика (справа) -->
    <div id="view-supplier-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay"
        onmousedown="if(event.target === this) closeViewSupplierModal()">
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content"
            onclick="event.stopPropagation()">
            <div class="flex flex-col h-full">
                <div
                    class="flex items-center justify-between px-6 py-5 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.view') }}
                        </h2>
                        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('suppliers.view') }}
                            {{ __('suppliers.supplier') }}</p>
                    </div>
                    <button onclick="closeViewSupplierModal()"
                        class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-supplier-content" class="flex-1 overflow-y-auto p-6 space-y-5"></div>
            </div>
        </div>
    </div>

    <!-- Модалка добавления/редактирования поставщика -->
    <div id="supplier-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeSupplierModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#7c8799] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#7c8799] dark:border-[#3E3E3A] shrink-0">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="supplier-modal-title">
                        {{ __('suppliers.new_supplier') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                        {{ __('suppliers.supplier_modal_subtitle') }}</p>
                </div>
                <button type="button" onclick="closeSupplierModal()"
                    class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-all duration-200 hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="supplier-form" class="flex flex-col flex-1 min-h-0" enctype="multipart/form-data" data-ajax="1">
                @csrf
                <input type="hidden" name="supplier_id" id="supplier_id">
                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div>
                        <div class="supplier-steps-track" id="supplier-steps-track">
                            <div class="supplier-step-chip active" data-step-chip="1">1. Основная информация</div>
                            <div class="supplier-step-chip" data-step-chip="2">2. Реквизиты компании</div>
                            <div class="supplier-step-chip" data-step-chip="3">3. Банковские данные</div>
                        </div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]" id="supplier-step-caption">Шаг 1 из 3</p>
                    </div>

                    <!-- Секция 1: Основная информация -->
                    <div class="accordion-section supplier-step-section active" data-supplier-step="1">
                        <div class="accordion-header">
                            {{ __('suppliers.main_info') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div class="accordion-content accordion-open">
                            <div class="accordion-body space-y-5">
                                <div class="flex items-start gap-4">
                                    <div class="relative w-24 h-24 rounded-full bg-[#f1f5f9] dark:bg-[#0a0a0a] border-2 border-dashed border-[#7c8799] dark:border-[#3E3E3A] flex items-center justify-center flex-shrink-0 overflow-hidden cursor-pointer group"
                                        id="logo-preview" onclick="window.handleLogoPreviewClick(event)"
                                        title="{{ __('suppliers.upload') }}">
                                        <img id="logo-preview-img" src="" alt=""
                                            class="hidden w-full h-full object-cover">
                                        <span id="logo-preview-placeholder"
                                            class="text-3xl text-[#7c8799] dark:text-[#71716c] group-hover:text-[#f59e0b] transition-colors">+</span>
                                        <span id="logo-edit-hint"
                                            class="hidden absolute inset-0 bg-black/40 rounded-full flex items-center justify-center text-white text-xs font-medium opacity-0 group-hover:opacity-100 transition-opacity">{{ __('suppliers.edit') }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <label class="modal-label">{{ __('suppliers.logo') }}</label>
                                        <div class="flex items-center gap-3 mt-1">
                                            <input type="file" name="logo" id="logo-file-input"
                                                accept="image/jpeg,image/gif,image/png,image/webp" class="hidden">
                                            <label for="logo-file-input"
                                                class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm font-medium text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                                {{ __('suppliers.upload') }}
                                            </label>
                                            <button type="button" id="logo-remove-btn"
                                                class="text-sm text-red-500 hover:underline hidden">{{ __('suppliers.remove') }}</button>
                                        </div>
                                        <p class="modal-helper">{{ __('suppliers.logo_hint') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label modal-label-required">{{ __('suppliers.name') }}</label>
                                    <input type="text" name="name" required class="modal-input"
                                        placeholder="{{ __('suppliers.name_placeholder') }}">
                                    <p class="modal-helper">{{ __('suppliers.name_helper') }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="recommend" id="recommend"
                                        class="rounded border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                    <label for="recommend"
                                        class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.recommend_supplier') }}</label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.phone') }}</label>
                                        <input type="text" inputmode="tel" name="phone" id="supplier-phone"
                                            class="modal-input" placeholder="+7 700 123 45 67" autocomplete="tel">
                                        <p class="modal-helper">{{ __('suppliers.phone_helper') }}</p>
                                    </div>
                                    <div>
                                        <label class="modal-label modal-label-required">{{ __('suppliers.email') }}</label>
                                        <input type="email" name="email" required class="modal-input"
                                            placeholder="{{ __('suppliers.email_placeholder') }}">
                                        <p class="modal-helper">{{ __('suppliers.email_helper') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">Telegram</label>
                                        <div class="input-with-icon">
                                            <span class="input-icon text-[#0088cc]"><svg class="w-5 h-5"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                                </svg></span>
                                            <input type="text" name="telegram" id="supplier-telegram"
                                                class="modal-input" placeholder="@username или +7 700 123 45 67">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="modal-label">WhatsApp</label>
                                        <div class="input-with-icon">
                                            <span class="input-icon text-[#25D366]"><svg class="w-5 h-5"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                                </svg></span>
                                            <input type="text" name="whatsapp" id="supplier-whatsapp"
                                                class="modal-input" placeholder="+7 700 123 45 67" inputmode="tel">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.website') }}</label>
                                        <input type="url" name="website" class="modal-input"
                                            placeholder="Введите сайт">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.city') }}</label>
                                        <input type="text" name="city" class="modal-input"
                                            placeholder="{{ __('suppliers.city_placeholder') }}">
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.address') }}</label>
                                    <input type="text" name="address" class="modal-input"
                                        placeholder="{{ __('suppliers.address_placeholder') }}">
                                    <p class="modal-helper">{{ __('suppliers.address_helper') }}</p>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.sphere_activity') }}</label>
                                    <select name="sphere" class="modal-input">
                                        <option value="">{{ __('suppliers.sphere_placeholder') }}</option>
                                        @foreach (($sphereOptions ?? []) as $key => $name)
                                        
                                        <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.work_terms') }}</label>
                                        <select name="work_terms_type" class="modal-input">
                                            <option value="percent">{{ __('suppliers.work_terms_percent') }}</option>
                                            <option value="amount">{{ __('suppliers.work_terms_amount') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.value') }}</label>
                                        <input type="text" name="work_terms_value" class="modal-input"
                                            placeholder="{{ __('suppliers.value_placeholder') }}">
                                        <p class="modal-helper">{{ __('suppliers.value_helper') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.brands') }}</label>
                                    <p class="modal-helper mb-2">{{ __('suppliers.brands_helper') }}</p>
                                    <div id="brands-tags" class="flex flex-wrap gap-2 mb-2"></div>
                                    <div class="flex gap-2">
                                        <input type="text" name="brand_input" id="brand_input"
                                            class="modal-input flex-1"
                                            placeholder="{{ __('suppliers.brand_placeholder') }}">
                                        <button type="button" id="add-brand-btn"
                                            class="accordion-add-btn text-sm font-medium text-[#f59e0b] hover:underline whitespace-nowrap">+
                                            {{ __('suppliers.add') }}</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.cities_presence') }}</label>
                                    <p class="modal-helper mb-2">{{ __('suppliers.cities_helper') }}</p>
                                    <div id="cities-tags" class="flex flex-wrap gap-2 mb-2"></div>
                                    <div class="flex gap-2">
                                        <input type="text" name="city_input" id="city_input"
                                            class="modal-input flex-1"
                                            placeholder="{{ __('suppliers.city_placeholder') }}">
                                        <button type="button" id="add-city-btn"
                                            class="accordion-add-btn text-sm font-medium text-[#f59e0b] hover:underline whitespace-nowrap">+
                                            {{ __('suppliers.add') }}</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.comment') }}</label>
                                    <input type="text" name="comment_main" class="modal-input"
                                        placeholder="{{ __('suppliers.comment_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция 2: Реквизиты -->
                    <div class="accordion-section supplier-step-section" data-supplier-step="2">
                        <div class="accordion-header">
                            {{ __('suppliers.requisites') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div class="accordion-content accordion-open">
                            <div class="accordion-body space-y-5">
                                <div>
                                    <label class="modal-label">{{ __('suppliers.org_form') }}</label>
                                    <div class="flex gap-6 mt-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="org_form" value="ooo" checked
                                                class="text-[#f59e0b] focus:ring-[#f59e0b]">
                                            <span
                                                class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.org_too') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="org_form" value="ip"
                                                class="text-[#f59e0b] focus:ring-[#f59e0b]">
                                            <span
                                                class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.org_ip') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.inn') }}</label>
                                    <input type="text" name="inn" id="supplier-inn" class="modal-input"
                                        required placeholder="000000000000" maxlength="12" inputmode="numeric" pattern="[0-9]*"
                                        oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.kpp') }}</label>
                                        <input type="text" name="kpp" id="supplier-kpp" class="modal-input"
                                            placeholder="000000000" maxlength="9" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.ogrn') }}</label>
                                        <input type="text" name="ogrn" id="supplier-ogrn" class="modal-input"
                                            placeholder="0000000000000" maxlength="15" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.okpo') }}</label>
                                        <input type="text" name="okpo" id="supplier-okpo" class="modal-input"
                                            placeholder="00000000" maxlength="10" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.legal_address') }}</label>
                                    <input type="text" name="legal_address" class="modal-input"
                                        placeholder="{{ __('suppliers.legal_address_placeholder') }}">
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.actual_address') }}</label>
                                    <input type="text" name="actual_address" class="modal-input"
                                        placeholder="{{ __('suppliers.actual_address_placeholder') }}">
                                    <div class="flex items-center gap-2 mt-2">
                                        <input type="checkbox" name="address_match" id="address_match"
                                            class="rounded border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                        <label for="address_match"
                                            class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.match_legal') }}</label>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.director') }}</label>
                                    <input type="text" name="director" class="modal-input"
                                        placeholder="{{ __('suppliers.director_placeholder') }}">
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.accountant') }}</label>
                                    <input type="text" name="accountant" class="modal-input"
                                        placeholder="{{ __('suppliers.accountant_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция 3: Банковские реквизиты -->
                    <div class="accordion-section supplier-step-section" data-supplier-step="3">
                        <div class="accordion-header">
                            {{ __('suppliers.bank_details') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div class="accordion-content accordion-open">
                            <div class="accordion-body space-y-5">
                                <div>
                                    <label class="modal-label">{{ __('suppliers.bik') }}</label>
                                    <input type="text" name="bik" id="supplier-bik" class="modal-input"
                                        placeholder="00000000" maxlength="8" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.bank') }}</label>
                                    <input type="text" name="bank" class="modal-input"
                                        placeholder="{{ __('suppliers.bank_placeholder') }}">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.checking_account') }}</label>
                                    <input type="text" name="checking_account" id="supplier-checking-account"
                                        class="modal-input" placeholder="00000000000000000000" maxlength="20"
                                        inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.corr_account') }}</label>
                                    <input type="text" name="corr_account" id="supplier-corr-account"
                                        class="modal-input" placeholder="00000000000000000000" maxlength="20"
                                        inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.comment') }}</label>
                                    <input type="text" name="comment_bank" class="modal-input"
                                        placeholder="{{ __('suppliers.comment_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer flex-col sm:flex-row gap-3">
                    <div class="supplier-step-actions">
                        <button type="button" id="supplier-step-prev"
                            class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-2">
                            {{ __('objects.prev') }}
                        </button>
                        <button type="button" id="supplier-step-next"
                            class="add-btn w-full sm:w-auto">{{ __('objects.next') }}</button>
                        <button type="submit" id="supplier-step-submit" class="add-btn w-full sm:w-auto hidden">
                            Отправить на проверку
                        </button>
                    </div>
                    <button type="submit" id="supplier-submit-btn" class="hidden" tabindex="-1" aria-hidden="true">
                        {{ __('suppliers.add_supplier') }}
                    </button>
                    <button type="button" onclick="closeSupplierModal()"
                        class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-2 transition-all duration-200 hover:gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ __('suppliers.go_back') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка обрезки логотипа -->
    <div id="logo-crop-modal" class="fixed inset-0 bg-black/70 z-[60] hidden items-center justify-center p-4"
        onmousedown="if(event.target === this) closeLogoCropModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full max-h-[90vh] flex flex-col border border-[#7c8799] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.crop_logo') }}</h3>
                <button type="button" onclick="closeLogoCropModal()"
                    class="p-2 rounded-lg text-[#64748b] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-4 flex-1 min-h-0 overflow-hidden">
                <div class="max-h-[60vh] bg-[#0a0a0a] rounded-lg overflow-hidden">
                    <img id="logo-crop-image" src="" alt="Crop" class="max-w-full max-h-[60vh]">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-[#7c8799] dark:border-[#3E3E3A] flex gap-3 justify-end">
                <button type="button" onclick="closeLogoCropModal()"
                    class="btn-secondary">{{ __('suppliers.cancel') }}</button>
                <button type="button" id="logo-crop-apply" class="add-btn">{{ __('suppliers.apply') }}</button>
            </div>
        </div>
    </div>

    <script>
        const supplierWizardState = {
            step: 1,
            maxStep: 3,
        };

        function updateSupplierWizardUi() {
            const step = supplierWizardState.step;
            const sections = document.querySelectorAll('.supplier-step-section');
            sections.forEach((section) => {
                section.classList.toggle('active', parseInt(section.dataset.supplierStep, 10) === step);
            });

            document.querySelectorAll('[data-step-chip]').forEach((chip) => {
                const chipStep = parseInt(chip.dataset.stepChip, 10);
                chip.classList.toggle('active', chipStep === step);
                chip.classList.toggle('done', chipStep < step);
            });

            const caption = document.getElementById('supplier-step-caption');
            if (caption) {
                caption.textContent = `Шаг ${step} из ${supplierWizardState.maxStep}`;
            }

            const prevBtn = document.getElementById('supplier-step-prev');
            const nextBtn = document.getElementById('supplier-step-next');
            const submitBtn = document.getElementById('supplier-step-submit');

            if (prevBtn) {
                prevBtn.disabled = step === 1;
                prevBtn.classList.toggle('opacity-50', step === 1);
                prevBtn.classList.toggle('cursor-not-allowed', step === 1);
            }
            if (nextBtn) {
                nextBtn.classList.toggle('hidden', step === supplierWizardState.maxStep);
            }
            if (submitBtn) {
                submitBtn.classList.toggle('hidden', step !== supplierWizardState.maxStep);
            }
        }

        function validateSupplierStep(step) {
            const form = document.getElementById('supplier-form');
            if (!form) return true;
            const section = form.querySelector(`.supplier-step-section[data-supplier-step="${step}"]`);
            if (!section) return true;
            const requiredFields = section.querySelectorAll('[required]');
            for (const field of requiredFields) {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    return false;
                }
            }
            return true;
        }

        function goSupplierStep(nextStep) {
            const normalized = Math.max(1, Math.min(supplierWizardState.maxStep, nextStep));
            supplierWizardState.step = normalized;
            updateSupplierWizardUi();
        }

        function resetSupplierWizard() {
            supplierWizardState.step = 1;
            updateSupplierWizardUi();
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {


            // Brands / Cities add buttons
            const addBrand = () => {
                const inp = document.getElementById('brand_input');
                const val = inp?.value?.trim();
                if (!val) return;
                const tags = document.getElementById('brands-tags');
                const span = document.createElement('span');
                span.className =
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm text-[#0f172a] dark:text-[#EDEDEC]';
                span.innerHTML = val +
                    ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                const hi = document.createElement('input');
                hi.type = 'hidden';
                hi.name = 'brands[]';
                hi.value = val;
                span.appendChild(hi);
                tags.appendChild(span);
                inp.value = '';
            };
            const addCity = () => {
                const inp = document.getElementById('city_input');
                const val = inp?.value?.trim();
                if (!val) return;
                const tags = document.getElementById('cities-tags');
                const span = document.createElement('span');
                span.className =
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm text-[#0f172a] dark:text-[#EDEDEC]';
                span.innerHTML = val +
                    ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                const hi = document.createElement('input');
                hi.type = 'hidden';
                hi.name = 'cities_presence[]';
                hi.value = val;
                span.appendChild(hi);
                tags.appendChild(span);
                inp.value = '';
            };
            document.getElementById('add-brand-btn')?.addEventListener('click', addBrand);
            document.getElementById('add-city-btn')?.addEventListener('click', addCity);
            document.getElementById('supplier-step-prev')?.addEventListener('click', () => {
                goSupplierStep(supplierWizardState.step - 1);
            });
            document.getElementById('supplier-step-next')?.addEventListener('click', () => {
                if (!validateSupplierStep(supplierWizardState.step)) return;
                goSupplierStep(supplierWizardState.step + 1);
            });

            // Инициализация масок при первом открытии формы
            initSupplierMasks();
            resetSupplierWizard();

            // Кнопка добавления поставщика
            document.getElementById('add-supplier-btn')?.addEventListener('click', () => {
                document.getElementById('supplier-modal').classList.remove('hidden');
                document.getElementById('supplier-modal').classList.add('flex');
                document.getElementById('supplier-form').reset();
                document.getElementById('supplier_id').value = '';
                document.getElementById('supplier-modal-title').textContent =
                    '{{ __('suppliers.new_supplier') }}';
                document.getElementById('supplier-step-submit').textContent = 'Отправить на проверку';
                document.getElementById('brands-tags').innerHTML = '';
                document.getElementById('cities-tags').innerHTML = '';
                window.resetLogoPreview();
                resetSupplierWizard();
                if (supplierMasks.phone) {
                    supplierMasks.phone.value = '';
                    supplierMasks.whatsapp.value = '';
                }
            });

            // Логотип: выбор файла → открыть кроппер
            document.getElementById('logo-file-input')?.addEventListener('change', function(e) {
                const file = e.target.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;
                window.openLogoCropModal(file);
            });

            // Кнопка удаления логотипа
            document.getElementById('logo-remove-btn')?.addEventListener('click', function(e) {
                e.stopPropagation();
                window.resetLogoPreview(true);
            });
        });
    </script>
    <script src="{{ asset('js/crm-action-menu.js') }}"></script>
    @include('designer.suppliers.partials.crm-listing-scripts')

    <script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        const TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector(
            'input[name="_token"]')?.value;
        const PHONE_MASK = '+7 000 000 00 00';
        const supplierMasks = {};

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[c]));
        }

        function initSupplierMasks() {
            if (supplierMasks.phone) return;
            const phoneEl = document.getElementById('supplier-phone');
            const whatsappEl = document.getElementById('supplier-whatsapp');
            if (phoneEl && typeof IMask !== 'undefined') {
                supplierMasks.phone = IMask(phoneEl, {
                    mask: PHONE_MASK,
                    lazy: false
                });
                supplierMasks.whatsapp = IMask(whatsappEl, {
                    mask: PHONE_MASK,
                    lazy: false
                });
            }
        }

        function setPhoneMaskValue(mask, val) {
            if (!mask) return;
            const digits = val ? String(val).replace(/\D/g, '').replace(/^8/, '7').slice(-10) : '';
            mask.unmaskedValue = digits;
        }
        let logoCropper = null;
        let croppedLogoBlob = null;
        let logoCropObjectUrl = null;

        function updateLogoPreview(src) {
            const img = document.getElementById('logo-preview-img');
            const placeholder = document.getElementById('logo-preview-placeholder');
            const removeBtn = document.getElementById('logo-remove-btn');
            const editHint = document.getElementById('logo-edit-hint');
            if (src) {
                img.src = typeof src === 'string' ? src : URL.createObjectURL(src);
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
                if (removeBtn) removeBtn.classList.remove('hidden');
                if (editHint) editHint.classList.remove('hidden');
            } else {
                img.src = '';
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
                if (removeBtn) removeBtn.classList.add('hidden');
                if (editHint) editHint.classList.add('hidden');
            }
        }

        window.resetLogoPreview = function(removeExisting) {
            croppedLogoBlob = null;
            const fileInput = document.getElementById('logo-file-input');
            if (fileInput) fileInput.value = '';
            document.getElementById('remove_logo').value = removeExisting ? '1' : '0';
            updateLogoPreview(null);
        };

        function openLogoCropModalWithSource(src) {
            if (logoCropObjectUrl) {
                URL.revokeObjectURL(logoCropObjectUrl);
                logoCropObjectUrl = null;
            }
            const modal = document.getElementById('logo-crop-modal');
            const cropImg = document.getElementById('logo-crop-image');
            if (!modal || !cropImg) return;
            cropImg.src = typeof src === 'string' ? src : (logoCropObjectUrl = URL.createObjectURL(src));
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                if (logoCropper) logoCropper.destroy();
                logoCropper = new Cropper(cropImg, {
                    aspectRatio: 1,
                    viewMode: 2,
                    dragMode: 'move'
                });
            }, 50);
        }

        function openLogoCropModal(file) {
            const reader = new FileReader();
            reader.onload = function() {
                openLogoCropModalWithSource(reader.result);
            };
            reader.readAsDataURL(file);
        }

        window.handleLogoPreviewClick = function(e) {
            if (e.target.closest('#logo-remove-btn')) return;
            const img = document.getElementById('logo-preview-img');
            if (img && !img.classList.contains('hidden') && img.src) {
                openLogoCropModalWithSource(croppedLogoBlob || img.src);
            } else {
                document.getElementById('logo-file-input').click();
            }
        };

        function closeLogoCropModal() {
            const modal = document.getElementById('logo-crop-modal');
            if (logoCropper) {
                logoCropper.destroy();
                logoCropper = null;
            }
            if (logoCropObjectUrl) {
                URL.revokeObjectURL(logoCropObjectUrl);
                logoCropObjectUrl = null;
            }
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }

        document.getElementById('logo-crop-apply')?.addEventListener('click', function() {
            if (!logoCropper) return;
            const canvas = logoCropper.getCroppedCanvas({
                width: 400,
                height: 400
            });
            canvas.toBlob(function(blob) {
                croppedLogoBlob = blob;
                updateLogoPreview(blob);
                closeLogoCropModal();
            }, 'image/jpeg', 0.9);
        });

        function closeViewSupplierModal() {
            const modal = document.getElementById('view-supplier-modal');
            const panel = modal.querySelector('div[class*="absolute"]');
            modal.classList.add('hidden');
            if (panel) {
                panel.classList.add('translate-x-full');
                panel.classList.remove('translate-x-0');
            }
        }

        function renderRatingStars(rating) {
            const avg = rating && rating.average != null ? Number(rating.average) : 0;
            const count = rating && rating.count ? Number(rating.count) : 0;
            const rounded = Math.round(avg);
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += `<svg class="w-4 h-4 ${i <= rounded ? '' : 'opacity-30'}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/></svg>`;
            }
            const label = avg > 0 ? avg.toFixed(1) : '\u2014';
            return `<span class="inline-flex items-center gap-2"><span class="inline-flex items-center text-[#f59e0b]">${stars}</span><span class="text-sm text-[#64748b] dark:text-[#A1A09A]">${label}${count ? ` (${count})` : ''}</span></span>`;
        }

        async function viewSupplier(id) {
            try {
                let s = null;
                try {
                    const r = await fetch('{{ url('suppliers') }}/' + id, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (r.ok) {
                        s = await r.json();
                    }
                } catch (_) {}
                if (!s) {
                    s = (window.allSuppliers || []).find((x) => parseInt(x.id, 10) === parseInt(id, 10));
                }
                if (!s) {
                    return;
                }
                const content = document.getElementById('view-supplier-content');
                const brands = Array.isArray(s.brands) ? s.brands.join(', ') : (s.brands || '-');
                const cities = Array.isArray(s.cities_presence) ? s.cities_presence.join(', ') : (s.cities_presence ||
                    '-');
                const workTerms = s.work_terms_type && s.work_terms_value ?
                    `${s.work_terms_type === 'percent' ? '%' : '{{ __('suppliers.work_terms_amount') }}'}: ${s.work_terms_value}` :
                    '-';
                const orgForm = s.org_form === 'ip' ? '{{ __('suppliers.org_ip') }}' : '{{ __('suppliers.org_too') }}';
                const websiteHtml = s.website ?
                    `<a href="${escapeHtml(s.website)}" target="_blank" class="text-[#f59e0b] hover:underline">${escapeHtml(s.website)}</a>` :
                    '-';
                const logoHtml = s.logo_url ?
                    `<div class="mb-4"><img src="${s.logo_url}" alt="Logo" class="w-20 h-20 rounded-full object-cover border-2 border-[#7c8799] dark:border-[#3E3E3A]"></div>` :
                    '';
                const ratingBlock = `
                    <div class="pb-4 mb-1 border-b border-[#7c8799] dark:border-[#3E3E3A]">
                        <div class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1.5">{{ __('reviews.rating_label') }}</div>
                        ${renderRatingStars(s.rating)}
                    </div>`;
                content.innerHTML = logoHtml + ratingBlock + `
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.name || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.phone || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">Email</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.email || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">Telegram</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.telegram || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">WhatsApp</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.whatsapp || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${websiteHtml}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.city || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.address || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.sphere_display || s.sphere || '-')}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.brand') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(brands)}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.cities_presence') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(cities)}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.work_terms') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(workTerms)}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap">${escapeHtml(s.comment || '-')}</p></div>
            <div class="pt-4">
                <a href="${typeof appWithFrom === 'function' ? appWithFrom('/suppliers/' + s.id) : ('/suppliers/' + s.id)}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                    title="{{ __('suppliers.details') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('suppliers.details') }}
                </a>
            </div>
        `;
                const modal = document.getElementById('view-supplier-modal');
                const panel = modal?.querySelector('div[class*="absolute"]');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    if (panel) {
                        panel.classList.remove('translate-x-full');
                        panel.classList.add('translate-x-0');
                    }
                }, 10);
            } catch (e) {
                console.error(e);
            }
        }

        async function editSupplier(id) {
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const s = await r.json();
                document.getElementById('supplier-modal').classList.remove('hidden');
                document.getElementById('supplier-modal').classList.add('flex');
                document.getElementById('supplier-modal-title').textContent = '{{ __('suppliers.edit_supplier') }}';
                document.getElementById('supplier-step-submit').textContent = '{{ __('suppliers.save') }}';
                document.getElementById('supplier_id').value = s.id;
                document.querySelector('input[name="name"]').value = s.name || '';
                setPhoneMaskValue(supplierMasks.phone, s.phone);
                document.querySelector('input[name="email"]').value = s.email || '';
                document.querySelector('input[name="telegram"]').value = s.telegram || '';
                setPhoneMaskValue(supplierMasks.whatsapp, s.whatsapp);
                document.querySelector('input[name="website"]').value = s.website || '';
                document.querySelector('input[name="city"]').value = s.city || '';
                document.querySelector('input[name="address"]').value = s.address || '';
                const sphereSel = document.querySelector('select[name="sphere"]');
                if (sphereSel) sphereSel.value = s.sphere || '';
                const wt = document.querySelector('select[name="work_terms_type"]');
                if (wt) wt.value = s.work_terms_type || 'percent';
                document.querySelector('input[name="work_terms_value"]').value = s.work_terms_value || '';
                document.querySelector('input[name="comment_main"]').value = s.comment || '';
                document.querySelector('input[name="recommend"]').checked = !!s.recommend;
                document.querySelectorAll('input[name="org_form"]').forEach(rd => {
                    rd.checked = rd.value === (s.org_form || 'ooo');
                });
                document.querySelector('input[name="inn"]').value = s.inn || '';
                document.querySelector('input[name="kpp"]').value = s.kpp || '';
                document.querySelector('input[name="ogrn"]').value = s.ogrn || '';
                document.querySelector('input[name="okpo"]').value = s.okpo || '';
                document.querySelector('input[name="legal_address"]').value = s.legal_address || '';
                document.querySelector('input[name="actual_address"]').value = s.actual_address || '';
                document.querySelector('input[name="address_match"]').checked = !!s.address_match;
                document.querySelector('input[name="director"]').value = s.director || '';
                document.querySelector('input[name="accountant"]').value = s.accountant || '';
                document.querySelector('input[name="bik"]').value = s.bik || '';
                document.querySelector('input[name="bank"]').value = s.bank || '';
                document.querySelector('input[name="checking_account"]').value = s.checking_account || '';
                document.querySelector('input[name="corr_account"]').value = s.corr_account || '';
                document.querySelector('input[name="comment_bank"]').value = s.comment_bank || '';
                croppedLogoBlob = null;
                document.getElementById('remove_logo').value = '0';
                if (s.logo_url) {
                    updateLogoPreview(s.logo_url);
                } else {
                    resetLogoPreview(false);
                }
                const bt = document.getElementById('brands-tags');
                bt.innerHTML = '';
                (s.brands || []).forEach(b => {
                    const span = document.createElement('span');
                    span.className =
                        'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm';
                    span.innerHTML = b +
                        ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                    const hi = document.createElement('input');
                    hi.type = 'hidden';
                    hi.name = 'brands[]';
                    hi.value = b;
                    span.appendChild(hi);
                    bt.appendChild(span);
                });
                const ct = document.getElementById('cities-tags');
                ct.innerHTML = '';
                (s.cities_presence || []).forEach(c => {
                    const span = document.createElement('span');
                    span.className =
                        'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm';
                    span.innerHTML = c +
                        ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                    const hi = document.createElement('input');
                    hi.type = 'hidden';
                    hi.name = 'cities_presence[]';
                    hi.value = c;
                    span.appendChild(hi);
                    ct.appendChild(span);
                });
                resetSupplierWizard();
            } catch (e) {
                console.error(e);
            }
        }

        async function deleteSupplier(id) {
            if (!confirm('{{ __('suppliers.delete') }}?')) return;
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    }
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    projectAlert('error', data.message || '{{ __('suppliers.delete') }}', '', 3000);
                    return;
                }
                window.allSuppliers = (window.allSuppliers || []).filter((s) => parseInt(s.id, 10) !== parseInt(id, 10));
                window.renderSuppliersActiveTab?.();
                projectAlert('success', data.message || '{{ __('suppliers.deleted') }}', '', 2200);
            } catch (e) {
                console.error(e);
                projectAlert('error', '{{ __('objects.error') }}', '', 3000);
            }
        }

        async function toggleFavorite(id, btn) {
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id + '/toggle-favorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const d = await r.json();
                const idx = (window.allSuppliers || []).findIndex((s) => parseInt(s.id, 10) === parseInt(id, 10));
                if (idx >= 0) window.allSuppliers[idx].is_favorite = !!d.is_favorite;
                if (btn) {
                    btn.classList.toggle('active', !!d.is_favorite);
                    const svg = btn.querySelector('svg');
                    if (svg) svg.setAttribute('fill', d.is_favorite ? 'currentColor' : 'none');
                }
                window.renderSuppliersActiveTab?.();
            } catch (e) {
                console.error(e);
            }
        }

        function addOrderFromSupplier(supplierId) {
            // Перенаправляем на страницу поставок с предзаполненным поставщиком
            window.location.href = '{{ route('supplier-orders.index') }}?supplier_id=' + supplierId;
        }

        function closeSupplierModal() {
            const modal = document.getElementById('supplier-modal');
            modal.classList.add('modal-closing');
            setTimeout(() => {
                modal.classList.remove('modal-closing', 'flex');
                modal.classList.add('hidden');
                document.getElementById('supplier-form').reset();
                window.resetLogoPreview?.();
                resetSupplierWizard();
            }, 280);
        }

        // Обработка формы поставщика
        document.getElementById('supplier-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (supplierWizardState.step < supplierWizardState.maxStep) {
                if (!validateSupplierStep(supplierWizardState.step)) return;
                goSupplierStep(supplierWizardState.step + 1);
                return;
            }
            const form = e.target;
            if (!window.lockSubmit(form)) return;
            const id = form.querySelector('#supplier_id').value;
            const url = id ? '{{ url('suppliers') }}/' + id : '{{ route('suppliers.store') }}';
            const fd = new FormData(form);
            fd.append('_token', TOKEN);
            if (id) fd.append('_method', 'PUT');
            fd.delete('supplier_id');
            fd.delete('brand_input');
            fd.delete('city_input');
            fd.delete('logo');
            if (croppedLogoBlob) {
                fd.append('logo', croppedLogoBlob, 'logo.jpg');
            }
            const removeLogo = document.getElementById('remove_logo').value;
            if (removeLogo === '1') fd.set('remove_logo', '1');
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    },
                    body: fd
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok && data?.supplier) {
                    const s = data.supplier;
                    const list = window.allSuppliers || [];
                    const i = list.findIndex((x) => parseInt(x.id, 10) === parseInt(s.id, 10));
                    if (i >= 0) list[i] = s;
                    else list.unshift(s);
                    window.allSuppliers = list;
                    closeSupplierModal();
                    window.renderSuppliersActiveTab?.();
                    projectAlert('success', data.message || '{{ __('suppliers.updated') }}', '', 2400);
                } else {
                    projectAlert('error', data.message || '{{ __('objects.error') }}', '', 3200);
                }
            } catch (err) {
                projectAlert('error', '{{ __('objects.error') }}', '', 3200);
            } finally {
                window.unlockSubmit(form);
            }
        });
    </script>
@endsection
