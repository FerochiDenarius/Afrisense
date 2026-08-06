// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the todayDateValue helper for this browser module.
    function todayDateValue() {
        var today = new Date();
        var offset = today.getTimezoneOffset();
        var local = new Date(today.getTime() - offset * 60 * 1000);
        return local.toISOString().split("T")[0];
    }

    // Defines the formatDate helper for this browser module.
    function formatDate(value) {
        // Run this branch only when the required UI state is present.
        if (!value) return "";
        var date = new Date(value + "T00:00:00");
        // Run this branch only when the required UI state is present.
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString("en-GH", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    // Defines the formatTime helper for this browser module.
    function formatTime(value) {
        // Run this branch only when the required UI state is present.
        if (!value) return "";
        var parts = value.split(":");
        // Run this branch only when the required UI state is present.
        if (parts.length < 2) return value;
        var date = new Date();
        date.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0, 0);
        return date.toLocaleTimeString("en-GH", {
            hour: "numeric",
            minute: "2-digit"
        });
    }

    // Defines the updateCharacterCount helper for this browser module.
    function updateCharacterCount(textarea) {
        var count = textarea.closest(".af-textarea-wrap")?.querySelector("[data-character-count]");
        // Run this branch only when the required UI state is present.
        if (!count) return;
        var max = textarea.getAttribute("maxlength") || "0";
        count.textContent = textarea.value.length + "/" + max;
    }

    // Defines the updateBookingSummary helper for this browser module.
    function updateBookingSummary(form) {
        // Run this branch only when the required UI state is present.
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

        // Run this branch only when the required UI state is present.
        if (serviceTarget) serviceTarget.textContent = serviceName;
        // Run this branch only when the required UI state is present.
        if (dateTarget) dateTarget.textContent = dateTimeText;
        // Run this branch only when the required UI state is present.
        if (guestTarget) guestTarget.textContent = guests;
        // Run this branch only when the required UI state is present.
        if (totalTarget) totalTarget.textContent = "GHC " + total.toFixed(2);
    }

    // Defines the fieldGroupFor helper for this browser module.
    function fieldGroupFor(field) {
        return field.closest(".af-form-group");
    }

    // Defines the markField helper for this browser module.
    function markField(field) {
        var group = fieldGroupFor(field);
        // Run this branch only when the required UI state is present.
        if (!group) return;
        group.classList.toggle("is-invalid", !field.validity.valid);
    }

    // Defines the setupValidation helper for this browser module.
    function setupValidation(form) {
        var status = form.querySelector("[data-form-status]");
        var fields = form.querySelectorAll("input, select, textarea");

        fields.forEach(function (field) {
            // Bind the UI event handler for this interactive control.
            field.addEventListener("input", function () {
                markField(field);
                // Run this branch only when the required UI state is present.
                if (status) {
                    status.textContent = "";
                    status.className = "af-form-status";
                }
            });
            // Bind the UI event handler for this interactive control.
            field.addEventListener("blur", function () {
                markField(field);
            });
            // Bind the UI event handler for this interactive control.
            field.addEventListener("change", function () {
                markField(field);
            });
        });

        // Bind the UI event handler for this interactive control.
        form.addEventListener("submit", function (event) {
            fields.forEach(markField);

            // Run this branch only when the required UI state is present.
            if (!form.checkValidity()) {
                event.preventDefault();
                var firstInvalid = form.querySelector(":invalid");
                // Run this branch only when the required UI state is present.
                if (firstInvalid) firstInvalid.focus();
                // Run this branch only when the required UI state is present.
                if (status) {
                    status.textContent = "Please complete the highlighted fields before submitting.";
                    status.className = "af-form-status is-error";
                }
                return;
            }

            // Run this branch only when the required UI state is present.
            if (form.getAttribute("action") === "#") {
                event.preventDefault();
                form.reset();
                form.querySelectorAll(".is-invalid").forEach(function (item) {
                    item.classList.remove("is-invalid");
                });
                form.querySelectorAll("[data-character-source]").forEach(updateCharacterCount);
                updateBookingSummary(form);
                // Run this branch only when the required UI state is present.
                if (status) {
                    status.textContent = "Your form is complete and ready for backend submission.";
                    status.className = "af-form-status is-success";
                }
            }
        });
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-character-source]").forEach(function (textarea) {
            updateCharacterCount(textarea);
            // Bind the UI event handler for this interactive control.
            textarea.addEventListener("input", function () {
                updateCharacterCount(textarea);
            });
        });

        var bookingForm = document.querySelector("[data-booking-form]");
        // Run this branch only when the required UI state is present.
        if (bookingForm) {
            var dateField = bookingForm.querySelector("[data-booking-date]");
            // Run this branch only when the required UI state is present.
            if (dateField && !dateField.min) {
                dateField.min = todayDateValue();
            }

            bookingForm.querySelectorAll("[data-service-option]").forEach(function (label) {
                // Bind the UI event handler for this interactive control.
                label.addEventListener("click", function () {
                    bookingForm.querySelectorAll("[data-service-option]").forEach(function (item) {
                        item.classList.remove("is-active");
                    });
                    label.classList.add("is-active");
                    updateBookingSummary(bookingForm);
                });
            });

            bookingForm.querySelectorAll("[data-booking-date], [data-booking-time], [data-booking-guests]").forEach(function (field) {
                // Bind the UI event handler for this interactive control.
                field.addEventListener("input", function () {
                    updateBookingSummary(bookingForm);
                });
                // Bind the UI event handler for this interactive control.
                field.addEventListener("change", function () {
                    updateBookingSummary(bookingForm);
                });
            });

            updateBookingSummary(bookingForm);
        }

        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-enhanced-form]").forEach(setupValidation);
    });
})();
