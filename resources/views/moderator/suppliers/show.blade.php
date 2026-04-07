@extends('layouts.dashboard')

@section('title', $supplier->name ?? __('suppliers.supplier'))

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .dark .panel { background: #161615; border-color: #3E3E3A; }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #94a3b8;
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
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->name ?? '-' }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                {{ __('moderation.designer') }}: {{ $supplier->user?->name ?? '-' }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('moderator.index') }}" class="btn">{{ __('suppliers.close') }}</a>
        </div>
    </div>

    <div class="panel space-y-6">
        @php
            $status = (string) ($supplier->moderation_status ?? 'pending');
            $sphere = $supplier->sphere;
            $sphereTranslation = $sphere ? __('supplier_spheres.' . $sphere) : null;
            $sphereDisplay = $sphere
                ? ($sphereTranslation !== 'supplier_spheres.' . $sphere ? $sphereTranslation : $sphere)
                : null;
            $brands = is_array($supplier->brands) ? $supplier->brands : [];
            $citiesPresence = is_array($supplier->cities_presence) ? $supplier->cities_presence : [];
        @endphp

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if($status === 'approved') bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                    @elseif($status === 'rejected') bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300
                    @else bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200
                    @endif">
                    {{ __('moderation.' . $status) }}
                </span>

                @if(!empty($supplier->moderation_comment))
                    <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                        {{ $supplier->moderation_comment }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</div>
                <input disabled value="{{ $supplier->name ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</div>
                <input disabled value="{{ $supplier->city ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</div>
                <input disabled value="{{ $supplier->address ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</div>
                <input disabled value="{{ $supplier->phone ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Email</div>
                <input disabled value="{{ $supplier->email ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Telegram</div>
                <input disabled value="{{ $supplier->telegram ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">WhatsApp</div>
                <input disabled value="{{ $supplier->whatsapp ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</div>
                <input disabled value="{{ $supplier->website ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere') }}</div>
                <input disabled value="{{ $sphereDisplay ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.work_terms') }}</div>
                @php
                    $workLabel = null;
                    if ($supplier->work_terms_type && $supplier->work_terms_value) {
                        $workLabel = $supplier->work_terms_type === 'percent'
                            ? '%' . ': ' . $supplier->work_terms_value
                            : __('suppliers.work_terms_amount') . ': ' . $supplier->work_terms_value;
                    }
                @endphp
                <input disabled value="{{ $workLabel ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.brands') }}</div>
                <textarea disabled rows="2" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">@if($brands){{ implode(', ', $brands) }}@endif</textarea>
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.cities_presence') }}</div>
                <textarea disabled rows="2" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">@if($citiesPresence){{ implode(', ', $citiesPresence) }}@endif</textarea>
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</div>
                <textarea disabled rows="3" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $supplier->comment ?? '' }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.org_form') }}</div>
                <input disabled value="{{ $supplier->org_form === 'ip' ? __('suppliers.org_ip') : __('suppliers.org_ooo') }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.inn') }}</div>
                <input disabled value="{{ $supplier->inn ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.kpp') }}</div>
                <input disabled value="{{ $supplier->kpp ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.ogrn') }}</div>
                <input disabled value="{{ $supplier->ogrn ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.okpo') }}</div>
                <input disabled value="{{ $supplier->okpo ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.legal_address') }}</div>
                <input disabled value="{{ $supplier->legal_address ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.actual_address') }}</div>
                <input disabled value="{{ $supplier->actual_address ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.director') }}</div>
                <input disabled value="{{ $supplier->director ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.accountant') }}</div>
                <input disabled value="{{ $supplier->accountant ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.bik') }}</div>
                <input disabled value="{{ $supplier->bik ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.bank') }}</div>
                <input disabled value="{{ $supplier->bank ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.checking_account') }}</div>
                <input disabled value="{{ $supplier->checking_account ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div>
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.corr_account') }}</div>
                <input disabled value="{{ $supplier->corr_account ?? '' }}" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            </div>
            <div class="md:col-span-2">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</div>
                <textarea disabled rows="3" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $supplier->comment_bank ?? '' }}</textarea>
            </div>
        </div>

        <hr class="my-4 border-[#94a3b8] dark:border-[#3E3E3A]">

        <form id="moderation-form" method="POST" action="{{ route('moderator.suppliers.decision', $supplier->id) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('moderation.decision') }}</div>
                    <select name="decision" disabled class="editable-result w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        <option value="approved" @selected($supplier->moderation_status === 'approved')>
                            {{ __('moderation.approved') }}
                        </option>
                        <option value="rejected" @selected($supplier->moderation_status === 'rejected')>
                            {{ __('moderation.rejected') }}
                        </option>
                    </select>
                </div>

                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('moderation.comment') }}</div>
                    <textarea name="comment" disabled rows="4" class="editable-result w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ old('comment', $supplier->moderation_comment ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button id="btn-edit" type="button" class="btn">{{ __('suppliers.edit') }}</button>
                <button id="btn-save" type="submit" class="btn hidden">{{ __('suppliers.save') }}</button>
                <button id="btn-cancel" type="button" class="btn hidden">{{ __('suppliers.cancel') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const form = document.getElementById('moderation-form');
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');

            const toggleEdit = (enabled) => {
                form.querySelectorAll('.editable-result').forEach((el) => {
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

