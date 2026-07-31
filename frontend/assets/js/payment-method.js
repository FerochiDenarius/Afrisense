(function () {
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

        document.querySelectorAll('[data-payment-online-note]').forEach(function (note) {
            note.hidden = isCash;
        });

        var heading = document.querySelector('[data-payment-heading]');
        if (heading) {
            var icon = heading.querySelector(':scope > i');
            var title = heading.querySelector('h1');
            var copy = heading.querySelector('p');

            if (icon) {
                icon.className = isCash ? 'bi bi-bag-check' : 'bi bi-lock';
            }

            if (title) {
                title.textContent = isCash ? heading.dataset.cashTitle : heading.dataset.onlineTitle;
            }

            if (copy) {
                copy.textContent = isCash ? heading.dataset.cashCopy : heading.dataset.onlineCopy;
            }
        }

        var terms = form.querySelector('[data-payment-terms]');
        var termsCheckbox = form.querySelector('[data-payment-terms-checkbox]');

        if (terms) {
            terms.hidden = isCash;
        }

        if (termsCheckbox) {
            termsCheckbox.required = !isCash;

            if (isCash) {
                termsCheckbox.checked = false;
            }
        }

        var submit = form.querySelector('[data-payment-submit]');
        var submitIcon = form.querySelector('[data-payment-submit-icon]');
        var submitText = submit ? submit.querySelector('span') : null;

        if (submitIcon) {
            submitIcon.className = isCash ? 'bi bi-check2-circle' : 'bi bi-lock';
        }

        if (submitText && submit) {
            submitText.textContent = isCash ? submit.dataset.cashLabel : submit.dataset.onlineLabel;
        }
    }

    document.querySelectorAll('[data-payment-flow]').forEach(function (form) {
        updatePaymentFlow(form);
        form.addEventListener('change', function (event) {
            if (event.target.matches('input[name="payment_method"]')) {
                updatePaymentFlow(form);
            }
        });
    });
})();
