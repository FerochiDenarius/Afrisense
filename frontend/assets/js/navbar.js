// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the initNavbar helper for this browser module.
    function initNavbar() {
        var header = document.querySelector("[data-navbar]");
        var toggle = document.querySelector("[data-navbar-toggle]");

        // Run this branch only when the required UI state is present.
        if (!header || !toggle) {
            return;
        }

        // Bind the UI event handler for this interactive control.
        toggle.addEventListener("click", function () {
            var isOpen = header.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", String(isOpen));
        });
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", initNavbar);
})();
