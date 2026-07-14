(function () {
    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
    }

    function openModal(modal, options) {
        if (!modal) {
            return;
        }

        if (options && options.message) {
            var message = modal.querySelector("[data-modal-message]");
            if (message) {
                message.textContent = options.message;
            }
        }

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
    }

    function initModals() {
        document.addEventListener("click", function (event) {
            var opener = event.target.closest("[data-modal-open]");
            var closer = event.target.closest("[data-modal-close]");

            if (opener) {
                event.preventDefault();
                openModal(document.querySelector(opener.getAttribute("data-modal-open")));
            }

            if (closer) {
                event.preventDefault();
                closeModal(closer.closest("[data-modal]"));
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                document.querySelectorAll("[data-modal].is-open").forEach(closeModal);
            }
        });
    }

    window.AfriSenseModal = {
        open: openModal,
        close: closeModal
    };

    document.addEventListener("DOMContentLoaded", initModals);
})();
