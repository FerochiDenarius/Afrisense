// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    "use strict";

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        var selectAll = document.querySelector("[data-select-all-enquiries]");
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll("[data-enquiry-checkbox]"));
        var selectedCount = document.querySelector("[data-selected-enquiry-count]");
        var markReadButton = document.querySelector("[data-bulk-status]");
        var sortSelect = document.querySelector("[data-enquiry-sort]");

        // Defines the updateSelection helper for this browser module.
        function updateSelection() {
            var checked = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            // Run this branch only when the required UI state is present.
            if (selectedCount) {
                selectedCount.textContent = String(checked);
            }

            // Run this branch only when the required UI state is present.
            if (selectAll) {
                selectAll.checked = checked > 0 && checked === checkboxes.length;
                selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
            }
        }

        // Run this branch only when the required UI state is present.
        if (selectAll) {
            // Bind the UI event handler for this interactive control.
            selectAll.addEventListener("change", function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateSelection();
            });
        }

        checkboxes.forEach(function (checkbox) {
            // Bind the UI event handler for this interactive control.
            checkbox.addEventListener("change", updateSelection);
        });

        // Run this branch only when the required UI state is present.
        if (markReadButton) {
            // Bind the UI event handler for this interactive control.
            markReadButton.addEventListener("click", function () {
                var statusSelect = document.querySelector(".af-bulk-status-select select");

                // Run this branch only when the required UI state is present.
                if (statusSelect) {
                    statusSelect.value = markReadButton.getAttribute("data-bulk-status") || "Read";
                }
            });
        }

        // Run this branch only when the required UI state is present.
        if (sortSelect) {
            // Bind the UI event handler for this interactive control.
            sortSelect.addEventListener("change", function () {
                var sortForm = document.getElementById("af-enquiries-sort-form");

                // Run this branch only when the required UI state is present.
                if (sortForm) {
                    sortForm.submit();
                }
            });
        }

        updateSelection();
    });
}());
