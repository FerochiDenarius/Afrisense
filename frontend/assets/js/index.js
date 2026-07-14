document.addEventListener("DOMContentLoaded", function () {
    var bookingForm = document.querySelector(".booking-card");

    if (!bookingForm) {
        return;
    }

    bookingForm.addEventListener("submit", function (event) {
        var service = document.getElementById("service");
        var date = document.getElementById("booking_date");
        var time = document.getElementById("booking_time");

        if (!service.value || !date.value || !time.value) {
            return;
        }

        event.preventDefault();
        bookingForm.classList.add("is-submitted");
    });
});
