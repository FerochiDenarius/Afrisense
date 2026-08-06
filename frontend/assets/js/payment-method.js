// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Defines the updatePaymentFlow helper for this browser module.
    function updatePaymentFlow(form) {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        var method = checked ? checked.value : '';
        var isCash = method === 'Cash';

        form.querySelectorAll('[data-payment-choice]').forEach(function (choice) {
            var input = choice.querySelector('input[name="payment_method"]');
            choice.classList.toggle('is-active', Boolean(input && input.checked));
        });

        form.querySelectorAll('[data-payment-panel]').forEach(function (panel) {
            var isActive = panel.getAttribute('data-payment-panel') === method;
            panel.hidden = !isActive;
            panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !isActive;
            });
        });

        // Find the page elements controlled by this script.
        document.querySelectorAll('[data-payment-online-note]').forEach(function (note) {
            note.hidden = isCash;
        });

        var heading = document.querySelector('[data-payment-heading]');
        // Run this branch only when the required UI state is present.
        if (heading) {
            var icon = heading.querySelector(':scope > i');
            var title = heading.querySelector('h1');
            var copy = heading.querySelector('p');

            // Run this branch only when the required UI state is present.
            if (icon) {
                icon.className = isCash ? 'bi bi-bag-check' : 'bi bi-lock';
            }

            // Run this branch only when the required UI state is present.
            if (title) {
                title.textContent = isCash ? heading.dataset.cashTitle : heading.dataset.onlineTitle;
            }

            // Run this branch only when the required UI state is present.
            if (copy) {
                copy.textContent = isCash ? heading.dataset.cashCopy : heading.dataset.onlineCopy;
            }
        }

        var terms = form.querySelector('[data-payment-terms]');
        var termsCheckbox = form.querySelector('[data-payment-terms-checkbox]');

        // Run this branch only when the required UI state is present.
        if (terms) {
            terms.hidden = isCash;
        }

        // Run this branch only when the required UI state is present.
        if (termsCheckbox) {
            termsCheckbox.required = !isCash;

            // Run this branch only when the required UI state is present.
            if (isCash) {
                termsCheckbox.checked = false;
            }
        }

        var submit = form.querySelector('[data-payment-submit]');
        var submitIcon = form.querySelector('[data-payment-submit-icon]');
        var submitText = submit ? submit.querySelector('span') : null;

        // Run this branch only when the required UI state is present.
        if (submitIcon) {
            submitIcon.className = isCash ? 'bi bi-check2-circle' : 'bi bi-lock';
        }

        // Run this branch only when the required UI state is present.
        if (submitText && submit) {
            submitText.textContent = isCash ? submit.dataset.cashLabel : submit.dataset.onlineLabel;
        }
    }

    // Find the page elements controlled by this script.
    document.querySelectorAll('[data-payment-flow]').forEach(function (form) {
        updatePaymentFlow(form);
        // Bind the UI event handler for this interactive control.
        form.addEventListener('change', function (event) {
            // Run this branch only when the required UI state is present.
            if (event.target.matches('input[name="payment_method"]')) {
                updatePaymentFlow(form);
            }
        });
    });
})();
