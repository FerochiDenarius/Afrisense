document.addEventListener("DOMContentLoaded", function () {
    var selectAll = document.querySelector(".bulk-actions input[type='checkbox']");
    var selectedText = document.querySelector(".bulk-actions span");
    var cards = document.querySelectorAll(".image-frame input[type='checkbox']");

    function updateSelectedCount() {
        var selected = document.querySelectorAll(".image-frame input[type='checkbox']:checked").length;
        if (selectedText) {
            selectedText.textContent = selected + " selected";
        }
    }

    if (selectAll) {
        selectAll.addEventListener("change", function () {
            cards.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }

    cards.forEach(function (checkbox) {
        checkbox.addEventListener("change", updateSelectedCount);
    });
});
