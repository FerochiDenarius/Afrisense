// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the initSidebar helper for this browser module.
    function initSidebar() {
        var toggle = document.querySelector("[data-sidebar-toggle]");
        var close = document.querySelector("[data-sidebar-close]");

        // Run this branch only when the required UI state is present.
        if (!toggle) {
            return;
        }

        // Bind the UI event handler for this interactive control.
        toggle.addEventListener("click", function () {
            var isMobile = window.matchMedia("(max-width: 920px)").matches;

            // Run this branch only when the required UI state is present.
            if (isMobile) {
                var isOpen = document.body.classList.toggle("af-sidebar-open");
                toggle.setAttribute("aria-expanded", String(isOpen));
                return;
            }

            var isCollapsed = document.body.classList.toggle("af-sidebar-collapsed");
            toggle.setAttribute("aria-expanded", String(!isCollapsed));
        });

        // Run this branch only when the required UI state is present.
        if (close) {
            // Bind the UI event handler for this interactive control.
            close.addEventListener("click", function () {
                document.body.classList.remove("af-sidebar-open");
                toggle.setAttribute("aria-expanded", "false");
            });
        }

        // Bind the UI event handler for this interactive control.
        document.addEventListener("keydown", function (event) {
            // Run this branch only when the required UI state is present.
            if (event.key === "Escape") {
                document.body.classList.remove("af-sidebar-open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", initSidebar);
})();
