// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        // Find the page elements controlled by this script.
        document.querySelectorAll(".af-enquiry-types label").forEach(function (label) {
            // Bind the UI event handler for this interactive control.
            label.addEventListener("click", function () {
                // Find the page elements controlled by this script.
                document.querySelectorAll(".af-enquiry-types label").forEach(function (item) {
                    item.classList.remove("is-active");
                });
                label.classList.add("is-active");
            });
        });
    });
})();
