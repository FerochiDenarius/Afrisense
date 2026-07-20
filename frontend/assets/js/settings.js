document.addEventListener("DOMContentLoaded", () => {
    const updateSocialPreview = () => {
        const preview = document.querySelector("[data-social-preview]");

        if (!preview) {
            return;
        }

        const enabledRows = Array.from(document.querySelectorAll("[data-social-row]")).filter((row) => {
            const input = row.querySelector('input[type="url"]');
            const toggle = row.querySelector("[data-social-enabled]");

            return input && toggle && toggle.checked && input.value.trim() !== "";
        });

        preview.innerHTML = "";

        enabledRows.forEach((row) => {
            const icon = row.dataset.socialIcon || "bi-link";
            const label = row.dataset.socialName || "Social link";
            const item = document.createElement("span");
            item.title = label;
            item.innerHTML = `<i class="bi ${icon}" aria-hidden="true"></i>`;
            preview.appendChild(item);
        });

        if (enabledRows.length === 0) {
            const item = document.createElement("small");
            item.textContent = "No enabled social links yet.";
            preview.appendChild(item);
        }
    };

    document.querySelectorAll("[data-clear-input]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.clearInput || "");

            if (!input) {
                return;
            }

            input.value = "";
            const row = button.closest(".af-social-account");
            const toggle = row ? row.querySelector("[data-social-enabled]") : null;

            if (toggle) {
                toggle.checked = false;
            }

            updateSocialPreview();
        });
    });

    document.querySelectorAll('.af-social-account input[type="url"]').forEach((input) => {
        input.addEventListener("input", () => {
            const row = input.closest(".af-social-account");
            const toggle = row ? row.querySelector("[data-social-enabled]") : null;

            if (toggle) {
                toggle.checked = input.value.trim() !== "";
            }

            updateSocialPreview();
        });
    });

    document.querySelectorAll("[data-social-enabled]").forEach((toggle) => {
        toggle.addEventListener("change", () => {
            const row = toggle.closest(".af-social-account");
            const input = row ? row.querySelector('input[type="url"]') : null;

            if (!input) {
                updateSocialPreview();
                return;
            }

            if (!toggle.checked) {
                input.dataset.previousValue = input.value;
                input.value = "";
            } else if (input.value.trim() === "" && input.dataset.previousValue) {
                input.value = input.dataset.previousValue;
            }

            updateSocialPreview();
        });
    });

    updateSocialPreview();
});
