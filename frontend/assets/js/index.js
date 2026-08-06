// Bind the UI event handler for this interactive control.
document.addEventListener("DOMContentLoaded", function () {
    var bookingForm = document.querySelector(".booking-card");

    // Run this branch only when the required UI state is present.
    if (!bookingForm) {
        return;
    }

    var date = document.getElementById("booking_date");

    // Run this branch only when the required UI state is present.
    if (!date) {
        return;
    }

    var today = new Date();
    var offset = today.getTimezoneOffset();
    var local = new Date(today.getTime() - offset * 60 * 1000);

    date.min = local.toISOString().split("T")[0];
});
