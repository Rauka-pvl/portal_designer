@extends('layouts.dashboard')

@section('title', __('projects.stage_checklist'))

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .dark .panel {
            background: #161615;
            border-color: #3E3E3A;
        }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .dark .btn {
            background: #0a0a0a;
            border-color: #3E3E3A;
            color: #A1A09A;
        }
    </style>
@endpush

@section('content')
    @php
        $stageLabel = $stage_type ? __('projects.stage_' . $stage_type) : '-';
    @endphp

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
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
            <a href="{{ route('projects.index') }}" class="btn">{{ __('projects.close') }}</a>
        </div>
    </div>

    <div class="panel">
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
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.step_title_placeholder') }}</div>
                    <input type="text" value="{{ $step->title ?? '-' }}" disabled
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.deadline') }}</div>
                    <input type="date" value="{{ $step->deadline ?? '' }}" disabled
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.responsible') }}</div>
                    <input type="text" value="{{ $responsible?->name ?? '-' }}" disabled
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>

                <div class="md:col-span-2">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.links') }}</div>
                    <input type="url" value="{{ $step->link ?? '' }}" disabled
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('projects.step_result_status') }}</div>
                    <select name="result_status" disabled
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] editable-result">
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
                        class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] resize-none editable-result">{{ $step->result_comment ?? '' }}</textarea>
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

