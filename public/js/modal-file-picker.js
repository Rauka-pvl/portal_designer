/**
 * Unified modal file picker (projects-style UI + existing/new file management).
 */
(function (window) {
    'use strict';

    const instances = {};

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char]));
    }

    function normalizeFileItems(entity) {
        if (!entity) {
            return [];
        }

        if (Array.isArray(entity.file_items) && entity.file_items.length) {
            return entity.file_items
                .filter((item) => item && (item.path || item.name))
                .map((item) => ({
                    path: String(item.path || ''),
                    name: String(item.name || (item.path || '').split('/').pop() || ''),
                    url: String(item.url || ''),
                }))
                .filter((item) => item.path);
        }

        let paths = [];
        if (Array.isArray(entity.file_paths) && entity.file_paths.length) {
            paths = entity.file_paths;
        } else if (Array.isArray(entity.files) && entity.files.length) {
            paths = entity.files;
        } else if (entity.file_path) {
            paths = [entity.file_path];
        }

        const urls = Array.isArray(entity.file_urls) ? entity.file_urls : [];

        return paths
            .filter((path) => typeof path === 'string' && path.trim() !== '')
            .map((path, index) => ({
                path: String(path),
                name: String(path).split('/').pop(),
                url: urls[index]
                    ? String(urls[index])
                    : `/storage/${String(path).replace(/^\//, '')}`,
            }));
    }

    function create(config) {
        const id = String(config.id || '').trim();
        if (!id) {
            throw new Error('ModalFilePicker: id is required');
        }

        const inputName = config.inputName || 'files[]';
        const labels = config.labels || {};
        const notSelectedText = labels.notSelected || 'No files selected';
        const filesSelectedSuffix = labels.filesSelected || 'file(s) selected';
        const viewLabel = labels.view || 'View';
        const deleteLabel = labels.delete || 'Delete';

        const input = document.getElementById(`${id}-files-input`);
        const labelEl = document.getElementById(`${id}-files-label`);
        const existingEl = document.getElementById(`${id}-existing-files`);
        const previewEl = document.getElementById(`${id}-new-files-preview`);

        if (input) {
            input.setAttribute('name', inputName);
            if (config.multiple === false) {
                input.removeAttribute('multiple');
            } else {
                input.setAttribute('multiple', 'multiple');
            }
        }

        let pendingFiles = [];

        const syncInput = () => {
            if (!input) {
                return;
            }
            const dt = new DataTransfer();
            pendingFiles.forEach((file) => dt.items.add(file));
            input.files = dt.files;
        };

        const renderExisting = (entity) => {
            if (!existingEl) {
                return;
            }

            existingEl.innerHTML = '';

            normalizeFileItems(entity).forEach((file, index) => {
                const path = String(file.path || '');
                const name = String(file.name || path.split('/').pop() || '');
                const url = String(file.url || '');
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white/60 dark:bg-[#0a0a0a] px-3 py-2';
                row.dataset.fileIndex = String(index);
                row.dataset.filePath = path;
                row.innerHTML = `
                    <input type="hidden" name="existing_files[]" value="${esc(path)}">
                    <div class="min-w-0 flex-1 truncate text-sm text-[#0f172a] dark:text-[#EDEDEC]">${esc(name)}</div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        ${url ? `<a href="${esc(url)}" target="_blank" rel="noopener" class="text-xs text-[#f59e0b] hover:underline">${esc(viewLabel)}</a>` : ''}
                        <button type="button" class="modal-file-picker-remove text-red-500 hover:text-red-600" title="${esc(deleteLabel)}">×</button>
                    </div>
                `;
                row.querySelector('.modal-file-picker-remove')?.addEventListener('click', () => row.remove());
                existingEl.appendChild(row);
            });
        };

        const renderNew = () => {
            if (!previewEl) {
                return;
            }

            previewEl.innerHTML = '';

            pendingFiles.forEach((file, index) => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-2';
                row.innerHTML = `
                    <span class="truncate">${esc(file.name)}</span>
                    <button type="button" class="text-red-500 hover:text-red-600 shrink-0">${esc(deleteLabel)}</button>
                `;
                row.querySelector('button')?.addEventListener('click', () => {
                    pendingFiles.splice(index, 1);
                    syncInput();
                    renderNew();
                });
                previewEl.appendChild(row);
            });

            if (labelEl) {
                labelEl.textContent = pendingFiles.length
                    ? `${pendingFiles.length} ${filesSelectedSuffix}`
                    : notSelectedText;
            }
        };

        const api = {
            reset(entity = null) {
                pendingFiles = [];
                if (input) {
                    input.value = '';
                }
                renderExisting(entity);
                renderNew();
            },
            clear() {
                api.reset(null);
            },
        };

        if (input && !input.dataset.pickerBound) {
            input.dataset.pickerBound = '1';
            input.addEventListener('change', function onPick() {
                const picked = Array.from(this.files || []);
                if (!picked.length) {
                    return;
                }
                pendingFiles = pendingFiles.concat(picked);
                syncInput();
                renderNew();
            });
        }

        instances[id] = api;
        return api;
    }

    function get(id) {
        return instances[id] || null;
    }

    function initFromDom(root = document) {
        root.querySelectorAll('[data-modal-file-picker]').forEach((el) => {
            const id = el.dataset.pickerId;
            if (!id || instances[id]) {
                return;
            }
            create({
                id,
                inputName: el.dataset.inputName || 'files[]',
                multiple: el.dataset.multiple !== 'false',
                labels: {
                    notSelected: el.dataset.labelNotSelected || undefined,
                    filesSelected: el.dataset.labelFilesSelected || undefined,
                    view: el.dataset.labelView || undefined,
                    delete: el.dataset.labelDelete || undefined,
                },
            });
        });
    }

    window.ModalFilePicker = {
        create,
        get,
        esc,
        normalizeFileItems,
        initFromDom,
    };

    document.addEventListener('DOMContentLoaded', () => initFromDom());
})(window);
