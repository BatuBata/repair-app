let menu, trigger;
const closeMenu = (restore = false) => {
    if (!menu) return;
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (restore) trigger.focus();
    menu = trigger = null;
};
document.addEventListener('click', (event) => {
    const button = event.target.closest('.act-btn[aria-haspopup="menu"]');
    if (button) {
        const target = document.getElementById(button.getAttribute('aria-controls'));
        if (menu === target) return closeMenu();
        closeMenu();
        if (!target) return;
        document.body.appendChild(target);
        target.hidden = false;
        const rect = button.getBoundingClientRect();
        const top = rect.bottom + 4 + target.offsetHeight > innerHeight - 8 ? Math.max(8, rect.top - target.offsetHeight - 4) : rect.bottom + 4;
        target.style.top = `${top}px`;
        target.style.left = `${Math.max(8, Math.min(rect.left, innerWidth - target.offsetWidth - 8))}px`;
        button.setAttribute('aria-expanded', 'true');
        menu = target;
        trigger = button;
        menu.querySelector('[role="menuitem"]')?.focus({ preventScroll: true });
    } else if (menu && !event.target.closest('.menu')) closeMenu();
    else if (menu && event.target.closest('[role="menuitem"]')) closeMenu();

    const open = event.target.closest('[data-open]');
    if (open) document.getElementById(open.dataset.open)?.showModal();
    const close = event.target.closest('[data-close]');
    if (close) close.closest('dialog')?.close();
    const notice = event.target.closest('[data-toast]');
    if (notice) {
        const toast = document.querySelector('.toast');
        toast.textContent = notice.dataset.toast;
        toast.classList.add('show');
        clearTimeout(window.repairToastTimer);
        window.repairToastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    }
});
document.addEventListener('keydown', (event) => {
    if (!menu) return;
    const items = [...menu.querySelectorAll('[role="menuitem"]')];
    const index = items.indexOf(document.activeElement);
    if (event.key === 'Escape') { event.preventDefault(); closeMenu(true); }
    else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        items[(index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length]?.focus();
    } else if (event.key === 'Tab') closeMenu();
});
addEventListener('scroll', () => closeMenu(), true);
addEventListener('resize', () => closeMenu());
document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-money]')) return;
    const digits = event.target.value.replace(/\D/g, '');
    event.target.value = digits.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const raw = document.getElementById(event.target.dataset.money);
    if (raw) raw.value = digits.replace(/^0+(?=\d)/, '');
});
document.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-filter-tabs] [role="tab"]');
    if (!tab) return;
    const group = tab.closest('[data-filter-tabs]');
    group.querySelectorAll('[role="tab"]').forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
    document.getElementById(group.dataset.filterTabs)?.querySelectorAll('tbody tr').forEach((row) => {
        row.hidden = tab.dataset.value !== 'semua' && row.dataset.status !== tab.dataset.value;
    });
});
