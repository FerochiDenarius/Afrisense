(function () {
    function initSidebar() {
        var toggle = document.querySelector("[data-sidebar-toggle]");
        var close = document.querySelector("[data-sidebar-close]");

        if (!toggle) {
            return;
        }

        toggle.addEventListener("click", function () {
            var isMobile = window.matchMedia("(max-width: 920px)").matches;

            if (isMobile) {
                var isOpen = document.body.classList.toggle("af-sidebar-open");
                toggle.setAttribute("aria-expanded", String(isOpen));
                return;
            }

            var isCollapsed = document.body.classList.toggle("af-sidebar-collapsed");
            toggle.setAttribute("aria-expanded", String(!isCollapsed));
        });

        if (close) {
            close.addEventListener("click", function () {
                document.body.classList.remove("af-sidebar-open");
                toggle.setAttribute("aria-expanded", "false");
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                document.body.classList.remove("af-sidebar-open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    document.addEventListener("DOMContentLoaded", initSidebar);
})();
