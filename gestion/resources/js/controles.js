import '../css/controles.css';

const formValidators = new WeakMap();
let generatedId = 0;

const dispatchValueChange = (element) => {
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
};

const connectLabel = (source, control) => {
    if (!source.id) {
        return;
    }

    document.querySelectorAll('label[for]').forEach((label) => {
        if (label.htmlFor === source.id) {
            label.htmlFor = control.id;
        }
    });
};

const selectedOption = (select) => select.options[select.selectedIndex] ?? null;

const enhanceSelect = (select) => {
    if (select.multiple || select.dataset.gestionEnhanced === 'true' || select.closest('[data-gestion-picker-control]')) {
        return;
    }

    select.dataset.gestionEnhanced = 'true';
    const wrapper = document.createElement('div');
    wrapper.className = 'gestion-select';
    wrapper.dataset.disabled = String(select.disabled);
    select.parentNode.insertBefore(wrapper, select);
    wrapper.append(select);
    select.classList.add('gestion-select-source');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const control = document.createElement('input');
    control.type = 'text';
    control.className = `gestion-select-input${select.classList.contains('is-invalid') ? ' is-invalid' : ''}`;
    control.id = `${select.id || `gestion-select-${++generatedId}`}-control`;
    control.autocomplete = 'off';
    control.setAttribute('role', 'combobox');
    control.setAttribute('aria-autocomplete', 'list');
    control.setAttribute('aria-expanded', 'false');
    control.setAttribute('aria-haspopup', 'listbox');
    control.disabled = select.disabled;
    control.required = select.required;
    control.setAttribute('aria-required', String(select.required));
    if (select.getAttribute('aria-label')) {
        control.setAttribute('aria-label', select.getAttribute('aria-label'));
    }
    if (select.getAttribute('aria-describedby')) {
        control.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
    }
    if (select.getAttribute('aria-invalid') === 'true' || select.classList.contains('is-invalid')) {
        control.setAttribute('aria-invalid', 'true');
    }
    control.placeholder = selectedOption(select)?.textContent.trim() || 'Selecciona una opción';

    const list = document.createElement('div');
    list.className = 'gestion-select-menu';
    list.id = `${control.id}-options`;
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    control.setAttribute('aria-controls', list.id);

    const resultStatus = document.createElement('span');
    resultStatus.className = 'visually-hidden';
    resultStatus.setAttribute('role', 'status');
    resultStatus.setAttribute('aria-live', 'polite');
    wrapper.append(control, list, resultStatus);

    const originalRequired = select.required;
    select.required = false;
    connectLabel(select, control);

    let activeIndex = -1;
    let originalValue = '';

    const syncControl = () => {
        const option = selectedOption(select);
        control.value = option && option.value !== '' ? option.textContent.trim() : '';
        control.placeholder = select.options[0]?.textContent.trim() || 'Selecciona una opción';
        control.disabled = select.disabled;
        wrapper.dataset.disabled = String(select.disabled);
        control.required = originalRequired;
        control.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
        if (select.classList.contains('is-invalid')) {
            control.setAttribute('aria-invalid', 'true');
        } else {
            control.removeAttribute('aria-invalid');
        }
    };

    const renderOptions = (term = '') => {
        const normalizedTerm = term.trim().toLocaleLowerCase();
        const matches = Array.from(select.options)
            .map((option, index) => ({ option, index }))
            .filter(({ option }) => !option.disabled && option.textContent.toLocaleLowerCase().includes(normalizedTerm));
        list.replaceChildren();

        if (!select.required && select.value) {
            const clearOption = document.createElement('div');
            clearOption.className = 'gestion-select-option is-clear';
            clearOption.setAttribute('role', 'option');
            clearOption.setAttribute('aria-selected', 'false');
            clearOption.textContent = 'Limpiar selección';
            clearOption.addEventListener('mousedown', (event) => event.preventDefault());
            clearOption.addEventListener('click', () => {
                select.value = '';
                dispatchValueChange(select);
                syncControl();
                closeMenu();
            });
            list.append(clearOption);
        }

        const resultLimit = 50;
        matches.slice(0, resultLimit).forEach(({ option, index }, visibleIndex) => {
            const item = document.createElement('div');
            item.className = 'gestion-select-option';
            item.id = `${list.id}-option-${index}`;
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', String(option.value === select.value));
            item.dataset.index = String(index);
            item.textContent = option.textContent.trim();
            item.addEventListener('mousedown', (event) => event.preventDefault());
            item.addEventListener('click', () => {
                select.selectedIndex = index;
                dispatchValueChange(select);
                syncControl();
                closeMenu();
            });
            list.append(item);
            if (visibleIndex === 0 && activeIndex >= 0) {
                activeIndex = 0;
            }
        });

        resultStatus.textContent = matches.length > resultLimit
            ? `Se muestran las primeras ${resultLimit} coincidencias. Escribe para acotar la búsqueda.`
            : `${matches.length} opciones disponibles.`;
        activeIndex = -1;
    };

    const openMenu = () => {
        if (select.disabled || !list.hidden) {
            return;
        }

        originalValue = select.value;
        control.value = '';
        renderOptions();
        list.hidden = false;
        control.setAttribute('aria-expanded', 'true');
        wrapper.classList.add('is-open');
    };

    function closeMenu(restore = false) {
        if (list.hidden) {
            return;
        }

        if (restore) {
            select.value = originalValue;
            syncControl();
        }
        list.hidden = true;
        control.setAttribute('aria-expanded', 'false');
        control.removeAttribute('aria-activedescendant');
        wrapper.classList.remove('is-open');
        activeIndex = -1;
    }

    control.addEventListener('focus', openMenu);
    control.addEventListener('click', openMenu);
    control.addEventListener('input', () => {
        if (list.hidden) {
            openMenu();
        }
        select.value = '';
        renderOptions(control.value);
    });
    control.addEventListener('keydown', (event) => {
        const options = Array.from(list.querySelectorAll('[role="option"]:not(.is-clear)'));
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.hidden) {
                openMenu();
            }
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            activeIndex = Math.max(0, Math.min(options.length - 1, activeIndex + direction));
            options.forEach((option, index) => option.classList.toggle('is-active', index === activeIndex));
            const active = options[activeIndex];
            if (active) {
                control.setAttribute('aria-activedescendant', active.id);
                active.scrollIntoView({ block: 'nearest' });
            }
        } else if (event.key === 'Enter' && !list.hidden) {
            event.preventDefault();
            options[activeIndex]?.click();
        } else if (event.key === 'Escape' && !list.hidden) {
            event.preventDefault();
            closeMenu(true);
        } else if (event.key === 'Tab') {
            closeMenu();
        }
    });
    select.addEventListener('change', syncControl);
    select.addEventListener('input', syncControl);
    select.addEventListener('invalid', () => control.focus());
    select.form?.addEventListener('reset', () => window.setTimeout(syncControl, 0));
    document.addEventListener('pointerdown', (event) => {
        if (!wrapper.contains(event.target)) {
            closeMenu();
        }
    });

    const observer = new MutationObserver(() => {
        syncControl();
        if (!list.hidden) {
            renderOptions(control.value);
        }
    });
    observer.observe(select, { childList: true, subtree: true, attributes: true });
    syncControl();
};

const dateKey = (date) => [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getDate()).padStart(2, '0'),
].join('-');

const parseDate = (value) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value ?? '');
    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null;
};

const dateOnly = (value) => value?.slice(0, 10) ?? '';

const enhanceDateInput = (source) => {
    if (source.dataset.gestionEnhanced === 'true') {
        return;
    }

    source.dataset.gestionEnhanced = 'true';
    const originalType = source.type;
    const dateTime = originalType === 'datetime-local';
    const required = source.required;
    const originalId = source.id;
    const wrapper = document.createElement('div');
    wrapper.className = 'gestion-picker';
    source.parentNode.insertBefore(wrapper, source);
    wrapper.append(source);
    source.classList.add('gestion-picker-source');
    source.tabIndex = -1;
    source.setAttribute('aria-hidden', 'true');
    source.required = false;

    const dateButton = document.createElement('button');
    dateButton.type = 'button';
    dateButton.className = `gestion-picker-date${source.classList.contains('is-invalid') ? ' is-invalid' : ''}`;
    dateButton.id = `${originalId || `gestion-date-${++generatedId}`}-control`;
    dateButton.setAttribute('aria-haspopup', 'dialog');
    dateButton.setAttribute('aria-expanded', 'false');
    dateButton.setAttribute('aria-label', 'Seleccionar fecha');

    const dateText = document.createElement('span');
    dateText.className = 'gestion-picker-date-text';
    dateButton.append(dateText);

    const calendarIcon = document.createElement('i');
    calendarIcon.className = 'fa-regular fa-calendar';
    calendarIcon.setAttribute('aria-hidden', 'true');
    dateButton.append(calendarIcon);
    wrapper.append(dateButton);

    let hourSelect = null;
    let minuteSelect = null;
    if (dateTime) {
        const timeControl = document.createElement('div');
        timeControl.className = 'gestion-picker-time';
        timeControl.dataset.gestionPickerControl = '';
        const currentTime = source.value.split('T')[1] ?? '00:00';
        const [currentHour, currentMinute] = currentTime.split(':');
        hourSelect = document.createElement('select');
        hourSelect.className = 'gestion-picker-time-select';
        hourSelect.id = `${dateButton.id}-hour`;
        hourSelect.required = required;
        hourSelect.setAttribute('aria-label', 'Hora');
        minuteSelect = document.createElement('select');
        minuteSelect.className = 'gestion-picker-time-select';
        minuteSelect.id = `${dateButton.id}-minute`;
        minuteSelect.required = required;
        minuteSelect.setAttribute('aria-label', 'Minutos');
        for (let hour = 0; hour < 24; hour += 1) {
            const value = String(hour).padStart(2, '0');
            hourSelect.add(new Option(value, value));
        }
        for (let minute = 0; minute < 60; minute += 1) {
            const value = String(minute).padStart(2, '0');
            minuteSelect.add(new Option(value, value));
        }
        hourSelect.value = currentHour || '00';
        minuteSelect.value = currentMinute || '00';

        const hourLabel = document.createElement('label');
        hourLabel.className = 'visually-hidden';
        hourLabel.htmlFor = hourSelect.id;
        hourLabel.textContent = 'Hora';
        const minuteLabel = document.createElement('label');
        minuteLabel.className = 'visually-hidden';
        minuteLabel.htmlFor = minuteSelect.id;
        minuteLabel.textContent = 'Minutos';
        const separator = document.createElement('span');
        separator.className = 'gestion-picker-time-separator';
        separator.setAttribute('aria-hidden', 'true');
        separator.textContent = ':';
        timeControl.append(hourLabel, hourSelect, separator, minuteLabel, minuteSelect);
        wrapper.append(timeControl);
    }

    const calendar = document.createElement('div');
    calendar.className = 'gestion-calendar';
    calendar.hidden = true;
    calendar.setAttribute('role', 'dialog');
    calendar.setAttribute('aria-modal', 'false');
    calendar.setAttribute('aria-label', 'Seleccionar fecha');
    wrapper.append(calendar);

    const errorMessage = document.createElement('span');
    errorMessage.className = 'gestion-picker-error';
    errorMessage.setAttribute('role', 'alert');
    errorMessage.textContent = dateTime
        ? 'Selecciona una fecha y hora válida.'
        : 'Selecciona una fecha válida.';
    errorMessage.hidden = true;
    wrapper.append(errorMessage);
    connectLabel(source, dateButton);

    const originalLabel = Array.from(document.querySelectorAll('label[for]'))
        .find((label) => label.htmlFor === dateButton.id);
    const labelText = originalLabel?.textContent.replace('*', '').trim() || 'Fecha';
    dateButton.setAttribute('aria-label', `${labelText}: seleccionar fecha`);
    if (hourSelect && minuteSelect) {
        hourSelect.setAttribute('aria-label', `${labelText.replace(/^fecha y hora/i, 'Hora')}, hora`);
        minuteSelect.setAttribute('aria-label', `${labelText.replace(/^fecha y hora/i, 'Hora')}, minutos`);
    }
    let visibleMonth = parseDate(dateOnly(source.value)) ?? parseDate(source.min) ?? new Date();
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
    let closeTimer;

    const selectedDateText = () => {
        const date = parseDate(dateOnly(source.value));
        return date ? new Intl.DateTimeFormat('es', { dateStyle: 'long' }).format(date) : 'Selecciona una fecha';
    };

    const syncTimeBounds = () => {
        if (!hourSelect || !minuteSelect) {
            return;
        }

        const min = source.min?.split('T');
        const max = source.max?.split('T');
        const date = dateOnly(source.value);
        const minTime = min?.[0] === date ? min[1] ?? '' : '';
        const maxTime = max?.[0] === date ? max[1] ?? '' : '';
        const currentHour = Number(hourSelect.value);
        Array.from(hourSelect.options).forEach((option) => {
            const hour = Number(option.value);
            option.disabled = Boolean(
                (minTime && hour < Number(minTime.slice(0, 2)))
                || (maxTime && hour > Number(maxTime.slice(0, 2)))
            );
        });
        Array.from(minuteSelect.options).forEach((option) => {
            const minute = Number(option.value);
            option.disabled = Boolean(
                (minTime && currentHour === Number(minTime.slice(0, 2)) && minute < Number(minTime.slice(3, 5)))
                || (maxTime && currentHour === Number(maxTime.slice(0, 2)) && minute > Number(maxTime.slice(3, 5)))
            );
        });
    };

    const refreshSource = () => {
        dateText.textContent = selectedDateText();
        dateButton.classList.toggle('is-invalid', source.classList.contains('is-invalid') || wrapper.dataset.invalid === 'true');
        dateButton.setAttribute('aria-invalid', String(source.classList.contains('is-invalid') || wrapper.dataset.invalid === 'true'));
        syncTimeBounds();
        if (!calendar.hidden) {
            renderCalendar();
        }
    };

    const updateSource = () => {
        const date = dateOnly(source.value);
        if (dateTime) {
            source.value = date ? `${date}T${hourSelect.value}:${minuteSelect.value}` : '';
        }
        refreshSource();
        dispatchValueChange(source);
        wrapper.dataset.invalid = 'false';
        errorMessage.hidden = true;
    };

    const selectable = (key) => {
        const minDate = dateOnly(source.min);
        const maxDate = dateOnly(source.max);
        return (!minDate || key >= minDate) && (!maxDate || key <= maxDate);
    };

    const moveFocus = (offset) => {
        const active = parseDate(document.activeElement?.dataset.date)
            ?? parseDate(dateOnly(source.value))
            ?? visibleMonth;
        active.setDate(active.getDate() + offset);
        visibleMonth = new Date(active.getFullYear(), active.getMonth(), 1);
        renderCalendar();
        calendar.querySelector(`[data-date="${dateKey(active)}"]`)?.focus();
    };

    function renderCalendar() {
        const monthTitle = new Intl.DateTimeFormat('es', { month: 'long', year: 'numeric' })
            .format(visibleMonth);
        calendar.replaceChildren();

        const header = document.createElement('div');
        header.className = 'gestion-calendar-header';
        const previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'gestion-calendar-nav';
        previous.setAttribute('aria-label', 'Mes anterior');
        previous.innerHTML = '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>';
        const title = document.createElement('strong');
        title.textContent = monthTitle.charAt(0).toLocaleUpperCase() + monthTitle.slice(1);
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'gestion-calendar-nav';
        next.setAttribute('aria-label', 'Mes siguiente');
        next.innerHTML = '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>';
        header.append(previous, title, next);
        calendar.append(header);

        const weekdays = document.createElement('div');
        weekdays.className = 'gestion-calendar-grid gestion-calendar-weekdays';
        const referenceMonday = new Date(2024, 0, 1);
        for (let index = 0; index < 7; index += 1) {
            const day = document.createElement('span');
            day.textContent = new Intl.DateTimeFormat('es', { weekday: 'short' })
                .format(new Date(referenceMonday.getFullYear(), referenceMonday.getMonth(), referenceMonday.getDate() + index))
                .replace('.', '');
            weekdays.append(day);
        }
        calendar.append(weekdays);

        const days = document.createElement('div');
        days.className = 'gestion-calendar-grid';
        const monthStart = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
        const firstOffset = (monthStart.getDay() + 6) % 7;
        const totalDays = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 0).getDate();
        const today = dateKey(new Date());
        const selected = dateOnly(source.value);
        const focused = parseDate(calendar.querySelector('[data-date].is-focused')?.dataset.date);

        for (let cell = 0; cell < firstOffset + totalDays; cell += 1) {
            if (cell < firstOffset) {
                const spacer = document.createElement('span');
                spacer.className = 'gestion-calendar-spacer';
                spacer.setAttribute('aria-hidden', 'true');
                days.append(spacer);
                continue;
            }

            const date = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), cell - firstOffset + 1);
            const key = dateKey(date);
            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'gestion-calendar-day';
            dayButton.dataset.date = key;
            dayButton.textContent = String(date.getDate());
            dayButton.disabled = !selectable(key);
            dayButton.setAttribute('aria-label', new Intl.DateTimeFormat('es', { dateStyle: 'full' }).format(date));
            dayButton.setAttribute('aria-pressed', String(key === selected));
            if (key === today) {
                dayButton.classList.add('is-today');
            }
            if (key === selected) {
                dayButton.classList.add('is-selected');
            }
            if (focused && key === dateKey(focused)) {
                dayButton.classList.add('is-focused');
            }
            days.append(dayButton);
        }
        while (days.children.length % 7 !== 0) {
            const spacer = document.createElement('span');
            spacer.className = 'gestion-calendar-spacer';
            spacer.setAttribute('aria-hidden', 'true');
            days.append(spacer);
        }
        calendar.append(days);

        if (!required) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'gestion-calendar-clear';
            clear.textContent = 'Limpiar fecha';
            clear.addEventListener('click', () => {
                source.value = '';
                if (hourSelect && minuteSelect) {
                    hourSelect.value = '00';
                    minuteSelect.value = '00';
                }
                updateSource();
                closeCalendar();
                dateButton.focus();
            });
            calendar.append(clear);
        }

        const previousMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
        const nextMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
        previous.disabled = !selectable(dateKey(new Date(previousMonth.getFullYear(), previousMonth.getMonth() + 1, 0)));
        next.disabled = !selectable(dateKey(nextMonth));
        previous.addEventListener('click', () => {
            visibleMonth = previousMonth;
            renderCalendar();
            calendar.querySelector('.gestion-calendar-nav')?.focus();
        });
        next.addEventListener('click', () => {
            visibleMonth = nextMonth;
            renderCalendar();
            calendar.querySelectorAll('.gestion-calendar-nav')[1]?.focus();
        });
        days.addEventListener('click', (event) => {
            const dayButton = event.target.closest('[data-date]');
            if (!dayButton || dayButton.disabled) {
                return;
            }

            const key = dayButton.dataset.date;
            source.value = dateTime ? `${key}T${hourSelect.value}:${minuteSelect.value}` : key;
            updateSource();
            closeCalendar();
            dateButton.focus();
        });
    }

    calendar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeCalendar();
            dateButton.focus();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveFocus(1);
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveFocus(-1);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveFocus(7);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveFocus(-7);
        } else if (event.key === 'PageDown' || event.key === 'PageUp') {
            event.preventDefault();
            const direction = event.key === 'PageDown' ? 1 : -1;
            const nextMonth = new Date(
                visibleMonth.getFullYear(),
                visibleMonth.getMonth() + direction * (event.shiftKey ? 12 : 1),
                1
            );
            const minMonth = parseDate(dateOnly(source.min));
            const maxMonth = parseDate(dateOnly(source.max));
            if ((!minMonth || nextMonth >= new Date(minMonth.getFullYear(), minMonth.getMonth(), 1))
                && (!maxMonth || nextMonth <= new Date(maxMonth.getFullYear(), maxMonth.getMonth(), 1))) {
                visibleMonth = nextMonth;
                renderCalendar();
                calendar.querySelector('.gestion-calendar-day:not(:disabled)')?.focus();
            }
        }
    });

    const openCalendar = () => {
        window.clearTimeout(closeTimer);
        if (!calendar.hidden) {
            return;
        }
        visibleMonth = new Date(
            (parseDate(dateOnly(source.value)) ?? parseDate(source.min) ?? new Date()).getFullYear(),
            (parseDate(dateOnly(source.value)) ?? parseDate(source.min) ?? new Date()).getMonth(),
            1
        );
        renderCalendar();
        calendar.hidden = false;
        dateButton.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => calendar.classList.add('is-open'));
        const selected = calendar.querySelector('.gestion-calendar-day.is-selected:not(:disabled)');
        const firstAvailable = calendar.querySelector('.gestion-calendar-day:not(:disabled)');
        (selected ?? firstAvailable)?.focus();
    };

    function closeCalendar() {
        if (calendar.hidden) {
            return;
        }
        calendar.classList.remove('is-open');
        dateButton.setAttribute('aria-expanded', 'false');
        closeTimer = window.setTimeout(() => {
            calendar.hidden = true;
        }, 140);
    }

    dateButton.addEventListener('click', () => calendar.hidden ? openCalendar() : closeCalendar());
    hourSelect?.addEventListener('change', updateSource);
    minuteSelect?.addEventListener('change', updateSource);
    document.addEventListener('pointerdown', (event) => {
        if (!wrapper.contains(event.target)) {
            closeCalendar();
        }
    });
    source.addEventListener('change', refreshSource);
    source.addEventListener('input', refreshSource);
    source.form?.addEventListener('reset', () => window.setTimeout(() => {
        if (hourSelect && minuteSelect) {
            const [hour = '00', minute = '00'] = source.value.split('T')[1]?.split(':') ?? [];
            hourSelect.value = hour;
            minuteSelect.value = minute;
        }
        refreshSource();
        wrapper.dataset.invalid = 'false';
        errorMessage.hidden = true;
    }, 0));

    const form = source.form;
    if (form) {
        if (!formValidators.has(form)) {
            const validators = new Set();
            formValidators.set(form, validators);
            form.addEventListener('submit', (event) => {
                for (const validate of validators) {
                    const invalid = validate();
                    if (invalid) {
                        event.preventDefault();
                        invalid.focus();
                        break;
                    }
                }
            }, true);
        }
        formValidators.get(form).add(() => {
            const value = source.value;
            const key = dateOnly(value);
            const valid = (!required || Boolean(value))
                && (!key || selectable(key))
                && (!dateTime || !value || Boolean(hourSelect.value && minuteSelect.value))
                && (!source.min || !value || value >= source.min)
                && (!source.max || !value || value <= source.max);
            wrapper.dataset.invalid = String(!valid);
            errorMessage.hidden = valid;
            dateButton.setAttribute('aria-invalid', String(!valid));
            hourSelect?.setAttribute('aria-invalid', String(!valid));
            minuteSelect?.setAttribute('aria-invalid', String(!valid));
            return valid ? null : dateButton;
        });
    }

    const boundsObserver = new MutationObserver(() => {
        const currentDate = parseDate(dateOnly(source.value)) ?? parseDate(source.min) ?? new Date();
        visibleMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        refreshSource();
    });
    boundsObserver.observe(source, { attributes: true, attributeFilter: ['min', 'max'] });
    refreshSource();
};

const initializeControls = (root = document) => {
    root.querySelectorAll('select').forEach(enhanceSelect);
    root.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(enhanceDateInput);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initializeControls());
} else {
    initializeControls();
}

const dynamicControls = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node instanceof HTMLElement) {
                initializeControls(node);
            }
        });
    });
});
dynamicControls.observe(document.documentElement, { childList: true, subtree: true });
