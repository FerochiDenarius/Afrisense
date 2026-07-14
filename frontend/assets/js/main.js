(function () {
    function initFooterAccordions() {
        document.querySelectorAll("[data-footer-toggle]").forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var section = toggle.closest("[data-footer-section]");
                if (section) {
                    section.classList.toggle("is-open");
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", initFooterAccordions);
    document.documentElement.classList.add("js-ready");
})();
