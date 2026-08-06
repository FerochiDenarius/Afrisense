// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the updateStrength helper for this browser module.
    function updateStrength(input) {
        var label = document.querySelector("[data-strength-label]");
        // Run this branch only when the required UI state is present.
        if (!label || !input) {
            return;
        }

        var value = input.value;
        var score = 0;

        // Run this branch only when the required UI state is present.
        if (value.length >= 8) score += 1;
        // Run this branch only when the required UI state is present.
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
        // Run this branch only when the required UI state is present.
        if (/\d/.test(value)) score += 1;
        // Run this branch only when the required UI state is present.
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

        // Run this branch only when the required UI state is present.
        if (score <= 1) {
            label.textContent = "Weak";
            label.style.color = "#f04438";
        } else if (score === 2) {
            label.textContent = "Medium";
            label.style.color = "#f5a400";
        } else if (score === 3) {
            label.textContent = "Good";
            label.style.color = "#65a30d";
        } else {
            label.textContent = "Strong";
            label.style.color = "#159447";
        }
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
            // Bind the UI event handler for this interactive control.
            button.addEventListener("click", function () {
                var input = button.parentElement.querySelector("input");
                var icon = button.querySelector("i");
                var isPassword = input.type === "password";

                input.type = isPassword ? "text" : "password";
                button.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");

                // Run this branch only when the required UI state is present.
                if (icon) {
                    icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
                }
            });
        });

        var password = document.querySelector("[data-password-source]");
        // Run this branch only when the required UI state is present.
        if (password) {
            updateStrength(password);
            // Bind the UI event handler for this interactive control.
            password.addEventListener("input", function () {
                updateStrength(password);
            });
        }
    });
})();
