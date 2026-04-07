@extends('layouts.dashboard')

@section('title', __('projects.stage_checklist'))

@push('styles')
    <style>
        .panel {
            border-radius: 12px;
            padding: 1.25rem;
        }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #94a3b8;
            background: #ffffff;
            color: #4b5563;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .dark .btn {
            background: #0a0a0a;
            border: 1px solid #525252;
            color: #A1A09A;
        }

        /* Рамка видна и в режиме просмотра (disabled), без «затухания» как при вводе */
        #checklist-step-details-form input:disabled,
        #checklist-step-details-form select:disabled,
        #checklist-step-details-form textarea:disabled,
        #checklist-step-details-form input:enabled,
        #checklist-step-details-form select:enabled,
        #checklist-step-details-form textarea:enabled {
            opacity: 1;
        }

        #checklist-step-details-form input,
        #checklist-step-details-form select,
        #checklist-step-details-form textarea {
            border-width: 1px;
            border-style: solid;
            border-color: #94a3b8;
        }

        .dark #checklist-step-details-form input,
        .dark #checklist-step-details-form select,
        .dark #checklist-step-details-form textarea {
            border-color: #525252;
        }

        #checklist-step-details-form input:focus,
        #checklist-step-details-form select:focus,
        #checklist-step-details-form textarea:focus {
            outline: none;
            box-shadow: 0 0 0 1px #94a3b8;
        }

        .dark #checklist-step-details-form input:focus,
        .dark #checklist-step-details-form select:focus,
        .dark #checklist-step-details-form textarea:focus {
            box-shadow: 0 0 0 1px #a3a3a3;
        }
    </style>
@endpush

@section('content')
    @php
        $stageLabel = $stage_type ? __('projects.stage_' . $stage_type) : '-';
    @endphp

    <div
        class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-6 border-b border-solid border-[#94a3b8] dark:border-[#525252]">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                {{ $project?->name ?? '-' }}
            </h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                {{ __('projects.stage_checklist') }}: {{ $stageLabel }}
            </p>
        </div>

        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">{{ __('projects.edit') }}</button>
            <a href="{{ route('dashboard') }}" class="btn">{{ __('projects.close') }}</a>
        </div>
    </div>

    <div
        class="panel bg-[#f1f5f9] dark:bg-[#161615] border border-solid border-[#94a3b8] dark:border-[#525252] shadow-md shadow-gray-500/20 dark:shadow-none">
        <form id="checklist-step-details-form" method="POST" action="{{ route('checklist-steps.update', $step->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.project') }}</div>
                    <a href="{{ $project ? route('projects.show', $project->id) : '#' }}"
                        class="inline-flex items-center gap-2 text-[#f59e0b] dark:text-[#f59e0b] hover:underline">
                        {{ $project?->name ?? '-' }}
                    </a>
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.stage') }}</div>
                    <input type="text" value="{{ $stageLabel }}" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.step_title_placeholder') }}</div>
                    <input type="text" value="{{ $step->title ?? '-' }}" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.deadline') }}</div>
                    <input type="date" value="{{ $step->deadline ?? '' }}" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.responsible') }}</div>
                    <input type="text" value="{{ $responsible?->name ?? '-' }}" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div class="md:col-span-2">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.links') }}</div>
                    <input type="url" value="{{ $step->link ?? '' }}" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.step_result_status') }}</div>
                    <select name="result_status" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] editable-result">
                        <option value="pending" @selected(($step->result_status ?? 'pending') === 'pending')>
                            {{ __('projects.step_result_not_done') }}
                        </option>
                        <option value="done" @selected(($step->result_status ?? 'pending') === 'done')>
                            {{ __('projects.step_result_done') }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.step_result_comment') }}</div>
                    <textarea name="result_comment" rows="4" disabled
                        class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] resize-none editable-result">{{ $step->result_comment ?? '' }}</textarea>
                </div>
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
            const form = document.getElementById('checklist-step-details-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');

            const toggleEdit = (enabled) => {
                form.querySelectorAll('.editable-result').forEach((el) => {
                    el.disabled = !enabled;
                });
                // Скрываем/показываем кнопки редактирования
                btnEdit.classList.toggle('hidden', enabled);
                btnSave.classList.toggle('hidden', !enabled);
                btnCancel.classList.toggle('hidden', !enabled);
            };

            btnEdit?.addEventListener('click', function() {
                toggleEdit(true);
            });

            btnCancel?.addEventListener('click', function() {
                window.location.reload();
            });
        })();
    </script>
@endsection

