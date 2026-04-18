// SheWork$ — main.js

document.addEventListener('DOMContentLoaded', function () {

    // ── Tab switching on company profile page ─────────────────
    const tabBtns   = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const panel = document.getElementById('tab-' + target);
            if (panel) panel.classList.add('active');
        });
    });

    // ── Switch to pay tab if URL hash is #pay ────────────────
    if (window.location.hash === '#pay') {
        const payBtn = document.querySelector('[data-tab="pay"]');
        if (payBtn) payBtn.click();
    }

    // ── Animate gap bars ──────────────────────────────────────
    const bars = document.querySelectorAll('.gap-bar-fill');
    bars.forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = target; }, 300);
    });

    // ── Star rating hover fix (CSS handles most, this enhances) ─
    document.querySelectorAll('.star-rating').forEach(group => {
        const labels = group.querySelectorAll('label');
        labels.forEach((lbl, i) => {
            lbl.addEventListener('mouseenter', () => {
                labels.forEach((l, j) => {
                    l.style.color = j >= i ? 'var(--gold)' : '#DDD';
                });
            });
            lbl.addEventListener('mouseleave', () => {
                labels.forEach(l => l.style.color = '');
            });
        });
    });

    // ── Navbar search autocomplete hint ───────────────────────
    const navInput = document.querySelector('.nav-search-input');
    if (navInput) {
        navInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                navInput.closest('form').submit();
            }
        });
    }

});
