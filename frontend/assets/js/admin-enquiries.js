(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var selectAll = document.querySelector("[data-select-all-enquiries]");
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll("[data-enquiry-checkbox]"));
        var selectedCount = document.querySelector("[data-selected-enquiry-count]");
        var markReadButton = document.querySelector("[data-bulk-status]");
        var sortSelect = document.querySelector("[data-enquiry-sort]");

        function updateSelection() {
            var checked = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            if (selectedCount) {
                selectedCount.textContent = String(checked);
            }

            if (selectAll) {
                selectAll.checked = checked > 0 && checked === checkboxes.length;
                selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener("change", function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                updateSelection();
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener("change", updateSelection);
        });

        if (markReadButton) {
            markReadButton.addEventListener("click", function () {
                var statusSelect = document.querySelector(".af-bulk-status-select select");

                if (statusSelect) {
                    statusSelect.value = markReadButton.getAttribute("data-bulk-status") || "Read";
                }
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener("change", function () {
                var sortForm = document.getElementById("af-enquiries-sort-form");

                if (sortForm) {
                    sortForm.submit();
                }
            });
        }

        updateSelection();
    });
}());
