// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Lightweight page behavior for customer/guest ordering. Server-side POSTs
    // still own cart changes; this script only improves the interface.
    document.querySelectorAll('[data-favorite]').forEach((button) => {
        // Bind the UI event handler for this interactive control.
        button.addEventListener('click', () => {
            // Favorites are currently a visual toggle for menu browsing.
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
            button.closest('.af-note-form')?.classList.toggle('is-open');
        });
    });

    const sortSelect = document.querySelector('#food_sort');

    // Run this branch only when the required UI state is present.
    if (sortSelect) {
        // Bind the UI event handler for this interactive control.
        sortSelect.addEventListener('change', () => {
            sortSelect.form?.submit();
        });
    }

    const cartHeading = document.querySelector('.af-cart-card header h2');
    const headerCartBadge = document.querySelector('.af-header-action[aria-label="Cart"] em');

    // Run this branch only when the required UI state is present.
    if (cartHeading && headerCartBadge) {
        // Sync the fixed header cart badge with the server-rendered cart count.
        const match = cartHeading.textContent.match(/\((\d+)\)/);

        // Run this branch only when the required UI state is present.
        if (match) {
            headerCartBadge.textContent = match[1];
        }
    }
})();
