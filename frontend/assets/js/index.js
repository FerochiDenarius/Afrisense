document.addEventListener("DOMContentLoaded", function () {
    var bookingForm = document.querySelector(".booking-card");

    if (!bookingForm) {
        return;
    }

    var date = document.getElementById("booking_date");

    if (!date) {
        return;
    }

    var today = new Date();
    var offset = today.getTimezoneOffset();
    var local = new Date(today.getTime() - offset * 60 * 1000);

    date.min = local.toISOString().split("T")[0];
});
