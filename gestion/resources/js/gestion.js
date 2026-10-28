document.addEventListener('DOMContentLoaded', () => {
    const spaceSelect = document.getElementById('espacio_id');
    const subspacePicker = document.querySelector('[data-subspace-picker]');

    if (spaceSelect && subspacePicker) {
        const options = Array.from(subspacePicker.querySelectorAll('[data-space-id]'));
        const checkboxes = options.map((option) => option.querySelector('input[type="checkbox"]'));
        const placeholder = subspacePicker.querySelector('[data-subspace-placeholder]');
        const notApplicable = subspacePicker.querySelector('[data-subspace-not-applicable]');

        const syncSubspaces = (clearInvalid = false) => {
            const spaceId = spaceSelect.value;
            const availableOptions = options.filter((option) => option.dataset.spaceId === spaceId);
            options.forEach((option) => {
                const matches = option.dataset.spaceId === spaceId;
                option.hidden = !matches;
                const checkbox = option.querySelector('input[type="checkbox"]');
                checkbox.disabled = !matches;
                if (!matches && clearInvalid) {
                    checkbox.checked = false;
                }
            });

            placeholder.hidden = Boolean(spaceId);
            notApplicable.hidden = !spaceId || availableOptions.length > 0;
        };

        subspacePicker.closest('form')?.addEventListener('submit', (event) => {
            const available = checkboxes.filter((checkbox) => !checkbox.disabled);
            const first = available[0];
            if (!first) {
                return;
            }

            const hasSelection = available.some((checkbox) => checkbox.checked);
            first.setCustomValidity(hasSelection ? '' : 'Selecciona al menos un subespacio.');
            if (!hasSelection) {
                event.preventDefault();
                first.reportValidity();
            }
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                checkboxes.forEach((item) => item.setCustomValidity(''));
            });
        });

        syncSubspaces();
        spaceSelect.addEventListener('change', () => syncSubspaces(true));
    }

    document.querySelectorAll('.resource-quantity').forEach((input) => {
        const validateQuantity = () => {
            const max = Number(input.dataset.max);
            const quantity = Number(input.value);

            input.setCustomValidity(quantity > max
                ? `No hay suficientes unidades disponibles. Cantidad disponible: ${max}.`
                : '');
        };

        input.addEventListener('input', validateQuantity);
        validateQuantity();
    });

    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    if (startTime && endTime) {
        const syncEndTimeMinimum = () => {
            endTime.min = startTime.value;
        };

        syncEndTimeMinimum();
        startTime.addEventListener('change', syncEndTimeMinimum);
    }

});
