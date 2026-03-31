{{-- Legacy duplicate block disabled to prevent conflicting markup/scripts.
@extends('layouts.dashboard')

@section('title', __('projects.projects'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.projects') }}</h1>
        <button id="add-project-btn" type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('projects.add_project') }}
        </button>
    </div>

    <div class="mb-4">
        <input id="projects-search" type="text" placeholder="{{ __('projects.search') }}"
            class="w-full md:w-96 px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.object_address') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.client') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.start_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.planned_cost') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="projects-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]"></tbody>
            </table>
        </div>
    </div>

    <div id="project-modal" class="fixed inset-0 bg-black/50 z-[80] hidden flex items-center justify-center modal-overlay p-4" onmousedown="if(event.target===this) closeProjectModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-5xl w-full max-h-[92vh] mx-auto overflow-hidden flex flex-col border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] flex items-center justify-between">
                <h2 id="project-modal-title" class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.new_project') }}</h2>
                <button type="button" onclick="closeProjectModal()" class="p-2 rounded-lg hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="project-form" class="flex-1 overflow-y-auto p-6 space-y-5" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="project_id" name="project_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.project_name') }}</label>
                        <input type="text" name="name" required class="w-full modal-input">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.select_object') }}</label>
                        <select name="object_id" required class="w-full modal-input" id="project-object-select"></select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.status') }}</label>
                        <input type="text" name="status" required class="w-full modal-input">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.start_date') }}</label>
                        <input type="date" name="start_date" class="w-full modal-input">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.planned_end_date') }}</label>
                        <input type="date" name="planned_end_date" class="w-full modal-input">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.actual_end_date') }}</label>
                        <input type="date" name="actual_end_date" class="w-full modal-input">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('projects.planned_cost') }}</label>
                        <input type="number" name="planned_cost" min="0" step="0.01" class="w-full modal-input">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label modal-label-required">{{ __('projects.planned_end_date') }}</label>
                        <div class="relative">
                            <input type="text" name="planned_end_date" id="project-planned-end-date" required class="modal-input pr-10" placeholder="дд.мм.гггг" autocomplete="off">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b] transition-colors" onclick="document.getElementById('project-planned-end-date')._flatpickr?.open()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('projects.actual_end_date') }}</label>
                        <div class="relative">
                            <input type="text" name="actual_end_date" id="project-actual-end-date" class="modal-input pr-10" placeholder="дд.мм.гггг" autocomplete="off">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b] transition-colors" onclick="document.getElementById('project-actual-end-date')._flatpickr?.open()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.project_cost') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.project_cost_subtitle') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <input type="text" name="planned_cost" id="project-planned-cost" class="modal-input" placeholder="{{ __('projects.planned_cost') }}" inputmode="numeric">
                            <p class="modal-helper">{{ __('projects.cost_helper') }}</p>
                        </div>
                        <div>
                            <input type="text" name="actual_cost" id="project-actual-cost" class="modal-input" placeholder="{{ __('projects.actual_cost') }}" inputmode="numeric">
                            <p class="modal-helper">{{ __('projects.cost_helper') }}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.links') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.links_subtitle') }}</p>
                    <div id="project-links-container" class="space-y-3">
                        <div class="input-with-icon">
                            <span class="input-icon text-[#f59e0b]"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg></span>
                            <input type="url" name="project_links[]" class="modal-input" placeholder="{{ __('projects.paste_link') }}">
                        </div>
                    </div>
                    <button type="button" onclick="addProjectLinkField()" class="mt-2 text-sm font-medium text-[#f59e0b] hover:underline flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('projects.add_link') }}
                    </button>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.files') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.files_subtitle') }}</p>
                    <div class="flex gap-0 overflow-hidden rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        <label class="flex-1 flex items-center gap-2 px-4 py-2.5 text-[#64748b] dark:text-[#A1A09A] text-sm cursor-pointer min-h-[2.5rem]">
                            <svg class="w-5 h-5 text-[#f59e0b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg>
                            <span id="project-files-label">{{ __('projects.files_not_selected') }}</span>
                            <input type="file" name="project_files[]" id="project-files-input" multiple class="hidden">
                        </label>
                        <label for="project-files-input" class="px-4 py-2.5 bg-[#f59e0b] hover:bg-[#d97706] text-white font-medium text-sm cursor-pointer transition-colors shrink-0 flex items-center">
                            {{ __('projects.select_files') }}
                        </label>
                    </div>
                </div>
                <div>
                    <label class="modal-label">{{ __('projects.comment') }}</label>
                    <textarea name="comment" rows="3" class="modal-input resize-none" placeholder="{{ __('projects.comment_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer flex-col sm:flex-row gap-3">
                <button type="submit" id="project-submit-btn" class="add-btn w-full sm:w-auto">{{ __('projects.add_project') }}</button>
                <button type="button" onclick="closeProjectModal()" class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('projects.go_back') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const state = {
        projects: @json($projectsData ?? []),
        objects: @json($objectsData ?? []),
        templates: @json($templatesData ?? []),
        stageTypes: @json($stageTypes ?? []),
    };

    const tableBody = document.getElementById('projects-table-body');
    const searchInput = document.getElementById('projects-search');
    const projectModal = document.getElementById('project-modal');
    const projectForm = document.getElementById('project-form');
    const projectStagesEl = document.getElementById('project-stages');
    const projectLinksEl = document.getElementById('project-links');

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const stageLabels = {
        measurement: `{{ __('projects.stage_measurement') }}`,
        planning: `{{ __('projects.stage_planning') }}`,
        drawings: `{{ __('projects.stage_drawings') }}`,
        equipment: `{{ __('projects.stage_equipment') }}`,
        estimate: `{{ __('projects.stage_estimate') }}`,
        visualization: `{{ __('projects.stage_visualization') }}`,
    };
    const stageName = (t) => stageLabels[t] || t;
    const templateById = (id) => state.templates.find(t => Number(t.id) === Number(id));

    function renderObjectsSelect() {
        const select = document.getElementById('project-object-select');
        select.innerHTML = state.objects.map(o =>
            `<option value="${o.id}">${esc([o.city, o.address].filter(Boolean).join(', '))}${o.client_name ? ` - ${esc(o.client_name)}` : ''}</option>`
        ).join('');
    }

    function renderProjects() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const rows = state.projects.filter(p => !q || [p.name, p.object_address, p.client_name, p.status].join(' ').toLowerCase().includes(q));
        if (!rows.length) {
            tableBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.no_projects') }}</td></tr>`;
            return;
        }
        tableBody.innerHTML = rows.map(p => `
            <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]">
                <td class="px-4 py-3">${esc(p.name)}</td>
                <td class="px-4 py-3">${esc([p.object_city, p.object_address].filter(Boolean).join(', ') || '-')}</td>
                <td class="px-4 py-3">${esc(p.client_name || '-')}</td>
                <td class="px-4 py-3">${esc(p.status || '-')}</td>
                <td class="px-4 py-3">${esc(p.start_date || '-')}</td>
                <td class="px-4 py-3">${Number(p.planned_cost || 0).toLocaleString()}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <button type="button" onclick="window.viewProject(${p.id})" class="text-[#f59e0b] hover:underline">{{ __('projects.view') }}</button>
                        <button type="button" onclick="window.editProject(${p.id})" class="text-[#f59e0b] hover:underline">{{ __('projects.edit') }}</button>
                        <button type="button" onclick="window.deleteProject(${p.id})" class="text-red-500 hover:underline">{{ __('projects.delete') }}</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function linkInput(value = '') {
        return `<div class="flex gap-2"><input type="url" name="links[]" value="${esc(value)}" class="w-full modal-input"><button type="button" class="px-3 rounded border border-[#e2e8f0] dark:border-[#3E3E3A]" onclick="this.parentElement.remove()">×</button></div>`;
    }

    function stageBlock(stage = {}) {
        const selectedType = stage.stage_type || 'measurement';
        const templateOptions = state.templates
            .filter(t => t.type === selectedType)
            .map(t => `<option value="${t.id}" ${Number(stage.template_id)===Number(t.id)?'selected':''}>${esc(t.name)}${t.is_shared ? ' (base)' : ''}</option>`)
            .join('');
        const steps = Array.isArray(stage.steps) && stage.steps.length ? stage.steps : [''];

        return `<div class="rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] p-4 stage-row">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="stages[][stage_type]" class="modal-input stage-type">
                    ${state.stageTypes.map(t => `<option value="${t}" ${t===selectedType?'selected':''}>${esc(stageName(t))}</option>`).join('')}
                </select>
                <select name="stages[][template_id]" class="modal-input stage-template">
                    <option value="">{{ __('projects.select_template') }}</option>
                    ${templateOptions}
                </select>
                <div class="flex gap-2">
                    <input type="text" class="modal-input stage-template-name" placeholder="{{ __('projects.template_name_placeholder') }}">
                    <button type="button" class="px-3 rounded border border-[#f59e0b] text-[#f59e0b] save-template-btn">{{ __('projects.save_as_template') }}</button>
                </div>
            </div>
            <div class="mt-3 space-y-2 stage-steps">
                ${steps.map(s => `<div class="flex gap-2"><input type="text" name="stages[][steps][]" class="w-full modal-input" value="${esc(s)}"><button type="button" class="px-3 rounded border border-[#e2e8f0] dark:border-[#3E3E3A]" onclick="this.parentElement.remove()">×</button></div>`).join('')}
            </div>
            <div class="mt-2 flex gap-3">
                <button type="button" class="text-sm text-[#f59e0b] add-step-btn">{{ __('projects.add_item') }}</button>
                <button type="button" class="text-sm text-red-500 delete-template-btn">{{ __('projects.delete_template') }}</button>
                <button type="button" class="text-sm text-red-500 remove-stage-btn">{{ __('projects.delete') }}</button>
            </div>
        </div>`;
    }

    function bindStageEvents(wrapper) {
        const typeSelect = wrapper.querySelector('.stage-type');
        const tplSelect = wrapper.querySelector('.stage-template');
        const stepsWrap = wrapper.querySelector('.stage-steps');

        const refillTemplates = () => {
            const st = typeSelect.value;
            const list = state.templates.filter(t => t.type === st);
            tplSelect.innerHTML = `<option value="">{{ __('projects.select_template') }}</option>` + list
                .map(t => `<option value="${t.id}">${esc(t.name)}${t.is_shared ? ' (base)' : ''}</option>`).join('');
        };

        typeSelect.addEventListener('change', refillTemplates);
        tplSelect.addEventListener('change', () => {
            const tpl = templateById(tplSelect.value);
            if (!tpl) return;
            stepsWrap.innerHTML = tpl.steps.map(s => `<div class="flex gap-2"><input type="text" name="stages[][steps][]" class="w-full modal-input" value="${esc(s)}"><button type="button" class="px-3 rounded border border-[#e2e8f0] dark:border-[#3E3E3A]" onclick="this.parentElement.remove()">×</button></div>`).join('');
        });
        wrapper.querySelector('.add-step-btn').addEventListener('click', () => {
            stepsWrap.insertAdjacentHTML('beforeend', `<div class="flex gap-2"><input type="text" name="stages[][steps][]" class="w-full modal-input"><button type="button" class="px-3 rounded border border-[#e2e8f0] dark:border-[#3E3E3A]" onclick="this.parentElement.remove()">×</button></div>`);
        });
        wrapper.querySelector('.remove-stage-btn').addEventListener('click', () => wrapper.remove());
        wrapper.querySelector('.save-template-btn').addEventListener('click', async () => {
            const name = wrapper.querySelector('.stage-template-name').value.trim();
            const type = typeSelect.value;
            const steps = Array.from(stepsWrap.querySelectorAll('input')).map(i => i.value.trim()).filter(Boolean);
            if (!name || !steps.length) return projectAlert('error', '{{ __('projects.add_at_least_one_step') }}');
            const res = await fetch('{{ route('projects.templates.store') }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify({name, type, steps})
            });
            const data = await res.json();
            if (!res.ok || !data.success) return projectAlert('error', data.message || 'Error');
            state.templates.unshift(data.template);
            refillTemplates();
            tplSelect.value = String(data.template.id);
            projectAlert('success', data.message || 'OK');
        });
        wrapper.querySelector('.delete-template-btn').addEventListener('click', async () => {
            const templateId = Number(tplSelect.value || 0);
            if (!templateId) return;
            const tpl = templateById(templateId);
            if (!tpl || !tpl.is_owned) return projectAlert('error', '{{ __('projects.template_delete_only_own') }}');
            if (!confirm(`{{ __('projects.delete_template_confirm') }}`)) return;
            const res = await fetch(`{{ url('/projects/templates') }}/${templateId}`, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}
            });
            const data = await res.json();
            if (!res.ok || !data.success) return projectAlert('error', data.message || 'Error');
            state.templates = state.templates.filter(t => Number(t.id) !== templateId);
            refillTemplates();
            tplSelect.value = '';
            projectAlert('success', data.message || 'OK');
        });
    }

    function openProjectModal(project = null) {
        projectForm.reset();
        document.getElementById('project_id').value = project?.id || '';
        document.getElementById('project-modal-title').textContent = project ? '{{ __('projects.edit_project') }}' : '{{ __('projects.new_project') }}';
        projectLinksEl.innerHTML = '';
        projectStagesEl.innerHTML = '';
        document.getElementById('project-existing-files').innerHTML = '';
        (project?.links?.length ? project.links : ['']).forEach(l => projectLinksEl.insertAdjacentHTML('beforeend', linkInput(l)));
        (project?.stages?.length ? project.stages : [{}]).forEach(s => {
            const temp = document.createElement('div');
            temp.innerHTML = stageBlock(s);
            const row = temp.firstElementChild;
            projectStagesEl.appendChild(row);
            bindStageEvents(row);
        });
        if (project) {
            projectForm.querySelector('[name="name"]').value = project.name || '';
            projectForm.querySelector('[name="object_id"]').value = String(project.object_id || '');
            projectForm.querySelector('[name="status"]').value = project.status || '';
            projectForm.querySelector('[name="start_date"]').value = project.start_date || '';
            projectForm.querySelector('[name="planned_end_date"]').value = project.planned_end_date || '';
            projectForm.querySelector('[name="actual_end_date"]').value = project.actual_end_date || '';
            projectForm.querySelector('[name="planned_cost"]').value = project.planned_cost || '';
            projectForm.querySelector('[name="actual_cost"]').value = project.actual_cost || '';
            projectForm.querySelector('[name="comment"]').value = project.comment || '';
            const existing = document.getElementById('project-existing-files');
            (project.files || []).forEach((f, idx) => {
                const url = project.file_urls?.[idx] || '';
                existing.insertAdjacentHTML('beforeend', `<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="existing_files[]" value="${esc(f)}" checked>${url ? `<a class="text-[#f59e0b] hover:underline" target="_blank" href="${esc(url)}">${esc(f.split('/').pop())}</a>` : esc(f)}</label>`);
            });
        }
        projectModal.classList.remove('hidden');
    }

    window.closeProjectModal = () => projectModal.classList.add('hidden');

    window.viewProject = (id) => {
        const p = state.projects.find(v => Number(v.id) === Number(id));
        if (!p) return;
        const view = document.getElementById('project-view-modal');
        const content = document.getElementById('project-view-content');
        content.innerHTML = `
            <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.name') }}</div><div>${esc(p.name || '-')}</div></div>
            <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.object_address') }}</div><div>${esc([p.object_city,p.object_address].filter(Boolean).join(', ') || '-')}</div></div>
            <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.client') }}</div><div>${esc(p.client_name || '-')}</div></div>
            <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.status') }}</div><div>${esc(p.status || '-')}</div></div>
            <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.comment') }}</div><div>${esc(p.comment || '-')}</div></div>
        `;
        view.classList.remove('hidden');
        setTimeout(() => view.querySelector('div[class*="absolute"]').classList.remove('translate-x-full'), 10);
    };
    window.closeProjectViewModal = () => {
        const view = document.getElementById('project-view-modal');
        view.querySelector('div[class*="absolute"]').classList.add('translate-x-full');
        view.classList.add('hidden');
    };
    window.editProject = (id) => {
        const p = state.projects.find(v => Number(v.id) === Number(id));
        if (p) openProjectModal(p);
    };
    window.deleteProject = async (id) => {
        if (!confirm('{{ __('projects.delete_confirm') }}')) return;
        const res = await fetch(`{{ url('/projects') }}/${id}`, {method: 'DELETE', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
        const data = await res.json();
        if (!res.ok || !data.success) return projectAlert('error', data.message || 'Error');
        state.projects = state.projects.filter(p => Number(p.id) !== Number(id));
        renderProjects();
        projectAlert('success', data.message || 'OK');
    };

    document.getElementById('add-project-btn').addEventListener('click', () => openProjectModal(null));
    document.getElementById('add-project-link').addEventListener('click', () => projectLinksEl.insertAdjacentHTML('beforeend', linkInput('')));
    document.getElementById('add-stage-btn').addEventListener('click', () => {
        const temp = document.createElement('div');
        temp.innerHTML = stageBlock({});
        const row = temp.firstElementChild;
        projectStagesEl.appendChild(row);
        bindStageEvents(row);
    });
    searchInput?.addEventListener('input', renderProjects);

    projectForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(projectForm);
        const projectId = fd.get('project_id');

        const stageRows = Array.from(projectStagesEl.querySelectorAll('.stage-row')).map((row) => {
            return {
                stage_type: row.querySelector('.stage-type')?.value || '',
                template_id: row.querySelector('.stage-template')?.value || '',
                steps: Array.from(row.querySelectorAll('.stage-steps input')).map(i => i.value.trim()).filter(Boolean),
            };
        });
        fd.delete('stages[][stage_type]');
        fd.delete('stages[][template_id]');
        fd.delete('stages[][steps][]');
        stageRows.forEach((stage, idx) => {
            fd.append(`stages[${idx}][stage_type]`, stage.stage_type);
            if (stage.template_id) fd.append(`stages[${idx}][template_id]`, stage.template_id);
            stage.steps.forEach(step => fd.append(`stages[${idx}][steps][]`, step));
        });

        const url = projectId ? `{{ url('/projects') }}/${projectId}` : `{{ route('projects.store') }}`;
        if (projectId) fd.append('_method', 'PUT');
        const res = await fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}, body: fd});
        const data = await res.json();
        if (!res.ok || !data.success) {
            const msg = data?.message || Object.values(data?.errors || {}).flat().join('\n') || 'Error';
            return projectAlert('error', msg);
        }
        const idx = state.projects.findIndex(p => Number(p.id) === Number(data.project.id));
        if (idx >= 0) state.projects[idx] = data.project; else state.projects.unshift(data.project);
        closeProjectModal();
        renderProjects();
        projectAlert('success', data.message || 'OK');
    });

    renderObjectsSelect();
    renderProjects();
})();
</script>
@endsection
--}}

@extends('layouts.dashboard')

@section('title', __('projects.projects'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
<style>
    .tab-btn {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        cursor: pointer;
        color: #64748b;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .tab-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .tab-btn.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .funnel-column {
        min-height: 400px;
        background: #f8fafc;
        border-radius: 8px;
        padding: 1rem;
        border: 2px dashed #e2e8f0;
    }

    .funnel-column.drag-over {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .funnel-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        cursor: move;
        transition: all 0.3s;
    }

    .funnel-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .funnel-card.dragging {
        opacity: 0.5;
    }

    .sortable-header {
        cursor: pointer;
        user-select: none;
        position: relative;
        padding-right: 20px;
    }

    .sortable-header:hover {
        color: #f59e0b;
    }

    .sortable-header::after {
        content: '↕';
        position: absolute;
        right: 0;
        opacity: 0.5;
    }

    .sortable-header.asc::after {
        content: '↑';
        opacity: 1;
    }

    .sortable-header.desc::after {
        content: '↓';
        opacity: 1;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
        margin-top: 1.5rem;
    }

    .pagination button {
        padding: 0.5rem 1rem;
        border: 1px solid #e2e8f0;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        color: #64748b;
        transition: all 0.3s;
    }

    .pagination button:hover:not(:disabled) {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination button.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .tab-btn {
        background: #161615;
        border-color: #3E3E3A;
        color: #A1A09A;
    }

    .dark .tab-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .tab-btn.active {
        background: #0a0a0a;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .funnel-column {
        background: #0a0a0a;
        border-color: #3E3E3A;
    }

    .dark .funnel-column.drag-over {
        border-color: #f59e0b;
        background: #1D0002;
    }

    .dark .funnel-card {
        background: #161615;
        border-color: #3E3E3A;
    }

    .dark .pagination button {
        background: #161615;
        border-color: #3E3E3A;
        color: #A1A09A;
    }

    .dark .pagination button:hover:not(:disabled) {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .pagination button.active {
        background: #0a0a0a;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .project-stage-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.5rem;
        background: #f59e0b;
        color: #0f172a;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .project-stage-tag button {
        padding: 0;
        line-height: 1;
        color: inherit;
        opacity: 0.8;
    }
    .project-stage-tag button:hover {
        opacity: 1;
    }

    /* Stage dropdown - light theme */
    .stage-dropdown {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .stage-dropdown .stage-option {
        color: #0f172a;
    }
    .stage-dropdown .stage-option:hover {
        background: rgba(245, 158, 11, 0.15);
        color: #0f172a;
    }
    .stage-dropdown .stage-option.selected {
        background: #f59e0b;
        color: #0f172a;
    }
    .stage-dropdown .stage-option.selected:hover {
        background: #d97706;
        color: #ffffff;
    }

    /* Stage dropdown - dark theme */
    .dark .stage-dropdown {
        background: #161615;
        border-color: #3E3E3A;
    }
    .dark .stage-dropdown .stage-option {
        color: #EDEDEC;
    }
    .dark .stage-dropdown .stage-option:hover {
        background: rgba(245, 158, 11, 0.2);
        color: #EDEDEC;
    }
    .dark .stage-dropdown .stage-option.selected {
        background: #f59e0b;
        color: #0f172a;
    }
    .dark .stage-dropdown .stage-option.selected:hover {
        background: #d97706;
        color: #ffffff;
    }

    /* Step cards & stage checklist */
    .stage-steps-container {
        max-height: 380px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .stage-steps-container:empty::before {
        content: attr(data-placeholder);
        display: block;
        padding: 1.5rem;
        text-align: center;
        color: #94a3b8;
        font-size: 0.875rem;
        border: 1px dashed #e2e8f0;
        border-radius: 0.5rem;
        background: #f8fafc;
    }
    .dark .stage-steps-container:empty::before {
        color: #71716c;
        border-color: #3E3E3A;
        background: #0a0a0a;
    }
    .step-card {
        position: relative;
        border-left: 4px solid #f59e0b;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .step-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border-left-color: #d97706;
    }
    .step-card .step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 1.5rem;
        padding: 0 0.35rem;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 0.375rem;
        margin-right: 0.5rem;
    }
    .step-card .step-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #0f172a;
        line-height: 1.4;
    }
    .step-card .step-remove {
        transition: color 0.2s, background 0.2s;
    }
    .step-card .step-remove:hover {
        background: rgba(239, 68, 68, 0.1);
        border-radius: 0.25rem;
    }
    .add-step-btn {
        transition: color 0.2s, transform 0.15s;
    }
    .add-step-btn:hover {
        color: #d97706;
    }
    .add-step-btn:active {
        transform: scale(0.98);
    }
    .dark .step-card {
        border-left-color: #f59e0b;
    }
    .dark .step-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.2);
        border-left-color: #fbbf24;
    }
    .dark .step-card .step-title {
        color: #EDEDEC;
    }
    .step-card .modal-input {
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
    }
</style>
@endpush

@section('content')
<div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.projects') }}</h1>
    <button id="add-project-btn" class="add-btn">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('projects.add_project') }}
    </button>
</div>

<!-- Вкладки -->
<div class="mb-6 flex gap-2">
    <button data-tab="table" class="tab-btn active">{{ __('projects.table') }}</button>
    <button data-tab="list" class="tab-btn">{{ __('projects.list') }}</button>
    <button data-tab="funnel" class="tab-btn">{{ __('projects.funnel') }}</button>
</div>

<!-- Поиск и фильтры -->
<div class="mb-6 flex flex-col md:flex-row gap-4">
    <div class="flex-1">
        <input type="text" id="search-input" placeholder="{{ __('projects.search') }}"
               class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
    </div>
    <div class="w-full md:w-48">
        <select id="status-filter" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('projects.all_statuses') }}</option>
            <option value="contract_negotiation">{{ __('projects.status_contract_negotiation') }}</option>
            <option value="contract_signed">{{ __('projects.status_contract_signed') }}</option>
            <option value="prepayment_received">{{ __('projects.status_prepayment_received') }}</option>
            <option value="tz_signed">{{ __('projects.status_tz_signed') }}</option>
            <option value="documents_signed">{{ __('projects.status_documents_signed') }}</option>
            <option value="in_work">{{ __('projects.status_in_work') }}</option>
        </select>
    </div>
    <div class="w-full md:w-48">
        <select id="stage-filter" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('projects.all_stages') }}</option>
            <option value="measurement">{{ __('projects.stage_measurement') }}</option>
            <option value="planning">{{ __('projects.stage_planning') }}</option>
            <option value="drawings">{{ __('projects.stage_drawings') }}</option>
            <option value="equipment">{{ __('projects.stage_equipment') }}</option>
            <option value="estimate">{{ __('projects.stage_estimate') }}</option>
            <option value="visualization">{{ __('projects.stage_visualization') }}</option>
        </select>
    </div>
    <div class="w-full md:w-48">
        <select id="object-filter" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('projects.all_objects') }}</option>
            @foreach($objects as $object)
                <option value="{{ $object['id'] }}">{{ $object['address'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="w-full md:w-48">
        <select id="client-filter" class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('projects.all_clients') }}</option>
            @foreach($clients as $client)
                <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Контент вкладок -->
<div id="table-view" class="tab-content">
    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="name">{{ __('projects.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="start_date">{{ __('projects.start_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="planned_end_date">{{ __('projects.end_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="status">{{ __('projects.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="stage">{{ __('projects.stage') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="object_address">{{ __('projects.object_address') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="client_name">{{ __('projects.client') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="planned_cost">{{ __('projects.project_cost') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.links') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.comment') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.view') }}</th>
                    </tr>
                </thead>
                <tbody id="projects-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]">
                    <!-- Данные будут загружены через JavaScript -->
                </tbody>
            </table>
        </div>
        <!-- Пагинация -->
        <div class="pagination" id="pagination">
            <!-- Пагинация будет добавлена через JavaScript -->
        </div>
    </div>
</div>

<div id="list-view" class="tab-content hidden">
    <div class="space-y-4" id="projects-list-body">
        <!-- Данные будут загружены через JavaScript -->
    </div>
</div>

<div id="funnel-view" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="funnel-column" data-status="contract_negotiation" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_contract_negotiation') }}</h3>
            <div id="funnel-contract-negotiation" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="contract_signed" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_contract_signed') }}</h3>
            <div id="funnel-contract-signed" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="prepayment_received" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_prepayment_received') }}</h3>
            <div id="funnel-prepayment-received" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="tz_signed" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_tz_signed') }}</h3>
            <div id="funnel-tz-signed" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="documents_signed" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_documents_signed') }}</h3>
            <div id="funnel-documents-signed" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="in_work" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('projects.status_in_work') }}</h3>
            <div id="funnel-in-work" class="funnel-cards"></div>
        </div>
    </div>
</div>

<!-- Модалка просмотра проекта (справа) -->
<div id="view-project-modal" class="fixed inset-0 bg-black/50 z-[80] hidden modal-overlay" onmousedown="if(event.target === this) closeViewProjectModal()">
    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#e2e8f0] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between px-6 py-5 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.view') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('projects.view') }} {{ __('projects.project') }}</p>
                </div>
                <button onclick="closeViewProjectModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="view-project-content" class="flex-1 overflow-y-auto p-6 space-y-5"></div>
        </div>
    </div>
</div>

<!-- Модалка добавления/редактирования проекта -->
<div id="project-modal" class="fixed inset-0 bg-black/50 z-[80] hidden flex items-center justify-center modal-overlay p-4" onmousedown="if(event.target === this) closeProjectModal()">
    <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] shrink-0">
            <div>
                <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="project-modal-title">{{ __('projects.new_project') }}</h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('projects.project_modal_subtitle') }}</p>
            </div>
            <button type="button" onclick="closeProjectModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-all duration-200 hover:scale-110">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="project-form" class="flex flex-col flex-1 min-h-0" action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="project_id" id="project_id">
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <div>
                    <label class="modal-label modal-label-required">{{ __('projects.project_name') }}</label>
                    <input type="text" name="project_name" required class="modal-input" placeholder="{{ __('projects.project_name_placeholder') }}">
                    <p class="modal-helper">{{ __('projects.project_name_helper') }}</p>
                </div>
                <div>
                    <label class="modal-label modal-label-required">{{ __('projects.select_object') }}</label>
                    <select name="object_id" required class="modal-input">
                        <option value="">{{ __('projects.select_object_placeholder') }}</option>
                        @foreach($objects as $object)
                            <option value="{{ $object->id ?? $object['id'] }}">{{ $object->address ?? $object['address'] }}</option>
                        @endforeach
                    </select>
                    <p class="modal-helper">{{ __('projects.object_not_found') }} <a href="{{ route('objects.index') }}" class="modal-accent-link" target="_blank">{{ __('projects.create_object') }}</a></p>
                </div>
                <div>
                    <label class="modal-label modal-label-required">{{ __('projects.status') }}</label>
                    <select name="status" required class="modal-input">
                        <option value="">{{ __('projects.select_status_placeholder') }}</option>
                        <option value="contract_negotiation">{{ __('projects.status_contract_negotiation') }}</option>
                        <option value="contract_signed">{{ __('projects.status_contract_signed') }}</option>
                        <option value="prepayment_received">{{ __('projects.status_prepayment_received') }}</option>
                        <option value="tz_signed">{{ __('projects.status_tz_signed') }}</option>
                        <option value="documents_signed">{{ __('projects.status_documents_signed') }}</option>
                        <option value="in_work">{{ __('projects.status_in_work') }}</option>
                    </select>
                </div>
                <div>
                    <label class="modal-label modal-label-required">{{ __('projects.stage') }}</label>
                    <div class="project-stage-multiselect relative">
                        <div id="project-stage-trigger" class="modal-input min-h-10 pr-10 flex flex-wrap items-center gap-2 cursor-pointer" tabindex="0">
                            <div id="project-stage-tags" class="flex flex-wrap gap-1.5"></div>
                            <span id="project-stage-placeholder" class="text-[#94a3b8] dark:text-[#71716c]">{{ __('projects.select_stage_placeholder') }}</span>
                        </div>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#64748b] dark:text-[#A1A09A]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                        <div id="project-stage-dropdown" class="stage-dropdown hidden absolute left-0 right-0 top-full mt-1 z-20 rounded-lg shadow-xl max-h-48 overflow-y-auto py-1">
                            <div data-stage="measurement" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_measurement') }}</div>
                            <div data-stage="planning" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_planning') }}</div>
                            <div data-stage="drawings" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_drawings') }}</div>
                            <div data-stage="equipment" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_equipment') }}</div>
                            <div data-stage="estimate" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_estimate') }}</div>
                            <div data-stage="visualization" class="stage-option px-4 py-2 cursor-pointer transition-colors">{{ __('projects.stage_visualization') }}</div>
                        </div>
                    </div>
                    <div id="project-stage-checklists" class="mt-5 space-y-5"></div>
                </div>
                <div>
                    <label class="modal-label modal-label-required">{{ __('projects.start_date') }}</label>
                    <div class="relative">
                        <input type="text" name="start_date" id="project-start-date" required class="modal-input pr-10" placeholder="дд.мм.гггг" autocomplete="off">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b] transition-colors" onclick="document.getElementById('project-start-date')._flatpickr?.open()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label modal-label-required">{{ __('projects.planned_end_date') }}</label>
                        <div class="relative">
                            <input type="text" name="planned_end_date" id="project-planned-end-date" required class="modal-input pr-10" placeholder="дд.мм.гггг" autocomplete="off">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b] transition-colors" onclick="document.getElementById('project-planned-end-date')._flatpickr?.open()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('projects.actual_end_date') }}</label>
                        <div class="relative">
                            <input type="text" name="actual_end_date" id="project-actual-end-date" class="modal-input pr-10" placeholder="дд.мм.гггг" autocomplete="off">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b] transition-colors" onclick="document.getElementById('project-actual-end-date')._flatpickr?.open()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.project_cost') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.project_cost_subtitle') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <input type="text" name="planned_cost" id="project-planned-cost" class="modal-input" placeholder="{{ __('projects.planned_cost') }}" inputmode="numeric">
                            <p class="modal-helper">{{ __('projects.cost_helper') }}</p>
                        </div>
                        <div>
                            <input type="text" name="actual_cost" id="project-actual-cost" class="modal-input" placeholder="{{ __('projects.actual_cost') }}" inputmode="numeric">
                            <p class="modal-helper">{{ __('projects.cost_helper') }}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.links') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.links_subtitle') }}</p>
                    <div id="project-links-container" class="space-y-3">
                        <div class="input-with-icon">
                            <span class="input-icon text-[#f59e0b]"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg></span>
                            <input type="url" name="project_links[]" class="modal-input" placeholder="{{ __('projects.paste_link') }}">
                        </div>
                    </div>
                    <button type="button" onclick="addProjectLinkField()" class="mt-2 text-sm font-medium text-[#f59e0b] hover:underline flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('projects.add_link') }}
                    </button>
                </div>
                <div>
                    <h3 class="modal-section-title">{{ __('projects.files') }}</h3>
                    <p class="modal-section-subtitle">{{ __('projects.files_subtitle') }}</p>
                    <div class="flex gap-0 overflow-hidden rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        <label class="flex-1 flex items-center gap-2 px-4 py-2.5 text-[#64748b] dark:text-[#A1A09A] text-sm cursor-pointer min-h-[2.5rem]">
                            <svg class="w-5 h-5 text-[#f59e0b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg>
                            <span id="project-files-label">{{ __('projects.files_not_selected') }}</span>
                            <input type="file" name="project_files[]" id="project-files-input" multiple class="hidden">
                        </label>
                        <label for="project-files-input" class="px-4 py-2.5 bg-[#f59e0b] hover:bg-[#d97706] text-white font-medium text-sm cursor-pointer transition-colors shrink-0 flex items-center">
                            {{ __('projects.select_files') }}
                        </label>
                    </div>
                </div>
                <div>
                    <label class="modal-label">{{ __('projects.comment') }}</label>
                    <textarea name="comment" rows="3" class="modal-input resize-none" placeholder="{{ __('projects.comment_placeholder') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer flex-col sm:flex-row gap-3">
                <button type="submit" id="project-submit-btn" class="add-btn w-full sm:w-auto">{{ __('projects.add_project') }}</button>
                <button type="button" onclick="closeProjectModal()" class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('projects.go_back') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
// Глобальные переменные для проектов
let allProjects = @json($projects);
let currentView = 'table';
const projectUsers = @json($users ?? []);
const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
let templatesSource = @json($templatesData ?? []);
let templatesByType = {};
const stageLabels = { measurement: '{{ __("projects.stage_measurement") }}', planning: '{{ __("projects.stage_planning") }}', drawings: '{{ __("projects.stage_drawings") }}', equipment: '{{ __("projects.stage_equipment") }}', estimate: '{{ __("projects.stage_estimate") }}', visualization: '{{ __("projects.stage_visualization") }}' };
function getStageDisplay(project) {
    const arr = project.stages || (project.stage ? [project.stage] : []);
    return arr.map(s => stageLabels[s] || s).join(', ') || '-';
}

document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    const itemsPerPage = 10;
    let sortColumn = null;
    let sortDirection = 'asc';

    // Переключение вкладок
    document.querySelectorAll('[data-tab]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            currentView = tab;

            document.querySelectorAll('[data-tab]').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tab + '-view').classList.remove('hidden');

            if (tab === 'table') {
                renderTable();
            } else if (tab === 'list') {
                renderList();
            } else if (tab === 'funnel') {
                renderFunnel();
            }
        });
    });

    // Получение отфильтрованных проектов
    function getFilteredProjects() {
        const search = document.getElementById('search-input').value.toLowerCase();
        const statusFilter = document.getElementById('status-filter').value;
        const stageFilter = document.getElementById('stage-filter').value;
        const objectFilter = document.getElementById('object-filter').value;
        const clientFilter = document.getElementById('client-filter').value;

        return allProjects.filter(project => {
            const searchStr = Object.values(project).join(' ').toLowerCase();
            const matchSearch = !search || searchStr.includes(search);
            const matchStatus = !statusFilter || project.status === statusFilter;
            const stages = project.stages || (project.stage ? [project.stage] : []);
            const matchStage = !stageFilter || stages.includes(stageFilter);
            const matchObject = !objectFilter || project.object_id == objectFilter;
            const matchClient = !clientFilter || project.client_id == clientFilter;
            return matchSearch && matchStatus && matchStage && matchObject && matchClient;
        });
    }

    // Сортировка
    function sortProjects(column) {
        if (sortColumn === column) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = column;
            sortDirection = 'asc';
        }

        allProjects.sort((a, b) => {
            let aVal = column === 'stage' ? getStageDisplay(a) : a[column];
            let bVal = column === 'stage' ? getStageDisplay(b) : b[column];

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = (bVal || '').toString().toLowerCase();
            }

            if (sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        currentPage = 1;
        renderTable();
        updateSortHeaders();
    }

    function updateSortHeaders() {
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.classList.remove('asc', 'desc');
            if (header.dataset.sort === sortColumn) {
                header.classList.add(sortDirection);
            }
        });
    }

    // Рендеринг таблицы
    function renderTable() {
        let filtered = getFilteredProjects();

        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const paginated = filtered.slice(start, end);

        const tbody = document.getElementById('projects-table-body');
        if (!tbody) return;

        if (paginated.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.no_projects') }}</td></tr>';
            renderPagination();
            return;
        }

        tbody.innerHTML = paginated.map(project => {
            const statusClass = project.status === 'contract_negotiation' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' :
                               project.status === 'contract_signed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' :
                               project.status === 'prepayment_received' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' :
                               project.status === 'tz_signed' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200' :
                               project.status === 'documents_signed' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-200' :
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200';
            const statusText = project.status === 'contract_negotiation' ? '{{ __('projects.status_contract_negotiation') }}' :
                              project.status === 'contract_signed' ? '{{ __('projects.status_contract_signed') }}' :
                              project.status === 'prepayment_received' ? '{{ __('projects.status_prepayment_received') }}' :
                              project.status === 'tz_signed' ? '{{ __('projects.status_tz_signed') }}' :
                              project.status === 'documents_signed' ? '{{ __('projects.status_documents_signed') }}' :
                              '{{ __('projects.status_in_work') }}';
            const stageText = getStageDisplay(project);

            const startDate = new Date(project.start_date).toLocaleDateString('kk-KZ');
            const endDate = new Date(project.planned_end_date).toLocaleDateString('kk-KZ');
            const actualEndDate = project.actual_end_date ? new Date(project.actual_end_date).toLocaleDateString('kk-KZ') : '';

            return `
                <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-project='${JSON.stringify(project)}'>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${project.name}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${startDate}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                        ${endDate}
                        ${actualEndDate ? `<br><span class="text-xs text-[#64748b] dark:text-[#A1A09A]">({{ __('projects.actual_end_date') }}: ${actualEndDate})</span>` : ''}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs font-medium ${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200">${stageText}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${project.object_address}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${project.client_name}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                        <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.planned') }}: ${parseInt(project.planned_cost).toLocaleString('kk-KZ')} ₸</div>
                        ${project.actual_cost > 0 ? `<div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.actual') }}: ${parseInt(project.actual_cost).toLocaleString('kk-KZ')} ₸</div>` : ''}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        ${project.links && project.links.length > 0 ? project.links.map(link => `<a href="${link}" target="_blank" class="text-[#f59e0b] hover:underline text-xs">{{ __('projects.links') }}</a>`).join(' ') : '<span class="text-[#64748b] dark:text-[#A1A09A]">-</span>'}
                    </td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${project.comment || '-'}</td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            <button onclick="viewProject(${project.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('projects.view') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button onclick="editProject(${project.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('projects.edit') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteProject(${project.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-500 transition-colors" title="{{ __('projects.delete') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            <button onclick="addSupplierOrder(${project.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('projects.add_supplier_order') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Рендеринг списка
    function renderList() {
        let filtered = getFilteredProjects();

        const listBody = document.getElementById('projects-list-body');
        if (!listBody) return;

        listBody.innerHTML = filtered.map(project => {
            const statusClass = project.status === 'contract_negotiation' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' :
                               project.status === 'contract_signed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' :
                               project.status === 'prepayment_received' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' :
                               project.status === 'tz_signed' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200' :
                               project.status === 'documents_signed' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-200' :
                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200';
            const statusText = project.status === 'contract_negotiation' ? '{{ __('projects.status_contract_negotiation') }}' :
                              project.status === 'contract_signed' ? '{{ __('projects.status_contract_signed') }}' :
                              project.status === 'prepayment_received' ? '{{ __('projects.status_prepayment_received') }}' :
                              project.status === 'tz_signed' ? '{{ __('projects.status_tz_signed') }}' :
                              project.status === 'documents_signed' ? '{{ __('projects.status_documents_signed') }}' :
                              '{{ __('projects.status_in_work') }}';
            const stageText = getStageDisplay(project);

            const startDate = new Date(project.start_date).toLocaleDateString('kk-KZ');
            const endDate = new Date(project.planned_end_date).toLocaleDateString('kk-KZ');

            return `
                <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6" data-project='${JSON.stringify(project)}'>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${project.name}</h3>
                            <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                <p>${project.client_name}</p>
                                <p>${project.object_address}</p>
                                <p>${stageText}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-medium ${statusClass}">${statusText}</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.start_date') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${startDate}</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.end_date') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${endDate}</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.project_cost') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${parseInt(project.planned_cost).toLocaleString('kk-KZ')} ₸</span>
                        </div>
                    </div>
                    ${project.comment ? `<p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">${project.comment}</p>` : ''}
                    <div class="flex items-center gap-2">
                        <button onclick="viewProject(${project.id})" class="filter-btn">{{ __('projects.view') }}</button>
                        <button onclick="editProject(${project.id})" class="filter-btn">{{ __('projects.edit') }}</button>
                        <button onclick="deleteProject(${project.id})" class="filter-btn text-red-500 hover:text-red-600">{{ __('projects.delete') }}</button>
                        <button onclick="addSupplierOrder(${project.id})" class="filter-btn">{{ __('projects.add_supplier_order') }}</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Рендеринг воронки
    function renderFunnel() {
        const statuses = ['contract_negotiation', 'contract_signed', 'prepayment_received', 'tz_signed', 'documents_signed', 'in_work'];
        statuses.forEach(status => {
            const container = document.getElementById(`funnel-${status.replace(/_/g, '-')}`);
            if (!container) return;
            container.innerHTML = '';

            const filtered = getFilteredProjects().filter(p => p.status === status);
            filtered.forEach(project => {
                const card = document.createElement('div');
                card.className = 'funnel-card';
                card.draggable = true;
                card.dataset.projectId = project.id;
                card.ondragstart = drag;
                card.innerHTML = `
                    <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">${project.name}</h4>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">${project.client_name}</p>
                `;
                container.appendChild(card);
            });
        });
    }

    // Обработчики событий
    document.querySelectorAll('.sortable-header').forEach(header => {
        header.addEventListener('click', () => {
            sortProjects(header.dataset.sort);
        });
    });

    // Поиск
    document.getElementById('search-input').addEventListener('input', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    // Фильтры
    document.getElementById('status-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    document.getElementById('stage-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    document.getElementById('object-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    document.getElementById('client-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    function openAddProjectModal(prefillObjectId) {
        document.getElementById('project-modal').classList.remove('hidden');
        document.getElementById('project-modal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
        document.getElementById('project-form').reset();
        document.getElementById('project_id').value = '';
        document.getElementById('project-modal-title').textContent = '{{ __('projects.new_project') }}';
        document.getElementById('project-submit-btn').textContent = '{{ __('projects.add_project') }}';
        document.getElementById('project-files-label').textContent = '{{ __('projects.files_not_selected') }}';
        projectSelectedStages = [];
        window._projectEditChecklists = null;
        if (prefillObjectId) {
            const sel = document.querySelector('#project-form select[name="object_id"]');
            if (sel) sel.value = prefillObjectId;
        }
        fetchTemplates().then(() => renderProjectStageTags());
        resetProjectDateMasks();
    }
    document.getElementById('add-project-btn')?.addEventListener('click', () => openAddProjectModal());

    // Flatpickr для полей дат (календарь)
    const projectDateIds = ['project-start-date','project-planned-end-date','project-actual-end-date'];
    if (typeof flatpickr !== 'undefined') {
        const locale = document.documentElement.lang.startsWith('kk') ? 'kz' : (document.documentElement.lang.startsWith('ru') ? 'ru' : undefined);
        const isDark = document.documentElement.classList.contains('dark');
        projectDateIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                flatpickr(el, {
                    dateFormat: 'd.m.Y',
                    locale: locale,
                    theme: isDark ? 'dark' : 'default',
                    allowInput: true,
                    disableMobile: true
                });
            }
        });
    }
    window.resetProjectDateMasks = function() {
        projectDateIds.forEach(id => {
            const el = document.getElementById(id);
            if (el && el._flatpickr) el._flatpickr.clear();
        });
    };

    // Форматирование стоимости (пробелы для тысяч)
    ['project-planned-cost','project-actual-cost'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function() {
            let v = this.value.replace(/\s/g, '').replace(/\D/g, '');
            this.value = v ? parseInt(v, 10).toLocaleString('kk-KZ') : '';
        });
    });

    // Обновление метки файлов
    document.getElementById('project-files-input')?.addEventListener('change', function() {
        const label = document.getElementById('project-files-label');
        const n = this.files?.length || 0;
        label.textContent = n ? (n + ' {{ __("projects.files_selected") }}') : '{{ __('projects.files_not_selected') }}';
    });

    // Multi-select этапов с тегами и чек-листами
    window.projectSelectedStages = [];
    const stageOpts = [
        { value: 'measurement', label: '{{ __("projects.stage_measurement") }}' },
        { value: 'planning', label: '{{ __("projects.stage_planning") }}' },
        { value: 'drawings', label: '{{ __("projects.stage_drawings") }}' },
        { value: 'equipment', label: '{{ __("projects.stage_equipment") }}' },
        { value: 'estimate', label: '{{ __("projects.stage_estimate") }}' },
        { value: 'visualization', label: '{{ __("projects.stage_visualization") }}' }
    ];
    function syncProjectStages() {
        document.querySelectorAll('input[name="stages[]"]').forEach(el => el.remove());
        const container = document.getElementById('project-form');
        projectSelectedStages.forEach(v => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'stages[]';
            inp.value = v;
            container.appendChild(inp);
        });
    }
    function renderProjectStageTags() {
        const tagsEl = document.getElementById('project-stage-tags');
        const placeholderEl = document.getElementById('project-stage-placeholder');
        tagsEl.innerHTML = '';
        projectSelectedStages.forEach(value => {
            const label = stageLabels[value] || value;
            const tag = document.createElement('span');
            tag.className = 'project-stage-tag';
            tag.innerHTML = `${label} <button type="button" onclick="event.stopPropagation(); projectRemoveStage('${value}')">×</button>`;
            tagsEl.appendChild(tag);
        });
        placeholderEl.style.display = projectSelectedStages.length ? 'none' : 'inline';
        syncProjectStages();
        renderProjectStageChecklists(window._projectEditChecklists);
        document.querySelectorAll('#project-stage-dropdown .stage-option').forEach(opt => {
            opt.classList.toggle('selected', projectSelectedStages.includes(opt.dataset.stage));
        });
    }
    async function fetchTemplates() {
        try {
            templatesByType = (Array.isArray(templatesSource) ? templatesSource : []).reduce((acc, t) => {
                const type = t.type || 'other';
                if (!acc[type]) acc[type] = [];
                acc[type].push({
                    id: t.id,
                    name: t.name || '',
                    steps: t.steps || [],
                    is_owner: Boolean(t.is_owner ?? t.is_owned),
                });
                return acc;
            }, {});
        } catch (e) { console.warn('Templates fetch failed', e); }
    }
    function renderProjectStageChecklists(initialChecklists) {
        const container = document.getElementById('project-stage-checklists');
        container.innerHTML = '';
        projectSelectedStages.forEach((stageValue, idx) => {
            const checklistData = (initialChecklists || {})[stageValue];
            const label = stageLabels[stageValue] || stageValue;
            const templates = (templatesByType && templatesByType[stageValue]) || [];
            console.log("templates", templates);
            const selTemplateId = (checklistData?.template_id || '').toString();
            const optionsHtml = templates.map(t => `<option value="${t.id}" data-is-owner="${t.is_owner ? '1' : '0'}"${t.id == selTemplateId ? ' selected' : ''}>${(t.name || '').replace(/"/g, '&quot;')}</option>`).join('');
            
            const sect = document.createElement('div');
            sect.className = 'checklist-section rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a] overflow-hidden';
            sect.dataset.stage = stageValue;
            sect.innerHTML = `
                <button type="button" class="checklist-toggle w-full px-4 py-3 flex items-center justify-between gap-2 text-left hover:bg-[#f1f5f9] dark:hover:bg-[#161615] transition-colors">
                    <h4 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC] flex items-center gap-2">
                        {{ __('projects.stage_checklist') }}
                        <span class="project-stage-tag">${label}</span>
                    </h4>
                    <svg class="checklist-chevron w-5 h-5 text-[#64748b] dark:text-[#A1A09A] shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="checklist-body px-4 pb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 min-w-0">
                            <label class="modal-label">{{ __('projects.select_template') }}</label>
                            <select name="stage_checklist_template[${stageValue}]" class="modal-input stage-template-select" data-stage="${stageValue}">
                                <option value="">{{ __('projects.select_template') }}</option>
                                ${optionsHtml}
                            </select>
                        </div>
                        <button type="button" class="delete-template-btn hidden shrink-0 px-3 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" data-stage="${stageValue}" title="{{ __('projects.delete_template') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('projects.deadline_until') }}</label>
                        <div class="relative">
                            <input type="text" name="stage_checklist_deadline[${stageValue}]" class="modal-input pr-10 project-stage-deadline" placeholder="дд.мм.гггг" autocomplete="off" data-stage="${stageValue}" value="${(checklistData?.deadline || '').toString().replace(/"/g, '&quot;')}">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:text-[#f59e0b]" onclick="this.previousElementSibling._flatpickr?.open()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('projects.responsible') }}</label>
                        <select name="stage_checklist_responsible[${stageValue}]" class="modal-input">
                            <option value="">{{ __('projects.not_selected') }}</option>
                            ${(projectUsers || []).map(u => `<option value="${u.id}"${u.id == (checklistData?.responsible_id || '') ? ' selected' : ''}>${(u.name || u.email || '').replace(/"/g, '&quot;')}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 mb-4">
                    <input type="checkbox" name="stage_checklist_assign[${stageValue}]" class="rounded"${checklistData?.assign_task ? ' checked' : ''}>
                    <span class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.assign_task') }}</span>
                </label>
                <div class="stage-steps-container mb-3" data-stage="${stageValue}" data-placeholder="{{ __('projects.select_template') }}"></div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="add-step-btn text-sm font-medium text-[#f59e0b] hover:underline flex items-center gap-1.5" data-stage="${stageValue}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('projects.add_item') }}
                    </button>
                    <button type="button" class="save-template-btn text-sm font-medium text-[#22c55e] hover:underline flex items-center gap-1.5" data-stage="${stageValue}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        {{ __('projects.save_as_template') }}
                    </button>
                </div>
                </div>
            `;
            container.appendChild(sect);
            const steps = checklistData?.steps || [];
            if (steps.length) {
                renderStageSteps(sect, stageValue, steps);
            } else {
                const t = templates.find(x => x.id == selTemplateId);
                if (t) renderStageSteps(sect, stageValue, t.steps || []);
            }
            if (selTemplateId) {
                const isOwner = templates.find(x => x.id == selTemplateId)?.is_owner;
                if (isOwner) sect.querySelector('.delete-template-btn')?.classList.remove('hidden');
            }
            sect.querySelector('.checklist-toggle')?.addEventListener('click', function() {
                const body = sect.querySelector('.checklist-body');
                const chevron = sect.querySelector('.checklist-chevron');
                if (body && chevron) {
                    body.classList.toggle('hidden');
                    chevron.classList.toggle('-rotate-90', body.classList.contains('hidden'));
                }
            });
            sect.querySelector('.stage-template-select')?.addEventListener('change', function() {
                const tid = this.value;
                const opt = this.options[this.selectedIndex];
                const isOwner = opt?.dataset.isOwner === '1';
                sect.querySelector('.delete-template-btn')?.classList.toggle('hidden', !tid || !isOwner);
                const t = templates.find(x => x.id == tid);
                renderStageSteps(sect, stageValue, t ? t.steps : []);
            });
            sect.querySelector('.add-step-btn')?.addEventListener('click', function() {
                const stepsEl = sect.querySelector('.stage-steps-container');
                if (!stepsEl) return;
                const n = stepsEl.querySelectorAll('.step-card').length + 1;
                addStepCard(stepsEl, stageValue, n, '');
            });
            sect.querySelector('.save-template-btn')?.addEventListener('click', async function() {
                const stepsEl = sect.querySelector('.stage-steps-container');
                if (!stepsEl) return;
                const titles = Array.from(stepsEl.querySelectorAll('.step-card .step-title')).map(inp => (inp.value || '').trim()).filter(Boolean);
                if (!titles.length) { alert('{{ __('projects.add_at_least_one_step') }}'); return; }
                const name = prompt('{{ __('projects.template_name_placeholder') }}:', '');
                if (!name || !name.trim()) return;
                try {
                    const res = await fetch('{{ route('projects.templates.store') }}', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ name: name.trim(), type: stageValue, steps: titles })
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success || !data.template) {
                        throw new Error(data.message || 'Save failed');
                    }
                    templatesSource.unshift(data.template);
                    await fetchTemplates();
                    renderProjectStageChecklists();
                    const newSect = document.getElementById('project-stage-checklists')?.querySelector(`[data-stage="${stageValue}"]`);
                    const sel = newSect?.querySelector('.stage-template-select');
                    if (sel) { sel.value = String(data.template.id); sel.dispatchEvent(new Event('change')); }
                } catch (e) { alert(e.message || 'Ошибка сохранения'); }
            });
            sect.querySelector('.delete-template-btn')?.addEventListener('click', async function() {
                const sel = sect.querySelector('.stage-template-select');
                const tid = sel?.value;
                if (!tid) return;
                const opt = sel?.options[sel.selectedIndex];
                if (opt?.dataset.isOwner !== '1') return;
                if (!confirm('{{ __('projects.delete_template_confirm') }}')) return;
                try {
                    const res = await fetch(`{{ url('/projects/templates') }}/${tid}`, {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
                    });
                    if (!res.ok) throw new Error('Delete failed');
                    templatesSource = templatesSource.filter(t => String(t.id) !== String(tid));
                    await fetchTemplates();
                    renderProjectStageChecklists();
                } catch (e) { alert(e.message || 'Ошибка удаления'); }
            });
        });
        function renderStageSteps(sect, stageValue, steps) {
            const stepsEl = sect?.querySelector('.stage-steps-container');
            if (!stepsEl) return;
            stepsEl.innerHTML = '';
            (steps || []).forEach((s, i) => {
                const step = typeof s === 'object' ? s : { title: s };
                addStepCard(stepsEl, stageValue, i + 1, step.title || step, step);
            });
        }
        function addStepCard(container, stageValue, num, title, stepData) {
            const card = document.createElement('div');
            card.className = 'step-card rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] p-4 bg-white dark:bg-[#161615]';
            const safeTitle = (title || '').toString().replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            card.innerHTML = `
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="flex items-start gap-2 flex-1 min-w-0">
                        <span class="step-num shrink-0">${num}</span>
                        <input type="text" name="stage_step_title[${stageValue}][]" class="step-title modal-input flex-1 min-w-0 text-sm border-0 p-0 bg-transparent focus:ring-0" placeholder="{{ __('projects.step_title_placeholder') }}" value="${safeTitle}">
                    </div>
                    <button type="button" class="step-remove p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:text-red-500 shrink-0" title="{{ __('projects.delete') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 border-t border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <div>
                        <label class="modal-label text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.deadline_until') }}</label>
                        <div class="relative">
                            <input type="text" name="stage_step_deadline[${stageValue}][]" class="modal-input pr-10 step-deadline text-sm" placeholder="дд.мм.гггг" autocomplete="off">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[#64748b] cursor-pointer hover:text-[#f59e0b]" onclick="this.previousElementSibling._flatpickr?.open()">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.responsible') }}</label>
                        <select name="stage_step_responsible[${stageValue}][]" class="modal-input text-sm">
                            <option value="">{{ __('projects.not_selected') }}</option>
                            ${(projectUsers || []).map(u => `<option value="${u.id}">${(u.name || u.email || '').replace(/"/g, '&quot;')}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="modal-label text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.links') }}</label>
                        <input type="url" name="stage_step_link[${stageValue}][]" class="modal-input text-sm" placeholder="{{ __('projects.paste_link') }}">
                    </div>
                </div>
            `;
            container.appendChild(card);
            if (stepData && typeof stepData === 'object') {
                const dl = card.querySelector('.step-deadline');
                if (dl && stepData.deadline) { dl.value = stepData.deadline; }
                const resp = card.querySelector('select[name^="stage_step_responsible"]');
                if (resp && stepData.responsible_id) { resp.value = stepData.responsible_id; }
                const lnk = card.querySelector('input[type="url"]');
                if (lnk && stepData.link) { lnk.value = stepData.link; }
            }
            card.querySelector('.step-remove')?.addEventListener('click', () => card.remove());
            const dateInput = card.querySelector('.step-deadline');
            if (dateInput && typeof flatpickr !== 'undefined' && !dateInput._flatpickr) {
                flatpickr(dateInput, { dateFormat: 'd.m.Y', locale: document.documentElement.lang.startsWith('kk') ? 'kz' : (document.documentElement.lang.startsWith('ru') ? 'ru' : undefined), allowInput: true, disableMobile: true });
            }
        }
        container.querySelectorAll('.project-stage-deadline').forEach(el => {
            if (typeof flatpickr !== 'undefined' && !el._flatpickr) {
                flatpickr(el, { dateFormat: 'd.m.Y', locale: document.documentElement.lang.startsWith('kk') ? 'kz' : (document.documentElement.lang.startsWith('ru') ? 'ru' : undefined), allowInput: true, disableMobile: true });
            }
        });
    }
    window.projectRemoveStage = function(value) {
        projectSelectedStages = projectSelectedStages.filter(s => s !== value);
        renderProjectStageTags();
    };
    function projectToggleStage(value) {
        if (projectSelectedStages.includes(value)) {
            projectSelectedStages = projectSelectedStages.filter(s => s !== value);
        } else {
            projectSelectedStages.push(value);
        }
        renderProjectStageTags();
    }
    document.getElementById('project-stage-trigger')?.addEventListener('click', function(e) {
        if (e.target.closest('.project-stage-tag button')) return;
        document.getElementById('project-stage-dropdown').classList.toggle('hidden');
    });
    document.querySelectorAll('#project-stage-dropdown .stage-option').forEach(opt => {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            projectToggleStage(this.dataset.stage);
        });
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.project-stage-multiselect')) {
            document.getElementById('project-stage-dropdown')?.classList.add('hidden');
        }
    });

    // Обработка формы проекта
    document.getElementById('project-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!projectSelectedStages || projectSelectedStages.length === 0) {
            alert('{{ __("projects.select_stage_placeholder") }}');
            return;
        }
        const form = this;
        const projectId = document.getElementById('project_id').value;
        const url = projectId ? '{{ url("projects") }}/' + projectId : '{{ route("projects.store") }}';
        const submitBtn = document.getElementById('project-submit-btn');
        const origText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '...';
        try {
            const fd = new FormData(form);
            if (projectId) fd.append('_method', 'PUT');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Ошибка сохранения');
            }
            const idx = allProjects.findIndex(p => p.id == data.id);
            if (idx >= 0) {
                allProjects[idx] = data;
            } else {
                allProjects.unshift(data);
            }
            closeProjectModal();
            if (currentView === 'table') renderTable();
            else if (currentView === 'list') renderList();
            else if (currentView === 'funnel') renderFunnel();
        } catch (err) {
            alert(err.message || 'Ошибка сохранения');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = origText;
        }
    });

    window.renderTable = renderTable;
    window.renderList = renderList;
    window.renderFunnel = renderFunnel;
    window.fetchTemplates = fetchTemplates;
    window.renderProjectStageTags = renderProjectStageTags;

    // Инициализация
    renderTable();
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('add_project') === '1') {
        const objectId = urlParams.get('object_id') || '';
        setTimeout(() => openAddProjectModal(objectId), 100);
        history.replaceState({}, '', '{{ route("projects.index") }}');
    }
});

// Функции для работы с модалками
function closeViewProjectModal() {
    const modal = document.getElementById('view-project-modal');
    const panel = modal.querySelector('div[class*="absolute"]');
    modal.classList.add('hidden');
    if (panel) {
        panel.classList.add('translate-x-full');
        panel.classList.remove('translate-x-0');
    }
    document.body.classList.remove('overflow-hidden');
}

function viewProject(id) {
    const rows = document.querySelectorAll(`tr[data-project], div[data-project]`);
    let project = null;
    rows.forEach(row => {
        const p = JSON.parse(row.getAttribute('data-project'));
        if (p.id === id) {
            project = p;
        }
    });
    if (project) {
        const content = document.getElementById('view-project-content');
        const statusText = project.status === 'contract_negotiation' ? '{{ __('projects.status_contract_negotiation') }}' :
                          project.status === 'contract_signed' ? '{{ __('projects.status_contract_signed') }}' :
                          project.status === 'prepayment_received' ? '{{ __('projects.status_prepayment_received') }}' :
                          project.status === 'tz_signed' ? '{{ __('projects.status_tz_signed') }}' :
                          project.status === 'documents_signed' ? '{{ __('projects.status_documents_signed') }}' :
                          '{{ __('projects.status_in_work') }}';
        const stageText = getStageDisplay(project);

        const startDate = new Date(project.start_date).toLocaleDateString('kk-KZ');
        const endDate = new Date(project.planned_end_date).toLocaleDateString('kk-KZ');
        const actualEndDate = project.actual_end_date ? new Date(project.actual_end_date).toLocaleDateString('kk-KZ') : '';

        content.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.project_name') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${project.name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.object_address') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${project.object_address}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.client') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${project.client_name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.status') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${statusText}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.stage') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${stageText}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.start_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${startDate}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.planned_end_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${endDate}</p>
            </div>
            ${actualEndDate ? `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.actual_end_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${actualEndDate}</p>
            </div>
            ` : ''}
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.project_cost') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">
                    {{ __('projects.planned') }}: ${parseInt(project.planned_cost).toLocaleString('kk-KZ')} ₸<br>
                    {{ __('projects.actual') }}: ${project.actual_cost > 0 ? parseInt(project.actual_cost).toLocaleString('kk-KZ') + ' ₸' : '-'}
                </p>
            </div>
            ${project.comment ? `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.comment') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${project.comment}</p>
            </div>
            ` : ''}
        `;
        const modal = document.getElementById('view-project-modal');
        const panel = modal.querySelector('div[class*="absolute"]');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            if (panel) {
                panel.classList.remove('translate-x-full');
                panel.classList.add('translate-x-0');
            }
        }, 10);
    }
}

function dateToDdMmYyyy(str) {
    if (!str) return '';
    const d = new Date(str);
    if (isNaN(d.getTime())) return str;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}.${month}.${year}`;
}

async function editProject(id) {
    const rows = document.querySelectorAll(`tr[data-project], div[data-project]`);
    let project = null;
    rows.forEach(row => {
        const p = JSON.parse(row.getAttribute('data-project'));
        if (p.id == id) {
            project = p;
        }
    });
    if (project) {
        document.getElementById('project-modal').classList.remove('hidden');
        document.getElementById('project-modal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
        document.getElementById('project-modal-title').textContent = '{{ __('projects.edit_project') }}';
        window._projectEditChecklists = project.stage_checklists || {};
        await fetchTemplates();
        document.getElementById('project-submit-btn').textContent = '{{ __('projects.save') }}';
        document.getElementById('project_id').value = project.id;
        document.querySelector('input[name="project_name"]').value = project.name || '';
        document.querySelector('select[name="object_id"]').value = project.object_id || '';
        document.querySelector('select[name="status"]').value = project.status || '';
        projectSelectedStages = project.stages || (project.stage ? [project.stage] : []);
        renderProjectStageTags();
        const startEl = document.getElementById('project-start-date');
        const plannedEl = document.getElementById('project-planned-end-date');
        const actualEl = document.getElementById('project-actual-end-date');
        if (project.start_date) { startEl?._flatpickr ? startEl._flatpickr.setDate(project.start_date) : (startEl.value = dateToDdMmYyyy(project.start_date)); }
        if (project.planned_end_date) { plannedEl?._flatpickr ? plannedEl._flatpickr.setDate(project.planned_end_date) : (plannedEl.value = dateToDdMmYyyy(project.planned_end_date)); }
        if (project.actual_end_date) { actualEl?._flatpickr ? actualEl._flatpickr.setDate(project.actual_end_date) : (actualEl.value = dateToDdMmYyyy(project.actual_end_date)); }
        const plannedCost = parseInt(project.planned_cost, 10) || '';
        const actualCost = parseInt(project.actual_cost, 10) || '';
        document.getElementById('project-planned-cost').value = plannedCost ? plannedCost.toLocaleString('kk-KZ') : '';
        document.getElementById('project-actual-cost').value = actualCost ? actualCost.toLocaleString('kk-KZ') : '';
        document.querySelector('textarea[name="comment"]').value = project.comment || '';
        const links = project.links || [];
        const linksContainer = document.getElementById('project-links-container');
        const firstInput = linksContainer.querySelector('.input-with-icon input');
        if (firstInput) firstInput.value = links[0] || '';
        linksContainer.querySelectorAll('.link-row-extra').forEach(el => el.remove());
        for (let i = 1; i < links.length; i++) {
            addProjectLinkField(links[i]);
        }
        document.getElementById('project-files-label').textContent = '{{ __('projects.files_not_selected') }}';
        document.getElementById('project-files-input').value = '';
    }
}

async function deleteProject(id) {
    if (!confirm('{{ __('projects.delete') }}?')) return;
    try {
        const fd = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]')?.value || '');
        fd.append('_method', 'DELETE');
        const res = await fetch(`{{ url('projects') }}/${id}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        });
        if (!res.ok) throw new Error('Delete failed');
        allProjects = allProjects.filter(p => p.id != id);
        if (currentView === 'table') renderTable();
        else if (currentView === 'list') renderList();
        else if (currentView === 'funnel') renderFunnel();
    } catch (e) {
        alert(e.message || 'Ошибка удаления');
    }
}

function closeProjectModal() {
    document.getElementById('project-modal').classList.add('hidden');
    document.getElementById('project-modal').classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('project-form').reset();
    window._projectEditChecklists = null;
    const linksContainer = document.getElementById('project-links-container');
    linksContainer.innerHTML = `
        <div class="input-with-icon">
            <span class="input-icon text-[#f59e0b]"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg></span>
            <input type="url" name="project_links[]" class="modal-input" placeholder="{{ __('projects.paste_link') }}">
        </div>
    `;
    document.getElementById('project-files-label').textContent = '{{ __('projects.files_not_selected') }}';
    projectSelectedStages = [];
    if (typeof renderProjectStageTags === 'function') renderProjectStageTags();
    if (typeof resetProjectDateMasks === 'function') resetProjectDateMasks();
}

function addProjectLinkField(initialValue) {
    const container = document.getElementById('project-links-container');
    const div = document.createElement('div');
    div.className = 'input-with-icon link-row-extra flex gap-2 items-center';
    div.innerHTML = `
        <span class="input-icon text-[#f59e0b] shrink-0"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/></svg></span>
        <input type="url" name="project_links[]" class="modal-input flex-1" placeholder="{{ __('projects.paste_link') }}" value="${(initialValue || '').replace(/"/g, '&quot;')}">
        <button type="button" onclick="this.closest('.link-row-extra').remove()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 shrink-0 transition-colors" title="{{ __('projects.delete') }}">×</button>
    `;
    container.appendChild(div);
}

function addSupplierOrder(projectId) {
    // Перенаправляем на страницу поставок с предзаполненным проектом
    window.location.href = '{{ route("supplier-orders.index") }}?project_id=' + projectId;
}

// Drag & Drop для воронок
let draggedProjectElement = null;

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function drag(ev) {
    draggedProjectElement = ev.target.closest('.funnel-card');
    if (draggedProjectElement) {
        draggedProjectElement.classList.add('dragging');
        ev.dataTransfer.effectAllowed = 'move';
    }
}

function drop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');

    if (draggedProjectElement) {
        const newStatus = ev.currentTarget.dataset.status;
        const projectId = draggedProjectElement.dataset.projectId;

        const project = allProjects.find(p => p.id == projectId);
        if (project) {
            project.status = newStatus;
        }

        ev.currentTarget.querySelector('.funnel-cards').appendChild(draggedProjectElement);
        draggedProjectElement.classList.remove('dragging');

        const fd = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]')?.value || '');
        fd.append('_method', 'PATCH');
        fd.append('status', newStatus);
        fetch(`{{ url('projects') }}/${projectId}/status`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        }).then(r => r.json()).then(data => {
            const idx = allProjects.findIndex(p => p.id == data.id);
            if (idx >= 0) allProjects[idx] = data;
        }).catch(() => {});

        draggedProjectElement = null;
    }
}

// Убираем класс drag-over при уходе мыши
document.querySelectorAll('.funnel-column').forEach(column => {
    column.addEventListener('dragleave', function(e) {
        this.classList.remove('drag-over');
    });
});
</script>
@endsection
