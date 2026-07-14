(function () {
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".af-enquiry-types label").forEach(function (label) {
            label.addEventListener("click", function () {
                document.querySelectorAll(".af-enquiry-types label").forEach(function (item) {
                    item.classList.remove("is-active");
                });
                label.classList.add("is-active");
            });
        });
    });
})();
