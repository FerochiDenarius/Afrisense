(function () {
    function submitOnChange(select) {
        select.addEventListener("change", function () {
            if (select.form) {
                select.form.submit();
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".af-remarks-filters select").forEach(submitOnChange);
    });
})();
