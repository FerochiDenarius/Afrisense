// Bind the UI event handler for this interactive control.
document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.querySelector(".password-toggle");

    // Run this branch only when the required UI state is present.
    if (!toggle) {
        return;
    }

    // Bind the UI event handler for this interactive control.
    toggle.addEventListener("click", function () {
        var input = document.getElementById(toggle.getAttribute("aria-controls"));
        var icon = toggle.querySelector("i");

        // Run this branch only when the required UI state is present.
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
