@extends('layouts.dashboard')

@section('title', $supplierData['name'] ?? __('suppliers.supplier'))

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
        $s = $supplierData;
    @endphp

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['name'] ?? '-' }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('suppliers.supplier') }} #{{ $s['id'] ?? '-' }}</p>
            @php
                $modStatus = (string) ($s['moderation_status'] ?? '');
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

                    @if(!empty($s['moderation_comment']))
                        <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                            {{ $s['moderation_comment'] }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">{{ __('suppliers.edit') }}</button>
            <form method="POST" action="{{ route('suppliers.destroy', $s['id']) }}" onsubmit="return confirm('{{ __('suppliers.delete') }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('suppliers.delete') }}</button>
            </form>
            <a href="{{ route('suppliers.index') }}" class="btn">{{ __('suppliers.close') }}</a>
        </div>
    </div>

    <div class="panel">
        <form id="supplier-details-form" method="POST" action="{{ route('suppliers.update', $s['id']) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</div>
                    <input name="name" value="{{ $s['name'] ?? '' }}" required disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</div>
                    <input name="phone" value="{{ $s['phone'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                </div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Email</div><input name="email" value="{{ $s['email'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Telegram</div><input name="telegram" value="{{ $s['telegram'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">WhatsApp</div><input name="whatsapp" value="{{ $s['whatsapp'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</div><input name="website" value="{{ $s['website'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</div><input name="city" value="{{ $s['city'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</div><input name="address" value="{{ $s['address'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere') }}</div>
                    <select name="sphere" disabled class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        <option value=""></option>
                        @foreach (($sphereOptions ?? []) as $key => $name)
                            <option value="{{ $key }}" @selected(($s['sphere'] ?? '') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</div><textarea name="comment_main" disabled rows="3" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $s['comment'] ?? '' }}</textarea></div>

                @foreach (($s['brands'] ?? []) as $brand)
                    <input type="hidden" name="brands[]" value="{{ $brand }}">
                @endforeach
                @foreach (($s['cities_presence'] ?? []) as $cityPresence)
                    <input type="hidden" name="cities_presence[]" value="{{ $cityPresence }}">
                @endforeach
                <input type="hidden" name="work_terms_type" value="{{ $s['work_terms_type'] ?? '' }}">
                <input type="hidden" name="work_terms_value" value="{{ $s['work_terms_value'] ?? '' }}">
                <input type="hidden" name="org_form" value="{{ $s['org_form'] ?? 'ooo' }}">
                <input type="hidden" name="inn" value="{{ $s['inn'] ?? '' }}">
                <input type="hidden" name="kpp" value="{{ $s['kpp'] ?? '' }}">
                <input type="hidden" name="ogrn" value="{{ $s['ogrn'] ?? '' }}">
                <input type="hidden" name="okpo" value="{{ $s['okpo'] ?? '' }}">
                <input type="hidden" name="legal_address" value="{{ $s['legal_address'] ?? '' }}">
                <input type="hidden" name="actual_address" value="{{ $s['actual_address'] ?? '' }}">
                <input type="hidden" name="director" value="{{ $s['director'] ?? '' }}">
                <input type="hidden" name="accountant" value="{{ $s['accountant'] ?? '' }}">
                <input type="hidden" name="bik" value="{{ $s['bik'] ?? '' }}">
                <input type="hidden" name="bank" value="{{ $s['bank'] ?? '' }}">
                <input type="hidden" name="checking_account" value="{{ $s['checking_account'] ?? '' }}">
                <input type="hidden" name="corr_account" value="{{ $s['corr_account'] ?? '' }}">
                <input type="hidden" name="comment_bank" value="{{ $s['comment_bank'] ?? '' }}">
            </div>
            <div class="mt-6 flex gap-3">
                <button id="btn-save" type="submit" class="btn hidden">{{ __('suppliers.save') }}</button>
                <button id="btn-cancel" type="button" class="btn hidden">{{ __('suppliers.cancel') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const form = document.getElementById('supplier-details-form');
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
