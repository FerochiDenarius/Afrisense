// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the closeModal helper for this browser module.
    function closeModal(modal) {
        // Run this branch only when the required UI state is present.
        if (!modal) {
            return;
        }

        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
    }

    // Defines the openModal helper for this browser module.
    function openModal(modal, options) {
        // Run this branch only when the required UI state is present.
        if (!modal) {
            return;
        }

        // Run this branch only when the required UI state is present.
        if (options && options.message) {
            var message = modal.querySelector("[data-modal-message]");
            // Run this branch only when the required UI state is present.
            if (message) {
                message.textContent = options.message;
            }
        }

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
    }

    // Defines the initModals helper for this browser module.
    function initModals() {
        // Bind the UI event handler for this interactive control.
        document.addEventListener("click", function (event) {
            var opener = event.target.closest("[data-modal-open]");
            var closer = event.target.closest("[data-modal-close]");

            // Run this branch only when the required UI state is present.
            if (opener) {
                event.preventDefault();
                openModal(document.querySelector(opener.getAttribute("data-modal-open")));
            }

            // Run this branch only when the required UI state is present.
            if (closer) {
                event.preventDefault();
                closeModal(closer.closest("[data-modal]"));
            }
        });

        // Bind the UI event handler for this interactive control.
        document.addEventListener("keydown", function (event) {
            // Run this branch only when the required UI state is present.
            if (event.key === "Escape") {
                // Find the page elements controlled by this script.
                document.querySelectorAll("[data-modal].is-open").forEach(closeModal);
            }
        });
    }

    window.AfriSenseModal = {
        open: openModal,
        close: closeModal
    };

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", initModals);
})();
