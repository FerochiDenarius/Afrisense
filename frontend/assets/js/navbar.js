(function () {
    function initNavbar() {
        var header = document.querySelector("[data-navbar]");
        var toggle = document.querySelector("[data-navbar-toggle]");

        if (!header || !toggle) {
            return;
        }

        toggle.addEventListener("click", function () {
            var isOpen = header.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", String(isOpen));
        });
    }

    document.addEventListener("DOMContentLoaded", initNavbar);
})();
