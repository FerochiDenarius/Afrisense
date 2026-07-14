document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.querySelector(".password-toggle");

    if (!toggle) {
        return;
    }

    toggle.addEventListener("click", function () {
        var input = document.getElementById(toggle.getAttribute("aria-controls"));
        var icon = toggle.querySelector("i");

        if (!input || !icon) {
            return;
        }

        var shouldShow = input.type === "password";
        input.type = shouldShow ? "text" : "password";
        toggle.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");
        icon.classList.toggle("bi-eye-slash", shouldShow);
        icon.classList.toggle("bi-eye", !shouldShow);
    });
});
