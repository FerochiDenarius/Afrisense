// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Find the page elements controlled by this script.
    document.querySelectorAll('[data-favorite]').forEach((button) => {
        // Bind the UI event handler for this interactive control.
        button.addEventListener('click', () => {
            button.classList.toggle('is-active');
            const icon = button.querySelector('i');

            // Run this branch only when the required UI state is present.
            if (icon) {
                icon.className = button.classList.contains('is-active') ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        });
    });

    // Find the page elements controlled by this script.
    document.querySelectorAll('[data-note-toggle]').forEach((button) => {
        // Bind the UI event handler for this interactive control.
        button.addEventListener('click', () => {
            button.closest('.af-public-cart-note')?.classList.toggle('is-open');
        });
    });

    // Find the page elements controlled by this script.
    document.querySelectorAll('[data-add-to-cart]').forEach((button) => {
        // Bind the UI event handler for this interactive control.
        button.addEventListener('pointerdown', () => {
            button.classList.remove('is-adding');
            window.requestAnimationFrame(() => {
                button.classList.add('is-adding');
            });
        });

        // Bind the UI event handler for this interactive control.
        button.addEventListener('animationend', () => {
            button.classList.remove('is-adding');
        });
    });

    // Find the page elements controlled by this script.
    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        // Bind the UI event handler for this interactive control.
        select.addEventListener('change', () => {
            select.form?.submit();
        });
    });
})();
