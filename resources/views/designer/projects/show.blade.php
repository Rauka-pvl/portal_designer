@extends('layouts.dashboard')

@section('title', $projectData['name'] ?? __('projects.project'))

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #7c8799;
            border-radius: 12px;
            padding: 1.25rem;
        }
        .dark .panel { background: #161615; border-color: #3E3E3A; }
        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #7c8799;
            background: #ffffff;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 500;
        }
        .btn:hover { border-color: #f59e0b; color: #f59e0b; }
        .dark .btn { background: #0a0a0a; border-color: #3E3E3A; color: #A1A09A; }
        .btn-danger { border-color: rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.12); color: #dc2626; }
        .details-tab-btn {
            padding: 0.5rem 0.9rem;
            border-radius: 10px;
            border: 1px solid #7c8799;
            color: #64748b;
            background: #fff;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }
        .details-tab-btn.active {
            border-color: #f59e0b;
            color: #f59e0b;
            background: #f8fafc;
        }
        .dark .details-tab-btn {
            background: #0a0a0a;
            border-color: #3E3E3A;
            color: #A1A09A;
        }
        .dark .details-tab-btn.active {
            background: #161615;
            border-color: #f59e0b;
            color: #f59e0b;
        }
        .checklist-item-done {
            border-color: #f59e0b !important;
            background: #fef3c7;
        }
        .dark .checklist-item-done {
            background: rgba(245, 158, 11, 0.18);
        }
        .check-toggle-circle {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #7c8799;
            background: #ffffff;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.18s ease;
        }
        .check-toggle-circle.is-done {
            border-color: #f59e0b;
            background: #f59e0b;
            color: #ffffff;
        }
        .dark .check-toggle-circle {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #71717a;
        }
        .dark .check-toggle-circle.is-done {
            border-color: #f59e0b;
            background: #f59e0b;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    @php
        $p = $projectData;
        $statusMap = [
            'in_moderation' => __('projects.status_in_moderation'),
            'rejected' => __('projects.status_rejected'),
            'contract_negotiation' => __('projects.status_contract_negotiation'),
            'contract_signed' => __('projects.status_contract_signed'),
            'prepayment_received' => __('projects.status_prepayment_received'),
            'tz_signed' => __('projects.status_tz_signed'),
            'documents_signed' => __('projects.status_documents_signed'),
            'in_work' => __('projects.status_in_work'),
        ];
    @endphp

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $p['name'] ?? '-' }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('projects.project') }} #{{ $p['id'] ?? '-' }}</p>
        </div>
        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">{{ __('projects.edit') }}</button>
            <form method="POST" action="{{ route('projects.destroy', $p['id']) }}" onsubmit="return confirm('{{ __('projects.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('projects.delete') }}</button>
            </form>
            <a href="{{ route('projects.index') }}" class="btn">{{ __('projects.close') }}</a>
        </div>
    </div>

    <div class="mb-5 flex items-center gap-2">
        <button type="button" class="details-tab-btn active" data-details-tab-btn="general">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h6" /></svg>
            {{ __('projects.details') }}
        </button>
        <button type="button" class="details-tab-btn" data-details-tab-btn="checklist">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 104 0M9 5a2 2 0 014 0m-6 7l2 2 4-4" /></svg>
            {{ __('projects.stage_checklist') }}
        </button>
    </div>

    <div id="details-general-tab" class="panel">
        <form id="project-details-form" method="POST" action="{{ route('projects.update', $p['id']) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.project_name') }}</div>
                    <input name="name" required value="{{ $p['name'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.select_object') }}</div>
                    <select name="object_id" required disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        @foreach (($objects ?? []) as $obj)
                            <option value="{{ $obj->id }}" @selected((int) ($p['object_id'] ?? 0) === (int) $obj->id)>
                                {{ $obj->address }}{{ $obj->city ? ' (' . $obj->city . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.status') }}</div>
                    <select name="status" required disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        <option value="contract_negotiation" @selected(($p['status'] ?? '') === 'contract_negotiation')>{{ __('projects.status_contract_negotiation') }}</option>
                        <option value="contract_signed" @selected(($p['status'] ?? '') === 'contract_signed')>{{ __('projects.status_contract_signed') }}</option>
                        <option value="prepayment_received" @selected(($p['status'] ?? '') === 'prepayment_received')>{{ __('projects.status_prepayment_received') }}</option>
                        <option value="tz_signed" @selected(($p['status'] ?? '') === 'tz_signed')>{{ __('projects.status_tz_signed') }}</option>
                        <option value="documents_signed" @selected(($p['status'] ?? '') === 'documents_signed')>{{ __('projects.status_documents_signed') }}</option>
                        <option value="in_work" @selected(($p['status'] ?? '') === 'in_work')>{{ __('projects.status_in_work') }}</option>
                    </select>
                </div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.start_date') }}</div><input type="date" name="start_date" value="{{ $p['start_date'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.planned_end_date') }}</div><input type="date" name="planned_end_date" value="{{ $p['planned_end_date'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.actual_end_date') }}</div><input type="date" name="actual_end_date" value="{{ $p['actual_end_date'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.planned_cost') }}</div><input type="number" step="0.01" name="planned_cost" value="{{ $p['planned_cost'] ?? 0 }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.actual_cost') }}</div><input type="number" step="0.01" name="actual_cost" value="{{ $p['actual_cost'] ?? 0 }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.comment') }}</div><textarea name="comment" rows="3" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $p['comment'] ?? '' }}</textarea></div>
                <div class="md:col-span-2">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.files') }}</div>
                    <input type="file" name="files[]" multiple disabled
                        class="w-full text-sm text-[#64748b] dark:text-[#A1A09A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                    @include('partials.file-actions-list', [
                        'filePaths' => $p['files'] ?? [],
                        'deleteCallback' => 'window.deleteProjectFileFromShow',
                        'deleteEntityId' => $p['id'],
                    ])
                </div>

                @foreach (($p['links'] ?? []) as $link)
                    <input type="hidden" name="links[]" value="{{ $link }}">
                @endforeach
                @foreach (($p['files'] ?? []) as $filePath)
                    <input type="hidden" name="existing_files[]" value="{{ $filePath }}">
                @endforeach
                @foreach (($p['stages'] ?? []) as $si => $stage)
                    <input type="hidden" name="stages[{{ $si }}][stage_type]" value="{{ $stage['stage_type'] ?? '' }}">
                    <input type="hidden" name="stages[{{ $si }}][template_id]" value="{{ $stage['template_id'] ?? '' }}">
                    <input type="hidden" name="stages[{{ $si }}][deadline]" value="{{ $stage['deadline'] ?? '' }}">
                    <input type="hidden" name="stages[{{ $si }}][assign_task]" value="{{ !empty($stage['assign_task']) ? 1 : 0 }}">
                    <input type="hidden" name="stages[{{ $si }}][responsible_id]" value="{{ $stage['responsible_id'] ?? '' }}">
                    @foreach (($stage['steps'] ?? []) as $ti => $step)
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][title]" value="{{ $step['title'] ?? '' }}">
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][deadline]" value="{{ $step['deadline'] ?? '' }}">
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][responsible_id]" value="{{ $step['responsible_id'] ?? '' }}">
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][link]" value="{{ $step['link'] ?? '' }}">
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][result_status]" value="{{ $step['result_status'] ?? 'pending' }}">
                        <input type="hidden" name="stages[{{ $si }}][steps][{{ $ti }}][result_comment]" value="{{ $step['result_comment'] ?? '' }}">
                    @endforeach
                @endforeach
            </div>
            <div class="mt-6 flex gap-3">
                <button id="btn-save" type="submit" class="btn hidden">{{ __('projects.save') }}</button>
                <button id="btn-cancel" type="button" class="btn hidden">{{ __('projects.cancel') }}</button>
            </div>
        </form>
    </div>

    <div id="details-checklist-tab" class="panel hidden">
        @php
            $rawStages = $p['stages'] ?? [];
            if ($rawStages instanceof \Illuminate\Support\Collection) {
                $stages = $rawStages->all();
            } elseif (is_array($rawStages)) {
                $stages = $rawStages;
            } else {
                $stages = [];
            }
        @endphp
        @if (count($stages) === 0)
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.no_projects') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($stages as $stage)
                    @php
                        $rawSteps = $stage['steps'] ?? [];
                        if ($rawSteps instanceof \Illuminate\Support\Collection) {
                            $steps = $rawSteps->all();
                        } elseif (is_array($rawSteps)) {
                            $steps = $rawSteps;
                        } else {
                            $steps = [];
                        }
                    @endphp
                    <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] overflow-hidden">
                        <div class="px-4 py-3 bg-[#f8fafc] dark:bg-[#0a0a0a] border-b border-[#7c8799] dark:border-[#3E3E3A] text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                            {{ $stage['stage_type_label'] ?? ($stage['stage_type'] ?? __('projects.stage')) }}
                        </div>
                        <div class="p-4 space-y-3">
                            @foreach ($steps as $step)
                                @php
                                    $isDone = (($step['result_status'] ?? 'pending') === 'done');
                                    $stepDeadline = $step['deadline'] ?? null;
                                @endphp
                                <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] p-3 checklist-step-card {{ $isDone ? 'checklist-item-done' : '' }}" data-step-id="{{ $step['id'] }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $step['title'] ?? '-' }}</h4>
                                            @if (!empty($stepDeadline))
                                                <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">
                                                    {{ __('projects.deadline') }}:
                                                    {{ \Illuminate\Support\Carbon::parse($stepDeadline)->format('d.m.Y') }}
                                                </p>
                                            @endif
                                            @if (!empty($step['link']))
                                                <a href="{{ $step['link'] }}" target="_blank" rel="noopener noreferrer" class="text-xs text-[#f59e0b] hover:underline">{{ __('projects.links') }}</a>
                                            @endif
                                        </div>
                                        <button type="button"
                                            class="checklist-toggle-btn check-toggle-circle {{ $isDone ? 'is-done' : '' }}"
                                            data-step-id="{{ $step['id'] }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('projects.step_result_comment') }}</label>
                                        <textarea class="checklist-comment mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] resize-none"
                                            rows="2"
                                            data-step-id="{{ $step['id'] }}">{{ $step['result_comment'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const form = document.getElementById('project-details-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const generalTab = document.getElementById('details-general-tab');
            const checklistTab = document.getElementById('details-checklist-tab');
            const tabButtons = document.querySelectorAll('[data-details-tab-btn]');

            const setActiveTab = (name) => {
                const isChecklist = name === 'checklist';
                generalTab?.classList.toggle('hidden', isChecklist);
                checklistTab?.classList.toggle('hidden', !isChecklist);
                tabButtons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.detailsTabBtn === name);
                });
            };
            tabButtons.forEach((btn) => {
                btn.addEventListener('click', () => setActiveTab(btn.dataset.detailsTabBtn || 'general'));
            });

            const saveChecklistStep = async (stepId, resultStatus, resultComment) => {
                const body = new URLSearchParams();
                body.set('_token', csrfToken);
                body.set('_method', 'PUT');
                body.set('result_status', resultStatus);
                body.set('result_comment', resultComment || '');

                const res = await fetch(`/checklist-steps/${stepId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: body.toString(),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'save_failed');
                }
                return data.step || { result_status: resultStatus, result_comment: resultComment };
            };

            const applyChecklistUi = (card, status) => {
                const done = status === 'done';
                card.classList.toggle('checklist-item-done', done);
                const btn = card.querySelector('.checklist-toggle-btn');
                if (!btn) return;
                btn.classList.toggle('is-done', done);
            };

            checklistTab?.querySelectorAll('.checklist-toggle-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const stepId = Number(btn.dataset.stepId || 0);
                    if (!stepId) return;
                    const card = checklistTab.querySelector(`.checklist-step-card[data-step-id="${stepId}"]`);
                    if (!card) return;
                    const commentEl = card.querySelector('.checklist-comment');
                    const currentlyDone = card.classList.contains('checklist-item-done');
                    const nextStatus = currentlyDone ? 'pending' : 'done';
                    try {
                        const step = await saveChecklistStep(stepId, nextStatus, commentEl?.value || '');
                        applyChecklistUi(card, step.result_status || nextStatus);
                        projectAlert('success', '{{ __('projects.updated') }}', '', 1400);
                    } catch (e) {
                        console.error(e);
                        projectAlert('error', '{{ __('projects.save_error_generic') }}', '', 2500);
                    }
                });
            });

            checklistTab?.querySelectorAll('.checklist-comment').forEach((textarea) => {
                textarea.addEventListener('blur', async () => {
                    const stepId = Number(textarea.dataset.stepId || 0);
                    if (!stepId) return;
                    const card = checklistTab.querySelector(`.checklist-step-card[data-step-id="${stepId}"]`);
                    if (!card) return;
                    const status = card.classList.contains('checklist-item-done') ? 'done' : 'pending';
                    try {
                        const step = await saveChecklistStep(stepId, status, textarea.value || '');
                        applyChecklistUi(card, step.result_status || status);
                    } catch (e) {
                        console.error(e);
                        projectAlert('error', '{{ __('projects.save_error_generic') }}', '', 2500);
                    }
                });
            });
            window.deleteProjectFileFromShow = async function(projectId, fileIndex) {
                if (!confirm('{{ __('objects.delete_file_confirm') }}')) return;
                try {
                    const r = await fetch(`/projects/${projectId}/files/${fileIndex}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        }
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                        projectAlert('error', data.message || '{{ __('projects.delete_error_generic') }}', '', 3200);
                        return;
                    }
                    location.reload();
                } catch (e) {
                    console.error(e);
                    projectAlert('error', '{{ __('projects.delete_error_generic') }}', '', 3200);
                }
            };
            const toggleEdit = (enabled) => {
                form.querySelectorAll('input, select, textarea').forEach((el) => {
                    if (el.type === 'hidden') return;
                    el.disabled = !enabled;
                });
                document.querySelectorAll('.edit-only-control').forEach((el) => {
                    el.classList.toggle('hidden', !enabled);
                });
                btnEdit.classList.toggle('hidden', enabled);
                btnSave.classList.toggle('hidden', !enabled);
                btnCancel.classList.toggle('hidden', !enabled);
            };
            btnEdit?.addEventListener('click', () => toggleEdit(true));
            btnCancel?.addEventListener('click', () => location.reload());
        })();
    </script>
@endsection
