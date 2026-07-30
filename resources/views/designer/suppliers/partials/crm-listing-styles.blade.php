
    .crm-suppliers-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 1.5rem;
        padding: 0 0.45rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: color-mix(in srgb, var(--crm-border) 28%, transparent);
        color: var(--crm-muted);
        vertical-align: middle;
    }
    #crm-suppliers-workspace {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    #crm-suppliers-workspace .crm-toolbar {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    #crm-suppliers-workspace .crm-toolbar-right {
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    #crm-suppliers-workspace .crm-toolbar-search-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        width: min(320px, 100%);
    }
    #crm-suppliers-workspace .crm-toolbar-search {
        width: 100%;
        padding-left: 2rem;
        padding-right: 2rem;
    }
    #crm-suppliers-workspace .crm-search-clear {
        position: absolute;
        right: 0.35rem;
        width: 28px;
        height: 28px;
        border: none;
        background: transparent;
        color: var(--crm-muted);
        border-radius: 6px;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    #crm-suppliers-workspace .crm-search-clear.is-visible { display: inline-flex; }
    #crm-suppliers-workspace .crm-search-clear:hover { color: var(--crm-text); background: color-mix(in srgb, var(--crm-border) 22%, transparent); }
    #crm-suppliers-workspace .crm-filters-wrap { position: relative; }
    #crm-suppliers-workspace .crm-filters-btn { position: relative; }
    #crm-suppliers-workspace .crm-filters-badge {
        position: absolute;
        top: -0.35rem;
        right: -0.35rem;
        min-width: 1.1rem;
        height: 1.1rem;
        padding: 0 0.3rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1.1rem;
        text-align: center;
        background: var(--crm-accent);
        color: #fff;
    }
    #crm-suppliers-workspace .crm-filters-panel {
        position: absolute;
        top: calc(100% + 0.4rem);
        right: 0;
        z-index: 40;
        width: min(320px, calc(100vw - 2rem));
        background: var(--crm-surface);
        border: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
        border-radius: 10px;
        box-shadow: var(--crm-shadow);
        padding: 0.85rem;
        display: none;
    }
    #crm-suppliers-workspace .crm-filters-panel.is-open { display: block; }
    #crm-suppliers-workspace .crm-filters-panel .crm-label { margin-bottom: 0.25rem; }
    #crm-suppliers-workspace .crm-filters-panel .crm-input { width: 100%; margin-bottom: 0.65rem; }
    #crm-suppliers-workspace .crm-filters-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }
    #crm-suppliers-workspace .crm-filter-chips {
        display: none;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }
    #crm-suppliers-workspace .crm-filter-chips.has-chips { display: flex; }
    #crm-suppliers-workspace .crm-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        background: color-mix(in srgb, var(--crm-border) 22%, transparent);
        color: var(--crm-text);
        border: 1px solid color-mix(in srgb, var(--crm-border) 35%, transparent);
    }
    #crm-suppliers-workspace .crm-chip button {
        display: inline-flex;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: var(--crm-muted);
        border-radius: 999px;
        cursor: pointer;
        font-size: 0.85rem;
        line-height: 1;
    }
    #crm-suppliers-workspace .crm-chip button:hover { color: var(--crm-text); background: color-mix(in srgb, var(--crm-border) 30%, transparent); }
    #crm-suppliers-workspace .crm-chip-clear {
        border: none;
        background: transparent;
        color: var(--crm-accent);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        padding: 0.2rem 0.4rem;
    }
    #crm-suppliers-workspace .crm-suppliers-panel {
        background: var(--crm-surface);
        border-radius: 8px;
        box-shadow: var(--crm-shadow);
        overflow: hidden;
    }
    #crm-suppliers-workspace .crm-suppliers-panel.is-hidden { display: none !important; }
    #crm-suppliers-workspace .crm-table tbody tr { cursor: pointer; }
    #crm-suppliers-workspace .crm-table a { color: var(--crm-accent); }
    #crm-suppliers-workspace .crm-table a:hover { text-decoration: underline; }
    #crm-suppliers-workspace .crm-cell-primary {
        font-weight: 600;
        color: var(--crm-text);
        line-height: 1.3;
    }
    #crm-suppliers-workspace .crm-cell-secondary {
        font-size: 0.75rem;
        color: var(--crm-muted);
        line-height: 1.35;
        margin-top: 0.1rem;
    }
    #crm-suppliers-workspace .crm-rating {
        display: inline-flex;
        flex-direction: column;
        gap: 0.05rem;
        font-size: 0.8125rem;
        color: var(--crm-text);
        line-height: 1.25;
    }
    #crm-suppliers-workspace .crm-rating-row {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    #crm-suppliers-workspace .crm-rating-star { color: var(--crm-accent); }
    #crm-suppliers-workspace .crm-rating-count {
        font-size: 0.7rem;
        color: var(--crm-muted);
    }
    #crm-suppliers-workspace .crm-rating-empty {
        font-size: 0.75rem;
        color: var(--crm-muted);
    }
    #crm-suppliers-workspace .crm-mod-badge {
        display: inline-flex;
        align-items: center;
        max-width: 9.5rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #crm-suppliers-workspace .crm-mod-badge.is-approved {
        background: color-mix(in srgb, #10b981 14%, transparent);
        color: #047857;
    }
    #crm-suppliers-workspace .crm-mod-badge.is-pending {
        background: color-mix(in srgb, #f59e0b 16%, transparent);
        color: #b45309;
    }
    #crm-suppliers-workspace .crm-mod-badge.is-rejected {
        background: color-mix(in srgb, #f43f5e 14%, transparent);
        color: #be123c;
    }
    #crm-suppliers-workspace .crm-mod-badge.is-unknown {
        background: color-mix(in srgb, var(--crm-border) 28%, transparent);
        color: var(--crm-muted);
    }
    html.dark #crm-suppliers-workspace .crm-mod-badge.is-approved,
    .dark #crm-suppliers-workspace .crm-mod-badge.is-approved { color: #6ee7b7; }
    html.dark #crm-suppliers-workspace .crm-mod-badge.is-pending,
    .dark #crm-suppliers-workspace .crm-mod-badge.is-pending { color: #fcd34d; }
    html.dark #crm-suppliers-workspace .crm-mod-badge.is-rejected,
    .dark #crm-suppliers-workspace .crm-mod-badge.is-rejected { color: #fda4af; }

    #crm-suppliers-workspace .crm-actions-wrap { position: relative; display: inline-flex; }
    #crm-suppliers-workspace .crm-actions-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: var(--crm-muted);
        border-radius: 8px;
        cursor: pointer;
    }
    #crm-suppliers-workspace .crm-actions-btn:hover,
    #crm-suppliers-workspace .crm-actions-btn:focus-visible {
        color: var(--crm-text);
        background: color-mix(in srgb, var(--crm-border) 22%, transparent);
        outline: none;
    }

    #crm-suppliers-workspace .crm-suppliers-cards {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 0.75rem;
    }
    #crm-suppliers-workspace .crm-supplier-card {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        min-height: 100%;
        padding: 0.9rem 1rem;
        border: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
        border-radius: 8px;
        background: var(--crm-surface);
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }
    #crm-suppliers-workspace .crm-supplier-card:hover {
        border-color: color-mix(in srgb, var(--crm-accent) 45%, var(--crm-border));
        box-shadow: var(--crm-shadow);
    }
    #crm-suppliers-workspace .crm-supplier-card-title {
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--crm-text);
        line-height: 1.3;
    }
    #crm-suppliers-workspace .crm-supplier-card-meta {
        font-size: 0.75rem;
        color: var(--crm-muted);
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        flex: 1 1 auto;
    }
    #crm-suppliers-workspace .crm-supplier-card-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding-top: 0.45rem;
        border-top: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
        margin-top: auto;
    }

    #crm-suppliers-workspace .crm-suppliers-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.65rem 0.75rem;
        border-top: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
    }
    #crm-suppliers-workspace .crm-suppliers-pagination-pages {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }
    #crm-suppliers-workspace .crm-suppliers-empty {
        padding: 2.5rem 1.25rem;
        text-align: center;
    }
    #crm-suppliers-workspace .crm-suppliers-empty h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--crm-text);
        margin: 0 0 0.35rem;
    }
    #crm-suppliers-workspace .crm-suppliers-empty p {
        font-size: 0.875rem;
        color: var(--crm-muted);
        margin: 0 0 1rem;
        max-width: 28rem;
        margin-inline: auto;
    }
    #crm-suppliers-workspace .crm-suppliers-empty-actions {
        display: inline-flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .favorite-btn { color: var(--crm-muted); transition: color .2s; }
    .favorite-btn.active, .favorite-btn:hover { color: var(--crm-accent); }

    @media (max-width: 1280px) {
        #crm-suppliers-workspace .crm-suppliers-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 1024px) {
        #crm-suppliers-workspace .crm-suppliers-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        #crm-suppliers-workspace .crm-toolbar-search-wrap { width: min(100%, 360px); flex: 1 1 220px; }
    }
    @media (max-width: 768px) {
        #crm-suppliers-workspace .crm-suppliers-cards { grid-template-columns: 1fr; }
        #crm-suppliers-workspace .crm-toolbar-left,
        #crm-suppliers-workspace .crm-toolbar-right { width: 100%; }
        #crm-suppliers-workspace .crm-toolbar-search-wrap { width: 100%; }
        #crm-suppliers-workspace .crm-filters-panel {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            width: 100%;
            border-radius: 14px 14px 0 0;
            max-height: min(80vh, 560px);
            overflow: auto;
        }
        #crm-suppliers-workspace .crm-filters-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            z-index: 35;
            display: none;
        }
        #crm-suppliers-workspace .crm-filters-backdrop.is-open { display: block; }
    }
