@extends('layouts.supplier')

@section('title', __('products.title'))
@section('header_title', __('products.title'))

@section('content')
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('products.subtitle') }}</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="btn-add-product"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('products.add_manual') }}
            </button>
            <button type="button" id="btn-import-product"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] text-sm font-medium hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('products.import_excel') }}
            </button>
        </div>
    </div>

    @if (session('products_status'))
        <div class="mb-4 rounded-lg border border-[#22c55e]/40 bg-[#22c55e]/10 px-4 py-3 text-sm text-[#15803d] dark:text-[#4ade80]">
            {{ session('products_status') }}
        </div>
    @endif
    @if (session('products_error'))
        <div class="mb-4 rounded-lg border border-[#ef4444]/40 bg-[#ef4444]/10 px-4 py-3 text-sm text-[#b91c1c] dark:text-[#f87171]">
            {{ session('products_error') }}
        </div>
    @endif

    <div class="mb-6">
        <input type="text" id="products-search" placeholder="{{ __('products.search_placeholder') }}"
            class="w-full md:max-w-md px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
    </div>

    <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach ($products as $product)
            <div data-product-search="{{ mb_strtolower($product->name.' '.$product->sku.' '.$product->category) }}">
                @include('partials.product-card', ['product' => $product, 'editable' => true, 'detailUrl' => route('supplier.products.show', $product->id)])
            </div>
        @endforeach
    </div>

    <div id="products-empty" class="rounded-xl border border-dashed border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-10 text-center text-[#64748b] dark:text-[#A1A09A] {{ $products->isEmpty() ? '' : 'hidden' }}">
        {{ __('products.empty') }}
    </div>

    {{-- Модалка: добавить вручную --}}
    <div id="add-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A]">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.add_manual') }}</h3>
                <button type="button" class="modal-close text-[#94a3b8] hover:text-[#ef4444]">✕</button>
            </div>
            <form id="add-product-form" class="p-5 space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_name') }} *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_sku') }}</label>
                        <input type="text" name="sku" class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_category') }}</label>
                        <input type="text" name="category" class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_price') }}</label>
                        <input type="number" step="0.01" min="0" name="price" class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_unit') }}</label>
                        <input type="text" name="unit" class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_description') }}</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] resize-none focus:outline-none focus:border-[#f59e0b]"></textarea>
                </div>
                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.f_image') }}</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-[#64748b] dark:text-[#A1A09A]">
                </div>
                <p id="add-product-error" class="hidden text-sm text-[#ef4444]"></p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="modal-close px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('products.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">{{ __('products.add') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Модалка: импорт через шаблон --}}
    <div id="import-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A]">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.import_excel') }}</h3>
                <button type="button" class="modal-close text-[#94a3b8] hover:text-[#ef4444]">✕</button>
            </div>
            <form action="{{ route('supplier.products.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('products.import_hint') }}</p>
                <a href="{{ route('supplier.products.template') }}"
                    class="inline-flex items-center gap-1.5 text-sm text-[#f59e0b] hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4"/></svg>
                    {{ __('products.download_template') }}
                </a>
                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('products.file') }}</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full text-sm text-[#64748b] dark:text-[#A1A09A]">
                    @error('file')<p class="mt-1 text-sm text-[#ef4444]">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="modal-close px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('products.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">{{ __('products.import') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const base = '{{ url('supplier/products') }}';

            // ---- Модалки ----
            function openModal(id) {
                const m = document.getElementById(id);
                m.classList.remove('hidden');
                m.classList.add('flex');
            }
            function closeModal(m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
            }
            document.getElementById('btn-add-product')?.addEventListener('click', () => openModal('add-product-modal'));
            document.getElementById('btn-import-product')?.addEventListener('click', () => openModal('import-product-modal'));
            document.querySelectorAll('.modal-close').forEach((b) => b.addEventListener('click', function () {
                closeModal(this.closest('.fixed'));
            }));
            document.querySelectorAll('#add-product-modal, #import-product-modal').forEach((m) => {
                m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
            });

            // ---- Поиск ----
            const search = document.getElementById('products-search');
            search?.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('#products-grid > [data-product-search]').forEach((el) => {
                    const hay = el.getAttribute('data-product-search') || '';
                    el.style.display = (!q || hay.includes(q)) ? '' : 'none';
                });
            });

            // ---- Автосохранение при потере фокуса ----
            function showSaved(card) {
                const hint = card.querySelector('.product-save-hint');
                if (!hint) return;
                hint.style.opacity = '1';
                setTimeout(() => { hint.style.opacity = '0'; }, 1500);
            }

            document.getElementById('products-grid')?.addEventListener('focusout', function (e) {
                const field = e.target.closest('.product-edit');
                if (!field) return;
                const card = field.closest('.product-card');
                const id = card?.getAttribute('data-product-id');
                const name = field.getAttribute('data-field');
                if (!id || !name) return;

                const initial = field.dataset.initial ?? '';
                if (field.value === initial) return;
                field.dataset.initial = field.value;

                const body = new URLSearchParams();
                body.set('_token', csrf);
                body.set(name, field.value);

                fetch(base + '/' + id, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                }).then((r) => r.json()).then((data) => {
                    if (data.ok) showSaved(card);
                }).catch(() => {});
            });
            // запоминаем исходные значения
            document.querySelectorAll('#products-grid .product-edit').forEach((f) => { f.dataset.initial = f.value; });

            // ---- Смена фото прямо в карточке ----
            document.getElementById('products-grid')?.addEventListener('change', function (e) {
                const input = e.target.closest('.product-image-input');
                if (!input || !input.files.length) return;
                const card = input.closest('.product-card');
                const id = card?.getAttribute('data-product-id');
                if (!id) return;
                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('image', input.files[0]);
                fetch(base + '/' + id + '/image', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                }).then((r) => r.json()).then((data) => {
                    if (data.ok && data.image_url) {
                        const img = card.querySelector('.product-card__img');
                        img.src = data.image_url;
                        img.classList.remove('opacity-40');
                        showSaved(card);
                    }
                }).catch(() => {});
            });

            // ---- Удаление ----
            document.getElementById('products-grid')?.addEventListener('click', function (e) {
                const btn = e.target.closest('.product-delete');
                if (!btn) return;
                if (!confirm('{{ __('products.confirm_delete') }}')) return;
                const wrapper = btn.closest('[data-product-search]');
                const card = btn.closest('.product-card');
                const id = card?.getAttribute('data-product-id');
                if (!id) return;
                const body = new URLSearchParams();
                body.set('_token', csrf);
                body.set('_method', 'DELETE');
                fetch(base + '/' + id, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                }).then((r) => r.json()).then((data) => {
                    if (data.ok) {
                        wrapper.remove();
                        if (!document.querySelector('#products-grid > [data-product-search]')) {
                            document.getElementById('products-empty').classList.remove('hidden');
                        }
                    }
                }).catch(() => {});
            });

            // ---- Добавить вручную ----
            document.getElementById('add-product-form')?.addEventListener('submit', function (e) {
                e.preventDefault();
                const errBox = document.getElementById('add-product-error');
                errBox.classList.add('hidden');
                const fd = new FormData(this);
                fd.append('_token', csrf);
                fetch(base, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                }).then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (r.ok && data.ok) {
                        window.location.reload();
                    } else {
                        errBox.textContent = data.message || '{{ __('products.error_generic') }}';
                        errBox.classList.remove('hidden');
                    }
                }).catch(() => {
                    errBox.textContent = '{{ __('products.error_generic') }}';
                    errBox.classList.remove('hidden');
                });
            });
        })();
    </script>
@endpush
