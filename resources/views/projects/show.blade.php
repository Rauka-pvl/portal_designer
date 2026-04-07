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
    </style>
@endpush

@section('content')
    @php
        $p = $projectData;
        $statusMap = [
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
            @php
                $modStatus = (string) ($p['moderation_status'] ?? '');
            @endphp
            @if($modStatus !== '')
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($modStatus === 'approved') bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                        @elseif($modStatus === 'rejected') bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300
                        @else bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200
                        @endif">
                        {{ __('moderation.' . $modStatus) }}
                    </span>
                    @if(!empty($p['moderation_comment']))
                        <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                            {{ $p['moderation_comment'] }}
                        </span>
                    @endif
                </div>
            @endif
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

    <div class="panel">
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
                    @endforeach
                @endforeach
            </div>
            <div class="mt-6 flex gap-3">
                <button id="btn-save" type="submit" class="btn hidden">{{ __('projects.save') }}</button>
                <button id="btn-cancel" type="button" class="btn hidden">{{ __('projects.cancel') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const form = document.getElementById('project-details-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');
            const toggleEdit = (enabled) => {
                form.querySelectorAll('input, select, textarea').forEach((el) => {
                    if (el.type === 'hidden') return;
                    el.disabled = !enabled;
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
