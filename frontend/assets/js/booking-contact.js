(function () {
    function todayDateValue() {
        var today = new Date();
        var offset = today.getTimezoneOffset();
        var local = new Date(today.getTime() - offset * 60 * 1000);
        return local.toISOString().split("T")[0];
    }

    function formatDate(value) {
        if (!value) return "";
        var date = new Date(value + "T00:00:00");
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString("en-GH", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    function formatTime(value) {
        if (!value) return "";
        var parts = value.split(":");
        if (parts.length < 2) return value;
        var date = new Date();
        date.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0, 0);
        return date.toLocaleTimeString("en-GH", {
            hour: "numeric",
            minute: "2-digit"
        });
    }

    function updateCharacterCount(textarea) {
        var count = textarea.closest(".af-textarea-wrap")?.querySelector("[data-character-count]");
        if (!count) return;
        var max = textarea.getAttribute("maxlength") || "0";
        count.textContent = textarea.value.length + "/" + max;
    }

    function updateBookingSummary(form) {
        if (!form) return;
        var selected = form.querySelector("[data-service-option].is-active input");
        var selectedLabel = selected ? selected.closest("[data-service-option]") : null;
        var serviceName = selected ? (selected.dataset.serviceName || selected.value) : "Selected Service";
        var basePrice = selectedLabel ? parseFloat(selectedLabel.dataset.price || "0") : 0;
        var guestField = form.querySelector("[data-booking-guests]");
        var dateField = form.querySelector("[data-booking-date]");
        var timeField = form.querySelector("[data-booking-time]");
        var guests = guestField?.value || "4 Guests";
        var guestNumber = parseInt(guests, 10) || 4;
        var extraGuestFee = Math.max(0, guestNumber - 4) * 15;
        var dateValue = formatDate(dateField?.value || "");
        var timeValue = formatTime(timeField?.value || "");
        var dateTimeText = dateValue && timeValue
            ? dateValue + " at " + timeValue
            : "Select date and time";
        var total = basePrice + extraGuestFee;

        var serviceTarget = form.querySelector("[data-summary-service]");
        var dateTarget = form.querySelector("[data-summary-date]");
        var guestTarget = form.querySelector("[data-summary-guests]");
        var totalTarget = form.querySelector("[data-summary-total]");

        if (serviceTarget) serviceTarget.textContent = serviceName;
        if (dateTarget) dateTarget.textContent = dateTimeText;
        if (guestTarget) guestTarget.textContent = guests;
        if (totalTarget) totalTarget.textContent = "GHC " + total.toFixed(2);
    }

    function fieldGroupFor(field) {
        return field.closest(".af-form-group");
    }

    function markField(field) {
        var group = fieldGroupFor(field);
        if (!group) return;
        group.classList.toggle("is-invalid", !field.validity.valid);
    }

    function setupValidation(form) {
        var status = form.querySelector("[data-form-status]");
        var fields = form.querySelectorAll("input, select, textarea");

        fields.forEach(function (field) {
            field.addEventListener("input", function () {
                markField(field);
                if (status) {
                    status.textContent = "";
                    status.className = "af-form-status";
                }
            });
            field.addEventListener("blur", function () {
                markField(field);
            });
            field.addEventListener("change", function () {
                markField(field);
            });
        });

        form.addEventListener("submit", function (event) {
            fields.forEach(markField);

            if (!form.checkValidity()) {
                event.preventDefault();
                var firstInvalid = form.querySelector(":invalid");
                if (firstInvalid) firstInvalid.focus();
                if (status) {
                    status.textContent = "Please complete the highlighted fields before submitting.";
                    status.className = "af-form-status is-error";
                }
                return;
            }

            if (form.getAttribute("action") === "#") {
                event.preventDefault();
                form.reset();
                form.querySelectorAll(".is-invalid").forEach(function (item) {
                    item.classList.remove("is-invalid");
                });
                form.querySelectorAll("[data-character-source]").forEach(updateCharacterCount);
                updateBookingSummary(form);
                if (status) {
                    status.textContent = "Your form is complete and ready for backend submission.";
                    status.className = "af-form-status is-success";
                }
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-character-source]").forEach(function (textarea) {
            updateCharacterCount(textarea);
            textarea.addEventListener("input", function () {
                updateCharacterCount(textarea);
            });
        });

        var bookingForm = document.querySelector("[data-booking-form]");
        if (bookingForm) {
            var dateField = bookingForm.querySelector("[data-booking-date]");
            if (dateField && !dateField.min) {
                dateField.min = todayDateValue();
            }

            bookingForm.querySelectorAll("[data-service-option]").forEach(function (label) {
                label.addEventListener("click", function () {
                    bookingForm.querySelectorAll("[data-service-option]").forEach(function (item) {
                        item.classList.remove("is-active");
                    });
                    label.classList.add("is-active");
                    updateBookingSummary(bookingForm);
                });
            });

            bookingForm.querySelectorAll("[data-booking-date], [data-booking-time], [data-booking-guests]").forEach(function (field) {
                field.addEventListener("input", function () {
                    updateBookingSummary(bookingForm);
                });
                field.addEventListener("change", function () {
                    updateBookingSummary(bookingForm);
                });
            });

            updateBookingSummary(bookingForm);
        }

        document.querySelectorAll("[data-enhanced-form]").forEach(setupValidation);
    });
})();
