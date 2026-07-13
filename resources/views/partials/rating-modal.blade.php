{{-- Модалка оценки. Требует переменную $reviewStoreUrl. --}}
<div id="rating-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onmousedown="if(event.target === this) closeRatingModal()">
    <div class="bg-white dark:bg-[#161615] rounded-xl max-w-md w-full mx-auto overflow-hidden border border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#7c8799] dark:border-[#3E3E3A]">
            <div>
                <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('reviews.modal_title') }}</h2>
                <p id="rating-modal-subtitle" class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5"></p>
            </div>
            <button type="button" onclick="closeRatingModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="rating-form" method="POST" action="{{ $reviewStoreUrl }}" class="px-6 py-5">
            @csrf
            <input type="hidden" name="order_id" id="rating-order-id" value="">
            <input type="hidden" name="rating" id="rating-value" value="0">

            <div class="flex flex-col items-center gap-3">
                <div id="rating-stars" class="flex items-center gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" data-star="{{ $i }}"
                            class="rating-star text-[#cbd5e1] dark:text-[#3E3E3A] transition-transform hover:scale-110 focus:outline-none">
                            <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/>
                            </svg>
                        </button>
                    @endfor
                </div>
                <p id="rating-error" class="text-xs text-red-500 hidden">{{ __('reviews.rating_required') }}</p>
            </div>

            <div class="mt-5">
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('reviews.comment_label') }}</label>
                <textarea name="comment" rows="4" maxlength="2000"
                    class="w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b] resize-none"
                    placeholder="{{ __('reviews.comment_placeholder') }}"></textarea>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" onclick="closeRatingModal()" class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors">
                    {{ __('reviews.cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg border border-[#f59e0b] bg-[#f59e0b] text-[#111827] text-sm font-semibold hover:bg-[#d97706] hover:border-[#d97706] transition-colors">
                    {{ __('reviews.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('rating-modal');
        if (!modal) return;
        const stars = Array.from(modal.querySelectorAll('.rating-star'));
        const valueInput = document.getElementById('rating-value');
        const errorEl = document.getElementById('rating-error');
        const subtitleEl = document.getElementById('rating-modal-subtitle');
        const orderInput = document.getElementById('rating-order-id');
        const form = document.getElementById('rating-form');

        const activeClass = 'text-[#f59e0b]';
        const idleLight = 'text-[#cbd5e1]';
        const idleDark = 'dark:text-[#3E3E3A]';

        function paint(count) {
            stars.forEach((star) => {
                const val = parseInt(star.dataset.star, 10);
                if (val <= count) {
                    star.classList.add(activeClass);
                    star.classList.remove(idleLight, idleDark);
                } else {
                    star.classList.remove(activeClass);
                    star.classList.add(idleLight, idleDark);
                }
            });
        }

        stars.forEach((star) => {
            const val = parseInt(star.dataset.star, 10);
            star.addEventListener('mouseenter', () => paint(val));
            star.addEventListener('mouseleave', () => paint(parseInt(valueInput.value, 10) || 0));
            star.addEventListener('click', () => {
                valueInput.value = String(val);
                errorEl.classList.add('hidden');
                paint(val);
            });
        });

        window.openRatingModal = function (orderId, subtitle) {
            orderInput.value = orderId || '';
            subtitleEl.textContent = subtitle || '';
            valueInput.value = '0';
            errorEl.classList.add('hidden');
            form.reset();
            orderInput.value = orderId || '';
            valueInput.value = '0';
            paint(0);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeRatingModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        form.addEventListener('submit', function (e) {
            if ((parseInt(valueInput.value, 10) || 0) < 1) {
                e.preventDefault();
                errorEl.classList.remove('hidden');
            }
        });
    })();
</script>
