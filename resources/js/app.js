import './bootstrap';

// Простая защита от двойного клика
function submitButtons(form) {
    const inside = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    if (!form.id) return inside;
    const outside = document.querySelectorAll(
        `button[type="submit"][form="${form.id}"], input[type="submit"][form="${form.id}"]`
    );
    return [...inside, ...outside];
}

window.lockSubmit = function (form) {
    if (!form || form.dataset.busy === '1') return false;
    form.dataset.busy = '1';
    submitButtons(form).forEach((btn) => { btn.disabled = true; });
    return true;
};

window.unlockSubmit = function (form) {
    if (!form) return;
    form.dataset.busy = '0';
    submitButtons(form).forEach((btn) => { btn.disabled = false; });
};

// Обычные POST-формы (без AJAX): блокируем кнопку после первого submit
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (e.defaultPrevented) return;
    if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return;
    if (form.dataset.ajax === '1') return;

    if (form.dataset.busy === '1') {
        e.preventDefault();
        return;
    }
    window.lockSubmit(form);
});
