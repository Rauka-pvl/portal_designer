{{-- Единые toast-уведомления (success / error / info / warning) --}}
<div id="project-alert-container" class="pointer-events-none fixed top-4 right-4 z-[9999] flex flex-col gap-3 max-w-[min(100vw-2rem,24rem)]"></div>

<script>
    /**
     * @param {'success'|'error'|'info'|'warning'} type
     * @param {string} message
     * @param {string} title
     * @param {number} duration
     */
    function projectAlert(type, message, title = '', duration = 2800) {
        const container = document.getElementById('project-alert-container');
        if (!container) return;

        const isDark = document.documentElement.classList.contains('dark');
        const palette = {
            success: isDark
                ? { bg: 'rgba(16,185,129,0.14)', border: 'rgba(16,185,129,0.45)', color: '#34d399' }
                : { bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.35)', color: '#059669' },
            error: isDark
                ? { bg: 'rgba(239,68,68,0.14)', border: 'rgba(239,68,68,0.45)', color: '#f87171' }
                : { bg: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.35)', color: '#dc2626' },
            warning: isDark
                ? { bg: 'rgba(245,158,11,0.16)', border: 'rgba(245,158,11,0.5)', color: '#fbbf24' }
                : { bg: 'rgba(245,158,11,0.14)', border: 'rgba(245,158,11,0.4)', color: '#b45309' },
            info: isDark
                ? { bg: 'rgba(59,130,246,0.14)', border: 'rgba(59,130,246,0.45)', color: '#60a5fa' }
                : { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.35)', color: '#2563eb' },
        }[type] || (isDark
            ? { bg: 'rgba(59,130,246,0.14)', border: 'rgba(59,130,246,0.45)', color: '#60a5fa' }
            : { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.35)', color: '#2563eb' });

        const iconSvg = (() => {
            if (type === 'success') {
                return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6L9 17l-5-5"></path>
                </svg>`;
            }
            if (type === 'error') {
                return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18"></path>
                    <path d="M6 6l12 12"></path>
                </svg>`;
            }
            if (type === 'warning') {
                return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                    <path d="M12 9v4M12 17h.01"></path>
                </svg>`;
            }
            return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                <path d="M12 16v-4"></path>
                <path d="M12 8h.01"></path>
            </svg>`;
        })();

        const el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.className = 'pointer-events-auto flex items-start gap-3 rounded-xl border shadow-lg backdrop-blur-sm px-4 py-3 transition-all duration-200';
        el.style.background = palette.bg;
        el.style.borderColor = palette.border;
        el.style.color = palette.color;
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';

        const iconWrap = document.createElement('div');
        iconWrap.className = 'mt-0.5 flex items-center shrink-0';
        iconWrap.innerHTML = iconSvg;

        const textWrap = document.createElement('div');
        textWrap.className = 'flex-1 min-w-0';

        const titleEl = document.createElement('div');
        titleEl.className = 'text-sm font-semibold leading-4 mb-1';
        titleEl.textContent = title || '';
        titleEl.style.display = title ? 'block' : 'none';

        const msgEl = document.createElement('div');
        msgEl.className = 'text-sm leading-snug break-words';
        msgEl.textContent = message || '';

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.className = 'ml-2 -mr-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/10 transition-colors shrink-0';
        closeBtn.style.color = palette.color;
        closeBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6L6 18"></path>
            <path d="M6 6l12 12"></path>
        </svg>`;
        closeBtn.addEventListener('click', () => removeToast());

        textWrap.appendChild(titleEl);
        textWrap.appendChild(msgEl);

        el.appendChild(iconWrap);
        el.appendChild(textWrap);
        el.appendChild(closeBtn);

        container.appendChild(el);

        const removeToast = () => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            setTimeout(() => {
                try {
                    el.remove();
                } catch (_) {}
            }, 200);
        };

        requestAnimationFrame(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });

        setTimeout(removeToast, duration);
    }

    window.projectAlert = projectAlert;
</script>
