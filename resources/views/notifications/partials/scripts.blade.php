<script>
(function () {
    const app = document.getElementById('notifications-app');
    if (!app) return;

    const csrf = app.dataset.csrf;
    const i18n = JSON.parse(app.dataset.i18n || '{}');
    const list = document.getElementById('notifications-list');
    const readAllBtn = document.getElementById('notifications-read-all');
    const unreadTabCount = document.getElementById('notifications-unread-tab-count');

    function toast(type, message) {
        if (typeof projectAlert === 'function') projectAlert(type, message, '', 2600);
    }

    function updateSidebarCount(count) {
        app.dataset.unreadTotal = String(count);
        document.querySelectorAll('[data-unread-notifications]').forEach((el) => {
            if (count > 0) {
                el.textContent = String(count);
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
        if (unreadTabCount) {
            if (count > 0) {
                unreadTabCount.textContent = String(count);
                unreadTabCount.classList.remove('hidden');
            } else {
                unreadTabCount.classList.add('hidden');
            }
        }
        if (readAllBtn) {
            readAllBtn.classList.toggle('hidden', count <= 0);
        }
    }

    function setRowReadState(row, isRead) {
        row.dataset.read = isRead ? '1' : '0';
        row.classList.toggle('is-unread', !isRead);
        const dot = row.querySelector('[data-unread-dot]');
        if (dot) dot.classList.toggle('invisible', isRead);
        const title = row.querySelector('[data-title]');
        if (title) {
            title.classList.toggle('font-semibold', !isRead);
            title.classList.toggle('font-medium', isRead);
        }
        const toggle = row.querySelector('.n-mark-toggle');
        if (toggle) {
            toggle.dataset.mode = isRead ? 'unread' : 'read';
            toggle.textContent = isRead ? (i18n.mark_unread || '') : (i18n.mark_read || '');
        }
    }

    async function api(url, method = 'POST') {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.ok === false) {
            throw new Error(data.message || 'Error');
        }
        return data;
    }

    async function markRead(row, silent = false) {
        if (row.dataset.read === '1') return;
        const data = await api(row.dataset.readUrl, 'POST');
        setRowReadState(row, true);
        if (typeof data.unread_count === 'number') updateSidebarCount(data.unread_count);
        if (!silent && data.message) toast('success', data.message);
    }

    document.addEventListener('click', async (e) => {
        const menuBtn = e.target.closest('.n-menu-btn');
        if (menuBtn) {
            e.preventDefault();
            e.stopPropagation();
            const menu = menuBtn.parentElement.querySelector('.n-menu');
            document.querySelectorAll('.n-menu').forEach((m) => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
            menuBtn.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
            return;
        }

        if (!e.target.closest('.n-menu')) {
            document.querySelectorAll('.n-menu').forEach((m) => m.classList.add('hidden'));
        }

        if (e.target.closest('.n-actions-stop')) {
            // handled below, but don't trigger row navigation
        }

        const row = e.target.closest('.notification-row');
        if (!row) return;

        const markToggle = e.target.closest('.n-mark-toggle');
        if (markToggle) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const mode = markToggle.dataset.mode;
                const url = mode === 'read' ? row.dataset.readUrl : row.dataset.unreadUrl;
                const data = await api(url, 'POST');
                setRowReadState(row, mode === 'read');
                if (typeof data.unread_count === 'number') updateSidebarCount(data.unread_count);
                toast('success', data.message || (mode === 'read' ? i18n.marked_read : i18n.marked_unread));
                row.querySelector('.n-menu')?.classList.add('hidden');
            } catch (err) {
                toast('error', err.message);
            }
            return;
        }

        const del = e.target.closest('.n-delete');
        if (del) {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm(i18n.delete_confirm || 'Delete?')) return;
            try {
                const data = await api(row.dataset.destroyUrl, 'DELETE');
                row.remove();
                if (typeof data.unread_count === 'number') updateSidebarCount(data.unread_count);
                toast('success', data.message || i18n.deleted);
                if (list && !list.querySelector('.notification-row')) {
                    list.innerHTML = `<div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-sm text-[#64748b] dark:text-[#A1A09A]">${i18n.empty || ''}</div>`;
                }
            } catch (err) {
                toast('error', err.message);
            }
            return;
        }

        const postBtn = e.target.closest('.n-primary-post');
        if (postBtn) {
            e.preventDefault();
            e.stopPropagation();
            postBtn.disabled = true;
            try {
                const data = await api(postBtn.dataset.url, 'POST');
                setRowReadState(row, true);
                if (typeof data.unread_count === 'number') updateSidebarCount(data.unread_count);
                toast('success', data.message || i18n.referral_ok);
                // Replace primary with view-only if actions returned
                const wrap = row.querySelector('[data-primary-wrap]');
                const actions = data.notification?.actions;
                if (wrap && actions?.primary?.mode === 'link') {
                    wrap.innerHTML = `<a href="${actions.primary.url}" class="n-btn n-btn-secondary w-full sm:w-auto n-primary-link" data-mark-read="1">${actions.primary.label}</a>`;
                } else if (wrap) {
                    postBtn.remove();
                }
            } catch (err) {
                toast('error', err.message);
                postBtn.disabled = false;
            }
            return;
        }

        const rateBtn = e.target.closest('.n-primary-rate');
        if (rateBtn) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof openRatingModal === 'function') {
                openRatingModal(rateBtn.dataset.orderId, rateBtn.dataset.title || '');
                markRead(row, true).catch(() => {});
            }
            return;
        }

        const primaryLink = e.target.closest('.n-primary-link, .n-secondary-link, .n-menu-secondary');
        if (primaryLink) {
            e.stopPropagation();
            markRead(row, true).catch(() => {});
            return;
        }

        if (e.target.closest('.n-actions-stop')) {
            e.stopPropagation();
            return;
        }

        const href = row.dataset.rowHref;
        if (href) {
            markRead(row, true).catch(() => {});
            window.location.href = href;
        }
    });

    readAllBtn?.addEventListener('click', async () => {
        const spinner = readAllBtn.querySelector('[data-spinner]');
        const label = readAllBtn.querySelector('[data-label]');
        readAllBtn.disabled = true;
        spinner?.classList.remove('hidden');
        try {
            const data = await api(readAllBtn.dataset.url, 'POST');
            document.querySelectorAll('.notification-row').forEach((row) => setRowReadState(row, true));
            updateSidebarCount(0);
            toast('success', data.message || i18n.marked_all_read);
        } catch (err) {
            toast('error', err.message);
        } finally {
            readAllBtn.disabled = false;
            spinner?.classList.add('hidden');
        }
    });
})();
</script>
