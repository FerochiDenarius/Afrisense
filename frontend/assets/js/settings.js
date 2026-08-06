// Bind the UI event handler for this interactive control.
document.addEventListener("DOMContentLoaded", () => {
    // Helper callback for the updateSocialPreview behavior in this script.
    const updateSocialPreview = () => {
        const preview = document.querySelector("[data-social-preview]");

        // Run this branch only when the required UI state is present.
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

        // Run this branch only when the required UI state is present.
        if (enabledRows.length === 0) {
            const item = document.createElement("small");
            item.textContent = "No enabled social links yet.";
            preview.appendChild(item);
        }
    };

    // Find the page elements controlled by this script.
    document.querySelectorAll("[data-clear-input]").forEach((button) => {
        // Bind the UI event handler for this interactive control.
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.clearInput || "");

            // Run this branch only when the required UI state is present.
            if (!input) {
                return;
            }

            input.value = "";
            const row = button.closest(".af-social-account");
            const toggle = row ? row.querySelector("[data-social-enabled]") : null;

            // Run this branch only when the required UI state is present.
            if (toggle) {
                toggle.checked = false;
            }

            updateSocialPreview();
        });
    });

    // Find the page elements controlled by this script.
    document.querySelectorAll('.af-social-account input[type="url"]').forEach((input) => {
        // Bind the UI event handler for this interactive control.
        input.addEventListener("input", () => {
            const row = input.closest(".af-social-account");
            const toggle = row ? row.querySelector("[data-social-enabled]") : null;

            // Run this branch only when the required UI state is present.
            if (toggle) {
                toggle.checked = input.value.trim() !== "";
            }

            updateSocialPreview();
        });
    });

    // Find the page elements controlled by this script.
    document.querySelectorAll("[data-social-enabled]").forEach((toggle) => {
        // Bind the UI event handler for this interactive control.
        toggle.addEventListener("change", () => {
            const row = toggle.closest(".af-social-account");
            const input = row ? row.querySelector('input[type="url"]') : null;

            // Run this branch only when the required UI state is present.
            if (!input) {
                updateSocialPreview();
                return;
            }

            // Run this branch only when the required UI state is present.
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

    const deliveryZoneTable = document.querySelector("[data-delivery-zone-table]");
    const addDeliveryZoneButton = document.querySelector("[data-add-delivery-zone]");

    // Helper callback for the bindDeliveryZoneRemove behavior in this script.
    const bindDeliveryZoneRemove = (row) => {
        const removeButton = row.querySelector("[data-remove-delivery-zone]");

        // Run this branch only when the required UI state is present.
        if (!removeButton) {
            return;
        }

        // Bind the UI event handler for this interactive control.
        removeButton.addEventListener("click", () => {
            const rows = deliveryZoneTable ? deliveryZoneTable.querySelectorAll("[data-delivery-zone-row]") : [];

            // Run this branch only when the required UI state is present.
            if (rows.length <= 1) {
                row.querySelectorAll("input").forEach((input) => {
                    input.value = "";
                });

                const status = row.querySelector("select");
                // Run this branch only when the required UI state is present.
                if (status) {
                    status.value = "Inactive";
                }

                return;
            }

            row.remove();
        });
    };

    // Run this branch only when the required UI state is present.
    if (deliveryZoneTable) {
        deliveryZoneTable.querySelectorAll("[data-delivery-zone-row]").forEach(bindDeliveryZoneRemove);
    }

    // Run this branch only when the required UI state is present.
    if (deliveryZoneTable && addDeliveryZoneButton) {
        // Bind the UI event handler for this interactive control.
        addDeliveryZoneButton.addEventListener("click", () => {
            const row = document.createElement("div");
            row.dataset.deliveryZoneRow = "";
            row.innerHTML = `
                <span><input name="delivery_zones[name][]" type="text" placeholder="Zone name"></span>
                <span><input name="delivery_zones[areas][]" type="text" placeholder="Areas / locations"></span>
                <span><input name="delivery_zones[fee][]" type="number" min="0" step="0.01" value="0.00"></span>
                <span><input name="delivery_zones[min_order][]" type="number" min="0" step="0.01" value="0.00"></span>
                <span><select name="delivery_zones[status][]"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></span>
                <span><button type="button" class="af-icon-action danger" data-remove-delivery-zone title="Remove zone"><i class="bi bi-trash" aria-hidden="true"></i></button></span>
            `;
            deliveryZoneTable.appendChild(row);
            bindDeliveryZoneRemove(row);
        });
    }
});
