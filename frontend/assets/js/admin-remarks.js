// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the submitOnChange helper for this browser module.
    function submitOnChange(select) {
        // Bind the UI event handler for this interactive control.
        select.addEventListener("change", function () {
            // Run this branch only when the required UI state is present.
            if (select.form) {
                select.form.submit();
            }
        });
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        // Find the page elements controlled by this script.
        document.querySelectorAll(".af-remarks-filters select").forEach(submitOnChange);
    });
})();
