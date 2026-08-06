// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the dismiss helper for this browser module.
    function dismiss(alert) {
        // Run this branch only when the required UI state is present.
        if (!alert) {
            return;
        }

        alert.classList.add("is-dismissing");
        window.setTimeout(function () {
            alert.remove();
        }, 170);
    }

    // Defines the show helper for this browser module.
    function show(type, message) {
        var stack = document.querySelector("[data-alert-stack]");
        // Run this branch only when the required UI state is present.
        if (!stack) {
            return;
        }

        var alert = document.createElement("div");
        alert.className = "af-alert af-alert-" + (type || "info");
        alert.setAttribute("role", "alert");
        alert.setAttribute("data-alert", "");
        alert.innerHTML = '<i class="bi bi-info-circle-fill" aria-hidden="true"></i><p></p><button type="button" aria-label="Dismiss alert" data-alert-dismiss><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
        alert.querySelector("p").textContent = message || "";
        stack.appendChild(alert);
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("click", function (event) {
        var button = event.target.closest("[data-alert-dismiss]");
        // Run this branch only when the required UI state is present.
        if (button) {
            dismiss(button.closest("[data-alert]"));
        }
    });

    window.AfriSenseAlert = {
        show: show,
        dismiss: dismiss
    };
})();
