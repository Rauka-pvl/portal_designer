@extends('layouts.dashboard')

@section('title', 'Поиск поставщика')

@section('content')
    <div class="max-w-3xl">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">Поиск поставщика по ИИН</h1>
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">Найдите профиль поставщика и отправьте приглашение.</p>

        <div class="mt-5 flex gap-2">
            <input id="inn" type="text" placeholder="Введите ИИН"
                   class="flex-1 px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            <button id="search-btn" class="px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b]">Найти</button>
        </div>

        <div id="result" class="mt-5 hidden rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]" id="supplier-name"></div>
            <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1" id="supplier-inn"></div>
            <div class="text-sm text-[#64748b] dark:text-[#A1A09A]" id="supplier-city"></div>
            <button id="invite-btn" class="mt-3 px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b]">Отправить приглашение</button>
        </div>
    </div>

    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        let supplierId = null;
        document.getElementById('search-btn').addEventListener('click', () => {
            const inn = document.getElementById('inn').value.trim();
            if (!inn) return;
            fetch(`{{ route('suppliers.search_by_inn') }}?inn=${encodeURIComponent(inn)}`, {headers: {'Accept': 'application/json'}})
                .then(async r => ({ok: r.ok, body: await r.json()}))
                .then(({ok, body}) => {
                    if (!ok || !body.success) {
                        projectAlert('error', body.message || 'Поставщик не найден', '', 3000);
                        return;
                    }
                    supplierId = body.supplier.id;
                    document.getElementById('result').classList.remove('hidden');
                    document.getElementById('supplier-name').textContent = body.supplier.name || '-';
                    document.getElementById('supplier-inn').textContent = `ИИН: ${body.supplier.inn || '-'}`;
                    document.getElementById('supplier-city').textContent = `Город: ${body.supplier.city || '-'}`;
                });
        });

        document.getElementById('invite-btn').addEventListener('click', () => {
            if (!supplierId) return;
            fetch(`{{ url('/suppliers') }}/${supplierId}/invite`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
            })
                .then(async r => ({ok: r.ok, body: await r.json()}))
                .then(({ok, body}) => {
                    if (!ok || !body.success) {
                        projectAlert('error', body.message || 'Не удалось отправить приглашение', '', 3000);
                        return;
                    }
                    projectAlert('success', body.message || 'Приглашение отправлено', '', 2500);
                });
        });
    </script>
@endsection
