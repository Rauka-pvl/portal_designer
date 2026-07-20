<script>
(function () {
    const modal = document.getElementById('product-qr-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const i18n = {
        loadError: @json(__('products.qr_load_error')),
        copied: @json(__('products.qr_copied')),
        reissueConfirm: @json(__('products.qr_reissue_confirm')),
    };

    let currentProductId = null;
    let lastFocus = null;

    const els = {
        name: document.getElementById('product-qr-name'),
        sku: document.getElementById('product-qr-sku'),
        image: document.getElementById('product-qr-image'),
        preview: document.getElementById('product-qr-preview'),
        url: document.getElementById('product-qr-url'),
        png: document.getElementById('product-qr-download-png'),
        svg: document.getElementById('product-qr-download-svg'),
        print: document.getElementById('product-qr-print'),
        open: document.getElementById('product-qr-open-link'),
        error: document.getElementById('product-qr-error'),
        copy: document.getElementById('product-qr-copy'),
        reissue: document.getElementById('product-qr-reissue'),
        close: document.getElementById('product-qr-close'),
    };

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        if (lastFocus) lastFocus.focus();
    }

    function showError(msg) {
        if (!els.error) return;
        els.error.textContent = msg || i18n.loadError;
        els.error.classList.remove('hidden');
    }

    function fill(data) {
        els.error?.classList.add('hidden');
        els.name.textContent = data.product?.name || '';
        els.sku.textContent = data.product?.sku ? ('SKU: ' + data.product.sku) : '';
        if (data.product?.image_url) {
            els.image.src = data.product.image_url;
            els.image.classList.remove('hidden');
        } else {
            els.image.classList.add('hidden');
            els.image.removeAttribute('src');
        }
        els.preview.innerHTML = data.preview_svg || '';
        els.url.value = data.url || '';
        els.png.href = data.download_png || '#';
        els.svg.href = data.download_svg || '#';
        els.print.href = data.print_url || '#';
        els.open.href = data.open_url || '#';
        if (!data.png_available) {
            els.png.classList.add('opacity-50', 'pointer-events-none');
            els.png.title = 'PNG requires PHP GD';
        } else {
            els.png.classList.remove('opacity-50', 'pointer-events-none');
            els.png.removeAttribute('title');
        }
    }

    async function loadQr(productId) {
        currentProductId = productId;
        els.preview.innerHTML = '<div class="text-xs text-[#94a3b8]">…</div>';
        openModal();
        try {
            const res = await fetch(`{{ url('/supplier/products') }}/${productId}/qr`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || i18n.loadError);
            fill(data);
        } catch (e) {
            showError(e.message || i18n.loadError);
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.product-qr-btn, #product-qr-open');
        if (btn) {
            e.preventDefault();
            lastFocus = btn;
            const id = btn.getAttribute('data-product-id');
            if (id) loadQr(id);
        }
    });

    els.close?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    els.copy?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(els.url.value || '');
            const prev = els.copy.textContent;
            els.copy.textContent = i18n.copied;
            setTimeout(() => { els.copy.textContent = prev; }, 1500);
        } catch (err) {
            els.url.select();
            document.execCommand('copy');
        }
    });

    els.reissue?.addEventListener('click', async () => {
        if (!currentProductId) return;
        if (!confirm(i18n.reissueConfirm)) return;
        try {
            const res = await fetch(`{{ url('/supplier/products') }}/${currentProductId}/qr/reissue`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || i18n.loadError);
            fill({
                product: {
                    name: els.name.textContent,
                    sku: (els.sku.textContent || '').replace(/^SKU:\s*/, ''),
                    image_url: els.image.classList.contains('hidden') ? null : els.image.src,
                },
                url: data.url,
                preview_svg: data.preview_svg,
                download_png: data.download_png,
                download_svg: data.download_svg,
                print_url: data.print_url,
                open_url: els.open.href,
                png_available: !els.png.classList.contains('pointer-events-none'),
            });
        } catch (err) {
            showError(err.message || i18n.loadError);
        }
    });
})();
</script>
