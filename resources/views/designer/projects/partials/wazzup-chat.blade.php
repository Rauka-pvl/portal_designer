<style>
/* ===== WAZZUP demo chat ===== */
/* Root cause: on non-general tabs .crm-modal-work is flex and .crm-modal-main
   was content-sized, leaving a blank gap on the right. Force full width here
   (also in app.css) so it works even without Vite rebuild. */
.crm-modal-work:not(.is-split) > .crm-modal-main {
    flex: 1 1 auto !important;
    width: 100% !important;
    min-width: 0 !important;
}
.ov-panel--wazzup:not(.hidden) {
    display: flex;
    flex-direction: column;
    height: calc(100% + 1.7rem);
    min-height: 0;
    /* Break out of .crm-modal-main padding (0.85rem 1rem) — edge to edge. */
    margin: -0.85rem -1rem;
    width: calc(100% + 2rem);
    max-width: none;
    box-sizing: border-box;
}
.ov-panel--wazzup .wazzup-root {
    border-radius: 0;
    border-left: none;
    border-right: none;
    width: 100%;
}
.wazzup-root {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    width: 100%;
    height: 100%;
    background: var(--crm-surface);
    border: 1px solid color-mix(in srgb, var(--crm-border) 32%, transparent);
    border-radius: 12px;
    overflow: hidden;
}
.wazzup-sidebar {
    display: flex;
    flex-direction: column;
    border-right: 1px solid color-mix(in srgb, var(--crm-border) 30%, transparent);
    background: var(--crm-surface-2);
    min-height: 0;
}
.wazzup-sidebar-head {
    padding: 0.85rem 0.95rem 0.65rem;
    border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
}
.wazzup-sidebar-title {
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--crm-muted);
}
.wazzup-dialogs {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 0.5rem;
}
.wazzup-dialog {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    padding: 0.65rem 0.7rem;
    border-radius: 10px;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.wazzup-dialog:hover {
    background: color-mix(in srgb, var(--crm-border) 12%, transparent);
}
.wazzup-dialog.is-active {
    background: color-mix(in srgb, var(--crm-accent) 10%, var(--crm-surface));
    border-color: color-mix(in srgb, var(--crm-accent) 45%, transparent);
}
.wazzup-dialog-meta { min-width: 0; flex: 1 1 auto; }
.wazzup-dialog-name {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--crm-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wazzup-dialog-sub {
    display: block;
    font-size: 0.75rem;
    color: var(--crm-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wazzup-avatar {
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: #ffffff;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    overflow: hidden;
    user-select: none;
}
.wazzup-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.wazzup-avatar--lg { width: 44px; height: 44px; font-size: 1.05rem; }
.wazzup-avatar--sm { width: 32px; height: 32px; font-size: 0.8rem; }

.wazzup-main {
    display: flex;
    flex-direction: column;
    min-width: 0;
    width: 100%;
    min-height: 0;
}
.wazzup-chat-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
    border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 28%, transparent);
    background: var(--crm-surface);
    flex: 0 0 auto;
}
.wazzup-chat-header-meta { min-width: 0; flex: 1 1 auto; }
.wazzup-chat-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--crm-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wazzup-chat-phone {
    font-size: 0.78rem;
    color: var(--crm-muted);
}
.wazzup-chat-actions { display: flex; align-items: center; gap: 0.25rem; }

.wazzup-messages {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    background:
        radial-gradient(circle at 12% 18%, color-mix(in srgb, var(--crm-accent) 5%, transparent) 0, transparent 34%),
        var(--crm-surface-2);
}
.wazzup-empty {
    margin: auto;
    max-width: 320px;
    text-align: center;
    color: var(--crm-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
}
.wazzup-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--crm-border) 14%, transparent);
    color: var(--crm-muted);
}
.wazzup-empty-title { font-size: 0.9rem; font-weight: 600; color: var(--crm-text); }
.wazzup-empty-body { font-size: 0.8rem; line-height: 1.45; }

.wazzup-msg-row { display: flex; }
.wazzup-msg-row.is-out { justify-content: flex-end; }
.wazzup-bubble {
    max-width: 100%;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    line-height: 1.45;
    word-break: break-word;
    white-space: pre-wrap;
    box-shadow: 0 1px 1px rgba(0,0,0,0.05);
}
.wazzup-bubble--out {
    background: #dcf8c6;
    color: #111b21;
    border-bottom-right-radius: 4px;
}
.dark .wazzup-bubble--out {
    background: #005c4b;
    color: #e9edef;
}
.wazzup-msg-time {
    display: block;
    margin-top: 0.2rem;
    font-size: 0.68rem;
    text-align: right;
    color: rgba(17, 27, 33, 0.45);
}
.dark .wazzup-msg-time { color: rgba(233, 237, 239, 0.55); }

.wazzup-input-bar {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.6rem 0.85rem;
    border-top: 1px solid color-mix(in srgb, var(--crm-border) 28%, transparent);
    background: var(--crm-surface);
}
.wazzup-input-wrap {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: center;
    background: var(--crm-input-bg);
    border: 1px solid color-mix(in srgb, var(--crm-border) 45%, transparent);
    border-radius: 999px;
    padding: 0 0.35rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.wazzup-input-wrap:focus-within {
    border-color: var(--crm-accent);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--crm-accent) 22%, transparent);
}
.wazzup-icon-btn {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: var(--crm-muted);
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}
.wazzup-icon-btn:hover { background: color-mix(in srgb, var(--crm-border) 16%, transparent); color: var(--crm-text); }
.wazzup-icon-btn[disabled] { cursor: default; }

.wazzup-textarea {
    flex: 1 1 auto;
    min-width: 0;
    border: none;
    background: transparent;
    resize: none;
    outline: none;
    padding: 0.6rem 0.25rem;
    font-size: 0.9rem;
    line-height: 1.35;
    max-height: 110px;
    color: var(--crm-text);
    font-family: inherit;
}
.wazzup-textarea::placeholder { color: var(--crm-muted); }

.wazzup-send-btn {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #25d366;
    color: #ffffff;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.05s ease;
}
.wazzup-send-btn:hover { background: #1fb857; }
.wazzup-send-btn:active { transform: scale(0.96); }
.wazzup-send-btn[disabled] {
    background: color-mix(in srgb, var(--crm-border) 35%, transparent);
    color: var(--crm-muted);
    cursor: default;
}

.wazzup-tip {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: var(--crm-text);
    color: var(--crm-bg);
    font-size: 0.72rem;
    padding: 0.3rem 0.55rem;
    border-radius: 6px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s ease;
    z-index: 5;
}
.wazzup-tip-anchor { position: relative; }
.wazzup-tip-anchor.is-tip-visible .wazzup-tip { opacity: 1; }

@media (max-width: 860px) {
    .wazzup-root { grid-template-columns: 1fr; grid-template-rows: auto minmax(0, 1fr); }
    .wazzup-sidebar {
        border-right: none;
        border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 30%, transparent);
    }
    .wazzup-sidebar-head { display: none; }
    .wazzup-dialogs { padding: 0.45rem 0.6rem; overflow: visible; }
    .wazzup-dialog { padding: 0.5rem 0.6rem; }
    .wazzup-chat-header { display: none; }
    .wazzup-bubble { max-width: 100%; }
}
</style>

<div class="wazzup-root" id="wazzup-root">
    <aside class="wazzup-sidebar">
        <div class="wazzup-sidebar-head">
            <div class="wazzup-sidebar-title">Wazzup</div>
        </div>
        <div class="wazzup-dialogs">
            <button type="button" class="wazzup-dialog is-active" id="wazzup-dialog" aria-current="true">
                <span class="wazzup-avatar" id="wazzup-list-avatar"></span>
                <span class="wazzup-dialog-meta">
                    <span class="wazzup-dialog-name" id="wazzup-list-name"></span>
                    <span class="wazzup-dialog-sub" id="wazzup-list-sub"></span>
                </span>
            </button>
        </div>
    </aside>

    <section class="wazzup-main">
        <header class="wazzup-chat-header">
            <span class="wazzup-avatar wazzup-avatar--lg" id="wazzup-header-avatar"></span>
            <div class="wazzup-chat-header-meta">
                <div class="wazzup-chat-name" id="wazzup-header-name"></div>
                <div class="wazzup-chat-phone" id="wazzup-header-phone"></div>
            </div>
            <div class="wazzup-chat-actions">
                <button type="button" class="wazzup-icon-btn" data-wazzup-deco aria-label="{{ __('projects.wazzup_search') }}" title="{{ __('projects.wazzup_search') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
                <button type="button" class="wazzup-icon-btn" data-wazzup-deco aria-label="{{ __('detail.more_actions') }}" title="{{ __('detail.more_actions') }}">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                </button>
            </div>
        </header>

        <div class="wazzup-messages" id="wazzup-messages"></div>

        <div class="wazzup-input-bar">
            <div class="wazzup-input-wrap">
                <span class="wazzup-tip-anchor" id="wazzup-emoji-anchor">
                    <button type="button" class="wazzup-icon-btn" data-wazzup-deco aria-label="{{ __('projects.wazzup_emoji') }}" title="{{ __('projects.wazzup_emoji') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <span class="wazzup-tip">{{ __('projects.wazzup_deco_hint') }}</span>
                </span>
                <textarea id="wazzup-textarea" class="wazzup-textarea" rows="1"
                    placeholder="{{ __('projects.wazzup_input_placeholder') }}"
                    aria-label="{{ __('projects.wazzup_input_placeholder') }}"></textarea>
                <span class="wazzup-tip-anchor">
                    <button type="button" class="wazzup-icon-btn" data-wazzup-deco aria-label="{{ __('projects.wazzup_attach') }}" title="{{ __('projects.wazzup_attach') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </button>
                    <span class="wazzup-tip">{{ __('projects.wazzup_deco_hint') }}</span>
                </span>
            </div>
            <span class="wazzup-tip-anchor" id="wazzup-mic-anchor">
                <button type="button" class="wazzup-icon-btn" id="wazzup-mic-btn" data-wazzup-deco aria-label="{{ __('projects.wazzup_mic') }}" title="{{ __('projects.wazzup_mic') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </button>
                <span class="wazzup-tip">{{ __('projects.wazzup_deco_hint') }}</span>
            </span>
            <button type="button" class="wazzup-send-btn" id="wazzup-send" disabled aria-label="{{ __('projects.wazzup_send') }}" title="{{ __('projects.wazzup_send') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
    </section>
</div>
