(function () {
    const form = document.querySelector('[data-order-form]');

    if (!form) {
        return;
    }

    const foodSelect = form.querySelector('[data-order-food-select]');
    const quantityInput = form.querySelector('[data-order-quantity]');
    const nameNode = form.querySelector('[data-order-name]');
    const priceNode = form.querySelector('[data-order-price]');
    const summaryNode = form.querySelector('[data-order-summary]');
    const subtotalNode = form.querySelector('[data-order-subtotal]');
    const totalNode = form.querySelector('[data-order-total]');
    const deliveryFee = 10;

    const money = new Intl.NumberFormat('en-GH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    function selectedOption() {
        return foodSelect ? foodSelect.options[foodSelect.selectedIndex] : null;
    }

    function updateTotals() {
        const option = selectedOption();
        const price = Number(option ? option.dataset.price : 0);
        const quantity = Math.max(1, Math.min(20, Number(quantityInput ? quantityInput.value : 1) || 1));
        const subtotal = price * quantity;
        const total = subtotal + (price > 0 ? deliveryFee : 0);

        if (quantityInput) {
            quantityInput.value = String(quantity);
        }

        if (priceNode) {
            priceNode.textContent = `GHc ${money.format(price)}`;
        }

        if (summaryNode) {
            summaryNode.textContent = `${quantity} item${quantity === 1 ? '' : 's'} selected`;
        }

        if (subtotalNode) {
            subtotalNode.textContent = `GHc ${money.format(subtotal)}`;
        }

        if (totalNode) {
            totalNode.textContent = `GHc ${money.format(total)}`;
        }
    }

    document.querySelectorAll('[data-order-select]').forEach((button) => {
        button.addEventListener('click', () => {
            const foodId = button.dataset.foodId || '';

            if (foodSelect) {
                foodSelect.value = foodId;
            }

            if (nameNode) {
                nameNode.textContent = button.dataset.foodName || 'Selected food';
            }

            document.querySelectorAll('.af-dish-card').forEach((card) => card.classList.remove('is-selected'));
            button.closest('.af-dish-card')?.classList.add('is-selected');

            document.querySelectorAll('[data-order-select]').forEach((itemButton) => {
                itemButton.innerHTML = '<i class="bi bi-plus-lg"></i> Order This';
            });
            button.innerHTML = '<i class="bi bi-check2"></i> Selected';

            updateTotals();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    foodSelect?.addEventListener('change', () => {
        const option = selectedOption();

        if (nameNode && option) {
            nameNode.textContent = option.textContent.trim();
        }

        updateTotals();
    });

    quantityInput?.addEventListener('input', updateTotals);
    updateTotals();
})();
