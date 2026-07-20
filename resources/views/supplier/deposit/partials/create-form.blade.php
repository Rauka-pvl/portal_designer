<form method="POST" action="{{ route('supplier.deposit.create') }}" id="deposit-create-form" class="space-y-4">
    @csrf
    <input type="hidden" name="payment_method" value="kaspi">

    <label class="flex items-start gap-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] cursor-pointer">
        <input type="checkbox" name="terms_accepted" value="1" required
            class="mt-1 rounded border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]"
            {{ old('terms_accepted') ? 'checked' : '' }}>
        <span>{{ __('supplier_deposit.terms_checkbox') }}</span>
    </label>
    @error('terms_accepted')
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    <button type="submit" id="deposit-create-btn"
        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3.5 text-sm font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-60">
        <span id="deposit-create-label">{{ __('supplier_deposit.pay_button', ['amount' => $amountLabel]) }}</span>
    </button>
</form>

<script>
(function () {
    const form = document.getElementById('deposit-create-form');
    if (!form || form.dataset.bound) return;
    form.dataset.bound = '1';
    form.addEventListener('submit', function () {
        const btn = document.getElementById('deposit-create-btn');
        const label = document.getElementById('deposit-create-label');
        if (btn) btn.disabled = true;
        if (label) label.textContent = @json(__('supplier_deposit.creating'));
    });
})();
</script>
