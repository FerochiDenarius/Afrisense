document.addEventListener("DOMContentLoaded", function () {
    var toggles = document.querySelectorAll(".password-toggle");

    toggles.forEach(function (toggle) {
        toggle.addEventListener("click", function () {
            var inputId = toggle.getAttribute("aria-controls");
            var input = document.getElementById(inputId);
            var icon = toggle.querySelector("i");

            if (!input || !icon) {
                return;
            }

            var shouldShow = input.type === "password";
            input.type = shouldShow ? "text" : "password";
            toggle.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");
            icon.classList.toggle("bi-eye", shouldShow);
            icon.classList.toggle("bi-eye-slash", !shouldShow);
        });
    });

    var cards = document.querySelectorAll(".selection-card");

    cards.forEach(function (card) {
        card.addEventListener("click", function () {
            cards.forEach(function (item) {
                item.classList.remove("active");
            });
            card.classList.add("active");
        });
    });
});
