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

    const deliveryZoneTable = document.querySelector("[data-delivery-zone-table]");
    const addDeliveryZoneButton = document.querySelector("[data-add-delivery-zone]");

    const bindDeliveryZoneRemove = (row) => {
        const removeButton = row.querySelector("[data-remove-delivery-zone]");

        if (!removeButton) {
            return;
        }

        removeButton.addEventListener("click", () => {
            const rows = deliveryZoneTable ? deliveryZoneTable.querySelectorAll("[data-delivery-zone-row]") : [];

            if (rows.length <= 1) {
                row.querySelectorAll("input").forEach((input) => {
                    input.value = "";
                });

                const status = row.querySelector("select");
                if (status) {
                    status.value = "Inactive";
                }

                return;
            }

            row.remove();
        });
    };

    if (deliveryZoneTable) {
        deliveryZoneTable.querySelectorAll("[data-delivery-zone-row]").forEach(bindDeliveryZoneRemove);
    }

    if (deliveryZoneTable && addDeliveryZoneButton) {
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
