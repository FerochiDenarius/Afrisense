(function () {
    function updateStrength(input) {
        var label = document.querySelector("[data-strength-label]");
        if (!label || !input) {
            return;
        }

        var value = input.value;
        var score = 0;

        if (value.length >= 8) score += 1;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

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

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
            button.addEventListener("click", function () {
                var input = button.parentElement.querySelector("input");
                var icon = button.querySelector("i");
                var isPassword = input.type === "password";

                input.type = isPassword ? "text" : "password";
                button.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");

                if (icon) {
                    icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
                }
            });
        });

        var password = document.querySelector("[data-password-source]");
        if (password) {
            updateStrength(password);
            password.addEventListener("input", function () {
                updateStrength(password);
            });
        }
    });
})();
