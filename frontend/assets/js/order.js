(function () {
    document.querySelectorAll('[data-favorite]').forEach((button) => {
        button.addEventListener('click', () => {
            button.classList.toggle('is-active');
            const icon = button.querySelector('i');

            if (icon) {
                icon.className = button.classList.contains('is-active') ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        });
    });

    document.querySelectorAll('[data-note-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.af-public-cart-note')?.classList.toggle('is-open');
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => {
            select.form?.submit();
        });
    });
})();
