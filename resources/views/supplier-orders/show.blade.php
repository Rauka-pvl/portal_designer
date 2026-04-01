@extends('layouts.dashboard')

@section('title', __('supplier-orders.supplier_order'))

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }
        .dark .panel { background: #161615; border-color: #3E3E3A; }
        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
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
        $o = $orderData;
        $statusMap = [
            'order_created' => __('supplier-orders.status_order_created'),
            'order_sent' => __('supplier-orders.status_order_sent'),
            'order_confirmed' => __('supplier-orders.status_order_confirmed'),
            'advance_payment' => __('supplier-orders.status_advance_payment'),
            'full_payment' => __('supplier-orders.status_full_payment'),
            'delivery_completed' => __('supplier-orders.status_delivery_completed'),
        ];
        $categoryName = ($categoryOptions[$o['category']] ?? null) ?: ($o['category'] ?: '-');
        $roomName = ($roomOptions[$o['room']] ?? null) ?: ($o['room'] ?: '-');
    @endphp

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.supplier_order') }} #{{ $o['id'] ?? '-' }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('supplier-orders.project') }}: {{ $o['project_name'] ?? '-' }}</p>
        </div>
        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">{{ __('supplier-orders.edit') }}</button>
            <form method="POST" action="{{ route('supplier-orders.destroy', $o['id']) }}" onsubmit="return confirm('{{ __('supplier-orders.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">{{ __('supplier-orders.delete') }}</button>
            </form>
            <a href="{{ route('supplier-orders.index') }}" class="btn">{{ __('supplier-orders.close') }}</a>
        </div>
    </div>

    <div class="panel">
        <form id="supplier-order-details-form" method="POST" action="{{ route('supplier-orders.update', $o['id']) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.project') }}</div>
                    <select name="project_id" required disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        @foreach (($projects ?? []) as $project)
                            <option value="{{ $project->id }}" @selected((int) ($o['project_id'] ?? 0) === (int) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.supplier') }}</div>
                    <select name="supplier_id" required disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        @foreach (($suppliers ?? []) as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) ($o['supplier_id'] ?? 0) === (int) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.status') }}</div>
                    <select name="status" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                        <option value="order_created" @selected(($o['status'] ?? '') === 'order_created')>{{ __('supplier-orders.status_order_created') }}</option>
                        <option value="order_sent" @selected(($o['status'] ?? '') === 'order_sent')>{{ __('supplier-orders.status_order_sent') }}</option>
                        <option value="order_confirmed" @selected(($o['status'] ?? '') === 'order_confirmed')>{{ __('supplier-orders.status_order_confirmed') }}</option>
                        <option value="advance_payment" @selected(($o['status'] ?? '') === 'advance_payment')>{{ __('supplier-orders.status_advance_payment') }}</option>
                        <option value="full_payment" @selected(($o['status'] ?? '') === 'full_payment')>{{ __('supplier-orders.status_full_payment') }}</option>
                        <option value="delivery_completed" @selected(($o['status'] ?? '') === 'delivery_completed')>{{ __('supplier-orders.status_delivery_completed') }}</option>
                    </select>
                </div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.amount') }}</div><input type="number" name="summa" value="{{ $o['summa'] ?? 0 }}" required min="0" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.category') }}</div><select name="category" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"><option value=""></option>@foreach (($categoryOptions ?? []) as $key => $name)<option value="{{ $key }}" @selected(($o['category'] ?? '') === $key)>{{ $name }}</option>@endforeach</select></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.room') }}</div><select name="room" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"><option value=""></option>@foreach (($roomOptions ?? []) as $key => $name)<option value="{{ $key }}" @selected(($o['room'] ?? '') === $key)>{{ $name }}</option>@endforeach</select></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.mark_on_drawing') }}</div><input name="mark" value="{{ $o['mark'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.planned_delivery_date') }}</div><input type="date" name="date_planned" value="{{ $o['date_planned'] ?? '' }}" required disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.actual_delivery_date') }}</div><input type="date" name="date_actual" value="{{ $o['date_actual'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.advance_date') }}</div><input type="date" name="prepayment_date" value="{{ $o['prepayment_date'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.balance_date') }}</div><input type="date" name="payment_date" value="{{ $o['payment_date'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.advance_amount') }}</div><input type="number" min="0" name="prepayment_amount" value="{{ $o['prepayment_amount'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.balance_amount') }}</div><input type="number" min="0" name="payment_amount" value="{{ $o['payment_amount'] ?? '' }}" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]"></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.product_service') }}</div><textarea name="comment" rows="3" disabled class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $o['product_service'] ?? '' }}</textarea></div>

                @foreach (($o['links'] ?? []) as $link)
                    <input type="hidden" name="links[]" value="{{ $link }}">
                @endforeach
                @foreach (($o['files'] ?? []) as $filePath)
                    <input type="hidden" name="existing_files[]" value="{{ $filePath }}">
                @endforeach
            </div>
            <div class="mt-6 flex gap-3">
                <button id="btn-save" type="submit" class="btn hidden">{{ __('supplier-orders.save') }}</button>
                <button id="btn-cancel" type="button" class="btn hidden">{{ __('supplier-orders.cancel') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const form = document.getElementById('supplier-order-details-form');
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
