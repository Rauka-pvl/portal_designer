<script>
(function () {
    'use strict';

    const bridge = window.CrmProjectBridge || {};
    const escapeHtml = bridge.escapeHtml || ((s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])));

    const i18n = {
        emptyTitle: @json(__('projects.wazzup_empty_title')),
        emptyBody: @json(__('projects.wazzup_empty_body')),
        noClient: @json(__('projects.wazzup_no_client')),
        noPhone: @json(__('projects.wazzup_no_phone')),
    };

    const els = {
        root: document.getElementById('wazzup-root'),
        listAvatar: document.getElementById('wazzup-list-avatar'),
        listName: document.getElementById('wazzup-list-name'),
        listSub: document.getElementById('wazzup-list-sub'),
        headerAvatar: document.getElementById('wazzup-header-avatar'),
        headerName: document.getElementById('wazzup-header-name'),
        headerPhone: document.getElementById('wazzup-header-phone'),
        messages: document.getElementById('wazzup-messages'),
        textarea: document.getElementById('wazzup-textarea'),
        sendBtn: document.getElementById('wazzup-send'),
        micBtn: document.getElementById('wazzup-mic-btn'),
    };

    if (!els.root) return;

    // Demo state lives only in memory — no persistence, no backend calls.
    let messages = [];
    let currentClient = null;

    function firstLetter(name) {
        const trimmed = String(name || '').trim();
        return trimmed ? trimmed.charAt(0).toUpperCase() : '•';
    }

    function avatarHtml(el, name) {
        el.innerHTML = escapeHtml(firstLetter(name));
    }

    function renderEmpty() {
        els.messages.innerHTML = `
            <div class="wazzup-empty">
                <span class="wazzup-empty-icon">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4-.84L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </span>
                <div class="wazzup-empty-title">${escapeHtml(i18n.emptyTitle)}</div>
                <div class="wazzup-empty-body">${escapeHtml(i18n.emptyBody)}</div>
            </div>`;
    }

    function bubbleHtml(msg) {
        return `
            <div class="wazzup-msg-row is-out">
                <div class="wazzup-bubble wazzup-bubble--out">
                    ${escapeHtml(msg.body)}
                    ${msg.time ? `<span class="wazzup-msg-time">${escapeHtml(msg.time)}</span>` : ''}
                </div>
            </div>`;
    }

    function renderMessages() {
        if (!messages.length) {
            renderEmpty();
            return;
        }
        els.messages.innerHTML = messages.map(bubbleHtml).join('');
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function currentTime() {
        try {
            return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (_) {
            return '';
        }
    }

    function syncSendState() {
        const hasText = els.textarea.value.trim().length > 0;
        els.sendBtn.disabled = !hasText;
        if (els.micBtn) els.micBtn.classList.toggle('hidden', hasText);
        els.sendBtn.classList.toggle('hidden', !hasText);
    }

    function send() {
        const body = els.textarea.value.trim();
        if (!body) return;
        messages.push({ body, time: currentTime() });
        els.textarea.value = '';
        els.textarea.style.height = 'auto';
        syncSendState();
        renderMessages();
        els.textarea.focus();
    }

    function autoGrow() {
        els.textarea.style.height = 'auto';
        els.textarea.style.height = Math.min(els.textarea.scrollHeight, 110) + 'px';
    }

    els.textarea.addEventListener('input', () => { autoGrow(); syncSendState(); });
    els.textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });
    els.sendBtn.addEventListener('click', send);

    // Decorative icons — no real function yet.
    document.querySelectorAll('[data-wazzup-deco]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const anchor = btn.closest('.wazzup-tip-anchor');
            if (!anchor) return;
            document.querySelectorAll('.wazzup-tip-anchor.is-tip-visible').forEach(a => {
                if (a !== anchor) a.classList.remove('is-tip-visible');
            });
            anchor.classList.toggle('is-tip-visible');
            setTimeout(() => anchor.classList.remove('is-tip-visible'), 1800);
        });
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.wazzup-tip-anchor')) {
            document.querySelectorAll('.wazzup-tip-anchor.is-tip-visible').forEach(a => a.classList.remove('is-tip-visible'));
        }
    });

    function resolveClient(project) {
        if (!project) return null;
        const list = (bridge.getClients ? bridge.getClients() : []) || [];
        const found = list.find(c => String(c.id) === String(project.client_id));
        const name = project.client_name || found?.name || '';
        if (!name) return null;
        return {
            name,
            phone: project.client_phone || found?.phone || '',
        };
    }

    function onProjectOpened(project) {
        messages = [];
        currentClient = resolveClient(project);
        const name = currentClient?.name || i18n.noClient;
        const phone = currentClient?.phone || i18n.noPhone;

        avatarHtml(els.listAvatar, currentClient?.name || '');
        avatarHtml(els.headerAvatar, currentClient?.name || '');
        els.listName.textContent = name;
        els.listSub.textContent = phone;
        els.headerName.textContent = name;
        els.headerPhone.textContent = phone;

        renderMessages();
        syncSendState();
        autoGrow();
    }

    syncSendState();
    renderEmpty();
    autoGrow();

    window.CrmWazzup = { onProjectOpened };
})();
</script>
