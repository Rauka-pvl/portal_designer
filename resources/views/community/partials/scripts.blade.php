<script>
(function () {
    const app = document.getElementById('community-app');
    if (!app) return;

    const csrf = app.dataset.csrf;
    const storeUrl = app.dataset.storeUrl;
    const maxImages = parseInt(app.dataset.maxImages || '10', 10);
    const maxKb = parseInt(app.dataset.maxKb || '5120', 10);
    const maxText = parseInt(app.dataset.maxText || '2000', 10);
    const i18n = JSON.parse(app.dataset.i18n || '{}');

    const postModal = document.getElementById('community-post-modal');
    const deleteModal = document.getElementById('community-delete-modal');
    const reportModal = document.getElementById('community-report-modal');
    const lightbox = document.getElementById('community-lightbox');
    const feedList = document.getElementById('community-feed-list');
    const textEl = document.getElementById('community-post-text');
    const submitBtn = document.getElementById('community-submit-post');
    const charsEl = document.getElementById('community-chars');
    const previews = document.getElementById('community-image-previews');
    const fileInput = document.getElementById('community-post-images');
    const progressWrap = document.getElementById('community-upload-progress');
    const progressBar = progressWrap ? progressWrap.querySelector('div') : null;

    let selectedFiles = [];
    let keepMedia = [];
    let dirty = false;
    let editingId = null;
    let deleteTargetId = null;
    let reportTargetId = null;
    let lightboxUrls = [];
    let lightboxIndex = 0;
    let lastFocus = null;
    let submitting = false;

    function alertToast(type, msg) {
        if (typeof projectAlert === 'function') projectAlert(type, msg, '', 2800);
    }

    function openModal(el) {
        lastFocus = document.activeElement;
        el.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const focusable = el.querySelector('textarea, button, select, input');
        if (focusable) focusable.focus();
    }

    function closeModal(el) {
        el.classList.add('hidden');
        if (![postModal, deleteModal, reportModal, lightbox].some(m => m && !m.classList.contains('hidden'))) {
            document.body.style.overflow = '';
        }
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    function updateSubmitState() {
        const hasText = (textEl.value || '').trim().length > 0;
        const hasMedia = selectedFiles.length > 0 || keepMedia.length > 0;
        submitBtn.disabled = !(hasText || hasMedia) || submitting;
    }

    function updateChars() {
        const len = (textEl.value || '').length;
        const left = maxText - len;
        if (left <= 200) {
            charsEl.classList.remove('hidden');
            charsEl.textContent = (i18n.chars_left || 'Chars: :count').replace(':count', String(left));
        } else {
            charsEl.classList.add('hidden');
        }
        dirty = true;
        updateSubmitState();
    }

    function renderPreviews() {
        previews.innerHTML = '';
        keepMedia.forEach((m, idx) => {
            const item = document.createElement('div');
            item.className = 'relative aspect-square rounded-lg overflow-hidden border border-[#3E3E3A]';
            item.innerHTML = `<img src="${m.url}" class="w-full h-full object-cover" alt=""><button type="button" data-keep="${m.id}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-sm">×</button>`;
            previews.appendChild(item);
        });
        selectedFiles.forEach((file, idx) => {
            const url = URL.createObjectURL(file);
            const item = document.createElement('div');
            item.className = 'relative aspect-square rounded-lg overflow-hidden border border-[#3E3E3A]';
            item.draggable = true;
            item.dataset.fileIndex = String(idx);
            item.innerHTML = `<img src="${url}" class="w-full h-full object-cover" alt=""><button type="button" data-file="${idx}" class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 text-white text-sm">×</button>`;
            previews.appendChild(item);
        });
        updateSubmitState();
    }

    function resetForm() {
        editingId = null;
        selectedFiles = [];
        keepMedia = [];
        dirty = false;
        textEl.value = '';
        document.getElementById('community-post-id').value = '';
        document.getElementById('community-post-category').value = '';
        document.getElementById('community-post-modal-title').textContent = @json(__('community.new_post'));
        submitBtn.querySelector('.label').textContent = @json(__('community.publish'));
        fileInput.value = '';
        renderPreviews();
        updateChars();
        dirty = false;
    }

    function openCreate() {
        resetForm();
        openModal(postModal);
    }

    function openEdit(card) {
        resetForm();
        editingId = card.dataset.postId;
        document.getElementById('community-post-id').value = editingId;
        document.getElementById('community-post-modal-title').textContent = @json(__('community.edit_post'));
        submitBtn.querySelector('.label').textContent = @json(__('community.save_changes'));
        const textNode = card.querySelector('.community-text');
        textEl.value = textNode ? (textNode.dataset.full || textNode.textContent.trim()) : '';
        document.getElementById('community-post-category').value = card.dataset.category || '';
        document.getElementById('community-post-city').value = card.dataset.city || document.getElementById('community-post-city').value;
        const mediaBtns = card.querySelectorAll('.community-open-lightbox');
        let urls = [];
        try { urls = mediaBtns[0] ? JSON.parse(mediaBtns[0].dataset.urls || '[]') : []; } catch (e) { urls = []; }
        keepMedia = Array.from(mediaBtns).map((btn, i) => ({
            id: parseInt(btn.dataset.mediaId || '0', 10) || 0,
            url: urls[i] || btn.querySelector('img')?.src || '',
        })).filter(m => m.id > 0 || m.url);
        updateChars();
        dirty = false;
        renderPreviews();
        openModal(postModal);
    }

    async function submitPost() {
        if (submitting) return;
        const hasText = (textEl.value || '').trim().length > 0;
        const hasMedia = selectedFiles.length > 0 || keepMedia.filter(m => m.id).length > 0 || keepMedia.length > 0;
        if (!hasText && selectedFiles.length === 0 && keepMedia.filter(m => m.id > 0).length === 0) {
            alertToast('error', i18n.empty_post || 'Empty');
            return;
        }
        submitting = true;
        updateSubmitState();
        const fd = new FormData();
        fd.append('text', textEl.value || '');
        fd.append('category', document.getElementById('community-post-category').value || '');
        fd.append('city', document.getElementById('community-post-city').value || '');
        keepMedia.filter(m => m.id > 0).forEach(m => fd.append('keep_media_ids[]', String(m.id)));
        selectedFiles.forEach(f => fd.append('images[]', f));

        const url = editingId ? (`{{ url('/community/posts') }}/` + editingId) : storeUrl;
        if (progressWrap) {
            progressWrap.classList.remove('hidden');
            if (progressBar) progressBar.style.width = '15%';
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });
            if (progressBar) progressBar.style.width = '85%';
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || i18n.publish_error);
            if (progressBar) progressBar.style.width = '100%';
            alertToast('success', data.message || (editingId ? i18n.updated : i18n.created));
            dirty = false;
            closeModal(postModal);
            if (editingId) {
                const card = feedList ? feedList.querySelector(`[data-post-id="${editingId}"]`) : document.querySelector(`[data-post-id="${editingId}"]`);
                if (card && data.html) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    card.replaceWith(tmp.firstElementChild);
                } else {
                    location.reload();
                }
            } else if (feedList && data.html) {
                const empty = feedList.querySelector('.text-center');
                if (empty && empty.closest('.rounded-2xl')) empty.closest('.rounded-2xl').remove();
                feedList.insertAdjacentHTML('afterbegin', data.html);
            } else {
                location.reload();
            }
            resetForm();
        } catch (e) {
            alertToast('error', e.message || i18n.publish_error);
        } finally {
            submitting = false;
            updateSubmitState();
            if (progressWrap) {
                setTimeout(() => {
                    progressWrap.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }, 400);
            }
        }
    }

    async function toggleLike(card) {
        const id = card.dataset.postId;
        const was = card.dataset.liked === '1';
        const likes = parseInt(card.dataset.likes || '0', 10);
        const btn = card.querySelector('.community-like');
        const svg = btn.querySelector('svg');
        card.dataset.liked = was ? '0' : '1';
        card.dataset.likes = String(was ? Math.max(0, likes - 1) : likes + 1);
        btn.classList.toggle('text-[#f59e0b]', !was);
        btn.setAttribute('aria-pressed', was ? 'false' : 'true');
        if (svg) svg.setAttribute('fill', was ? 'none' : 'currentColor');
        try {
            const res = await fetch(`{{ url('/community/posts') }}/${id}/like`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || i18n.like_error);
            card.dataset.liked = data.is_liked ? '1' : '0';
            card.dataset.likes = String(data.likes_count);
        } catch (e) {
            card.dataset.liked = was ? '1' : '0';
            card.dataset.likes = String(likes);
            btn.classList.toggle('text-[#f59e0b]', was);
            btn.setAttribute('aria-pressed', was ? 'true' : 'false');
            if (svg) svg.setAttribute('fill', was ? 'currentColor' : 'none');
            alertToast('error', e.message || i18n.like_error);
        }
    }

    async function toggleSave(card) {
        const id = card.dataset.postId;
        const was = card.dataset.saved === '1';
        const btn = card.querySelector('.community-save');
        const svg = btn.querySelector('svg');
        const label = card.querySelector('.label-save-btn');
        const menuLabel = card.querySelector('.label-save');
        card.dataset.saved = was ? '0' : '1';
        btn.classList.toggle('text-[#f59e0b]', !was);
        btn.setAttribute('aria-pressed', was ? 'false' : 'true');
        if (svg) svg.setAttribute('fill', was ? 'none' : 'currentColor');
        if (label) label.textContent = was ? (i18n.save_label || 'Save') : (i18n.saved_label || 'Saved');
        if (menuLabel) menuLabel.textContent = was ? (i18n.menu_save || 'Save') : (i18n.menu_unsave || 'Unsave');
        try {
            const res = await fetch(`{{ url('/community/posts') }}/${id}/save`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message || i18n.save_error);
            card.dataset.saved = data.is_saved ? '1' : '0';
            if (data.message) alertToast('success', data.message);
        } catch (e) {
            card.dataset.saved = was ? '1' : '0';
            btn.classList.toggle('text-[#f59e0b]', was);
            btn.setAttribute('aria-pressed', was ? 'true' : 'false');
            if (svg) svg.setAttribute('fill', was ? 'currentColor' : 'none');
            if (label) label.textContent = was ? (i18n.saved_label || 'Saved') : (i18n.save_label || 'Save');
            alertToast('error', e.message || i18n.save_error);
        }
    }

    function copyLink(id) {
        const url = `{{ url('/community/post') }}/${id}`;
        navigator.clipboard.writeText(url).then(() => alertToast('success', i18n.link_copied || 'Copied'));
    }

    function openLightbox(urls, index) {
        lightboxUrls = urls || [];
        lightboxIndex = index || 0;
        document.getElementById('community-lightbox-img').src = lightboxUrls[lightboxIndex] || '';
        openModal(lightbox);
    }

    function stepLightbox(dir) {
        if (!lightboxUrls.length) return;
        lightboxIndex = (lightboxIndex + dir + lightboxUrls.length) % lightboxUrls.length;
        document.getElementById('community-lightbox-img').src = lightboxUrls[lightboxIndex];
    }

    // Events
    document.getElementById('community-open-create')?.addEventListener('click', openCreate);
    document.getElementById('community-fab')?.addEventListener('click', openCreate);
    document.getElementById('community-composer-open')?.addEventListener('click', openCreate);
    document.querySelectorAll('.community-open-create-btn').forEach(b => b.addEventListener('click', openCreate));

    textEl?.addEventListener('input', updateChars);
    fileInput?.addEventListener('change', () => {
        const files = Array.from(fileInput.files || []);
        files.forEach(f => {
            if (selectedFiles.length + keepMedia.length >= maxImages) return;
            if (f.size > maxKb * 1024) {
                alertToast('error', i18n.image_error || @json(__('community.errors.image')));
                return;
            }
            selectedFiles.push(f);
        });
        fileInput.value = '';
        dirty = true;
        renderPreviews();
    });

    previews?.addEventListener('click', (e) => {
        const keepBtn = e.target.closest('[data-keep]');
        const fileBtn = e.target.closest('[data-file]');
        if (keepBtn) {
            const id = parseInt(keepBtn.dataset.keep, 10);
            keepMedia = keepMedia.filter(m => m.id !== id && !(m.id === 0 && false));
            keepMedia = keepMedia.filter((m, i, arr) => {
                // remove by matching button context - filter by id
                return m.id !== id;
            });
            // also remove keep with id 0 by index? use closest
            const allKeep = Array.from(previews.querySelectorAll('[data-keep]'));
            // simpler: rebuild from remaining
            const remainKeep = [];
            previews.querySelectorAll('[data-keep]').forEach(btn => {
                if (btn === keepBtn) return;
                const mid = parseInt(btn.dataset.keep, 10);
                const found = keepMedia.find(m => m.id === mid) || { id: mid, url: btn.parentElement.querySelector('img')?.src };
                remainKeep.push(found);
            });
            keepMedia = remainKeep;
            dirty = true;
            renderPreviews();
        }
        if (fileBtn) {
            const idx = parseInt(fileBtn.dataset.file, 10);
            selectedFiles.splice(idx, 1);
            dirty = true;
            renderPreviews();
        }
    });

    submitBtn?.addEventListener('click', submitPost);

    document.querySelectorAll('.community-close-post-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            if (dirty && (textEl.value || selectedFiles.length || keepMedia.length)) {
                if (!confirm(i18n.unsaved_title || 'Save changes?')) {
                    dirty = false;
                    closeModal(postModal);
                    resetForm();
                } else {
                    submitPost();
                }
                return;
            }
            closeModal(postModal);
            resetForm();
        });
    });

    document.querySelectorAll('.community-close-delete').forEach(b => b.addEventListener('click', () => closeModal(deleteModal)));
    document.querySelectorAll('.community-close-report').forEach(b => b.addEventListener('click', () => closeModal(reportModal)));
    postModal?.querySelector('.community-modal-backdrop')?.addEventListener('click', () => document.querySelector('.community-close-post-modal')?.click());
    deleteModal?.querySelector('.community-modal-backdrop')?.addEventListener('click', () => closeModal(deleteModal));
    reportModal?.querySelector('.community-modal-backdrop')?.addEventListener('click', () => closeModal(reportModal));

    document.getElementById('community-confirm-delete')?.addEventListener('click', async () => {
        if (!deleteTargetId) return;
        try {
            const res = await fetch(`{{ url('/community/posts') }}/${deleteTargetId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message);
            document.querySelector(`[data-post-id="${deleteTargetId}"]`)?.remove();
            alertToast('success', data.message || i18n.deleted);
            closeModal(deleteModal);
            if (location.pathname.includes('/community/post/')) {
                location.href = '{{ route('community.index') }}';
            }
        } catch (e) {
            alertToast('error', e.message || i18n.publish_error);
        }
    });

    document.getElementById('community-submit-report')?.addEventListener('click', async () => {
        if (!reportTargetId) return;
        const form = document.getElementById('community-report-form');
        const fd = new FormData(form);
        try {
            const res = await fetch(`{{ url('/community/posts') }}/${reportTargetId}/report`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.message);
            alertToast('success', data.message || i18n.reported);
            closeModal(reportModal);
        } catch (e) {
            alertToast('error', e.message || i18n.publish_error);
        }
    });

    document.addEventListener('click', (e) => {
        const menuBtn = e.target.closest('.community-menu-btn');
        if (menuBtn) {
            const menu = menuBtn.parentElement.querySelector('.community-menu');
            document.querySelectorAll('.community-menu').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
            menuBtn.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
            return;
        }
        if (!e.target.closest('.community-menu')) {
            document.querySelectorAll('.community-menu').forEach(m => m.classList.add('hidden'));
        }

        const card = e.target.closest('.community-card');
        if (!card) return;

        if (e.target.closest('.community-like')) { e.preventDefault(); toggleLike(card); return; }
        if (e.target.closest('.community-save') || e.target.closest('.community-toggle-save-menu')) { e.preventDefault(); toggleSave(card); return; }
        if (e.target.closest('.community-share') || e.target.closest('.community-copy')) { e.preventDefault(); copyLink(card.dataset.postId); return; }
        if (e.target.closest('.community-edit')) { e.preventDefault(); openEdit(card); return; }
        if (e.target.closest('.community-delete')) {
            e.preventDefault();
            deleteTargetId = card.dataset.postId;
            openModal(deleteModal);
            return;
        }
        if (e.target.closest('.community-report')) {
            e.preventDefault();
            reportTargetId = card.dataset.postId;
            openModal(reportModal);
            return;
        }
        if (e.target.closest('.community-hide')) {
            e.preventDefault();
            fetch(`{{ url('/community/posts') }}/${card.dataset.postId}/hide`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(r => r.json()).then(data => {
                card.remove();
                alertToast('success', data.message || i18n.hidden);
            });
            return;
        }
        if (e.target.closest('.community-toggle-text')) {
            const text = card.querySelector('.community-text');
            const btn = e.target.closest('.community-toggle-text');
            const clamped = text.classList.contains('is-clamped');
            text.classList.toggle('is-clamped', !clamped);
            btn.textContent = clamped ? (i18n.show_less || 'Less') : (i18n.show_more || 'More');
            return;
        }
        const lbBtn = e.target.closest('.community-open-lightbox');
        if (lbBtn) {
            let urls = [];
            try { urls = JSON.parse(lbBtn.dataset.urls || '[]'); } catch (err) {}
            openLightbox(urls, parseInt(lbBtn.dataset.index || '0', 10));
        }
    });

    document.getElementById('community-lightbox-close')?.addEventListener('click', () => closeModal(lightbox));
    document.getElementById('community-lightbox-prev')?.addEventListener('click', () => stepLightbox(-1));
    document.getElementById('community-lightbox-next')?.addEventListener('click', () => stepLightbox(1));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!lightbox.classList.contains('hidden')) closeModal(lightbox);
            else if (!deleteModal.classList.contains('hidden')) closeModal(deleteModal);
            else if (!reportModal.classList.contains('hidden')) closeModal(reportModal);
            else if (!postModal.classList.contains('hidden')) document.querySelector('.community-close-post-modal')?.click();
        }
        if (!lightbox.classList.contains('hidden')) {
            if (e.key === 'ArrowLeft') stepLightbox(-1);
            if (e.key === 'ArrowRight') stepLightbox(1);
        }
    });

    // Enhance card media with media ids if present in DOM via data on images - patch cards after load
    document.querySelectorAll('.community-card').forEach(card => {
        // no-op placeholder
    });

    // Auto-submit search filters on change
    document.querySelector('select[name="category"]')?.addEventListener('change', (e) => e.target.form.submit());
})();
</script>
