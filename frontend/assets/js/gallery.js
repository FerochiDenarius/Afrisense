// Bind the UI event handler for this interactive control.
document.addEventListener("DOMContentLoaded", function () {
    var selectAll = document.querySelector(".bulk-actions input[type='checkbox']");
    var selectedText = document.querySelector(".bulk-actions span");
    var cards = document.querySelectorAll(".image-frame input[type='checkbox']");

    // Defines the updateSelectedCount helper for this browser module.
    function updateSelectedCount() {
        var selected = document.querySelectorAll(".image-frame input[type='checkbox']:checked").length;
        // Run this branch only when the required UI state is present.
        if (selectedText) {
            selectedText.textContent = selected + " selected";
        }
    }

    // Run this branch only when the required UI state is present.
    if (selectAll) {
        // Bind the UI event handler for this interactive control.
        selectAll.addEventListener("change", function () {
            cards.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }

    cards.forEach(function (checkbox) {
        // Bind the UI event handler for this interactive control.
        checkbox.addEventListener("change", updateSelectedCount);
    });
});
