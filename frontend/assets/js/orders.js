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
            button.closest('.af-note-form')?.classList.toggle('is-open');
        });
    });

    const sortSelect = document.querySelector('#food_sort');

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            sortSelect.form?.submit();
        });
    }

    const cartHeading = document.querySelector('.af-cart-card header h2');
    const headerCartBadge = document.querySelector('.af-header-action[aria-label="Cart"] em');

    if (cartHeading && headerCartBadge) {
        const match = cartHeading.textContent.match(/\((\d+)\)/);

        if (match) {
            headerCartBadge.textContent = match[1];
        }
    }
})();
