import Chart from 'chart.js/auto';
import '../css/reportes-recursos.css';

const report = document.querySelector('[data-resource-statistics]');

if (report) {
    const form = report.querySelector('[data-statistics-filters]');
    const status = report.querySelector('[data-statistics-status]');
    const spaceSelect = form.querySelector('[name="espacio_id"]');
    const subspaceSelect = form.querySelector('[name="subespacio_id"]');
    const charts = new Map();
    let activeRequest;

    const palette = {
        blue: '#003366',
        gold: '#EDC001',
        slate: '#66758a',
        lightBlue: '#6f8eae',
    };

    const formatPeriod = (value, period) => {
        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day || 1);
        const options = period === 'mes'
            ? { month: 'short', year: 'numeric' }
            : period === 'semana'
                ? { day: 'numeric', month: 'short' }
                : { day: 'numeric', month: 'short', year: 'numeric' };

        return new Intl.DateTimeFormat('es', options).format(date);
    };

    const renderChart = ({ key, rows, labels, type = 'bar', datasets, indexAxis }) => {
        const canvas = report.querySelector(`[data-chart="${key}"]`);
        const emptyState = report.querySelector(`[data-chart-empty="${key}"]`);
        const hasData = rows.length > 0 && rows.some((row) => (
            datasets.some((dataset) => Number(dataset.value(row)) > 0)
        ));

        charts.get(key)?.destroy();
        charts.delete(key);
        canvas.parentElement.hidden = !hasData;
        emptyState.hidden = hasData;

        if (!hasData) {
            return;
        }

        charts.set(key, new Chart(canvas, {
            type,
            data: {
                labels,
                datasets: datasets.map((dataset) => ({
                    label: dataset.label,
                    data: rows.map(dataset.value),
                    backgroundColor: dataset.color,
                    borderColor: dataset.color,
                    borderWidth: type === 'line' ? 3 : 1,
                    borderRadius: type === 'bar' ? 5 : 0,
                    tension: 0.3,
                    fill: false,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis,
                plugins: {
                    legend: { display: datasets.length > 1 || type === 'doughnut', position: 'bottom' },
                },
                scales: type === 'doughnut' ? {} : {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                },
            },
        }));
    };

    const clearCharts = () => {
        charts.forEach((chart) => chart.destroy());
        charts.clear();
        report.querySelectorAll('.resource-statistics-chart').forEach((container) => {
            container.hidden = true;
        });
        report.querySelectorAll('[data-chart-empty]').forEach((container) => {
            container.hidden = true;
        });
    };

    const fillSelect = (select, entries, valueKey, labelFor) => {
        const currentValue = select.value;
        select.replaceChildren(new Option('Todas', ''));
        entries.forEach((entry) => {
            select.add(new Option(labelFor(entry), entry[valueKey]));
        });
        select.value = currentValue;
    };

    const updateSubspaceOptions = () => {
        const selectedSpace = spaceSelect.value;
        Array.from(subspaceSelect.options).forEach((option) => {
            if (!option.value) {
                return;
            }

            const matchesSpace = !selectedSpace || option.dataset.spaceId === selectedSpace;
            option.hidden = !matchesSpace;
            option.disabled = !matchesSpace;
            if (!matchesSpace && option.selected) {
                subspaceSelect.value = '';
            }
        });
    };

    const updateCharts = (statistics, period) => {
        const topTen = (items) => items.slice(0, 10);
        const topSpaces = topTen(statistics.espacios);
        const topSubspaces = topTen(statistics.subespacios);
        const topResources = topTen(statistics.implementos);
        const topUsers = topTen(statistics.usuarios);

        renderChart({
            key: 'spaces',
            rows: topSpaces,
            labels: topSpaces.map((row) => row.nombre),
            datasets: [{ label: 'Usos', value: (row) => row.usos, color: palette.blue }],
        });
        renderChart({
            key: 'subspaces',
            rows: topSubspaces,
            labels: topSubspaces.map((row) => row.nombre),
            indexAxis: 'y',
            datasets: [{ label: 'Usos', value: (row) => row.usos, color: palette.blue }],
        });
        renderChart({
            key: 'resources',
            rows: topResources,
            labels: topResources.map((row) => row.nombre),
            indexAxis: 'y',
            datasets: [
                { label: 'Solicitudes', value: (row) => row.solicitudes, color: palette.blue },
                { label: 'Unidades', value: (row) => row.unidades, color: palette.gold },
            ],
        });
        renderChart({
            key: 'users',
            rows: topUsers,
            labels: topUsers.map((row) => row.nombre),
            indexAxis: 'y',
            datasets: [
                { label: 'Solicitudes', value: (row) => row.solicitudes, color: palette.blue },
                { label: 'Unidades', value: (row) => row.unidades, color: palette.gold },
            ],
        });
        renderChart({
            key: 'categories',
            type: 'doughnut',
            rows: statistics.categorias,
            labels: statistics.categorias.map((row) => row.categoria),
            datasets: [{
                label: 'Solicitudes',
                value: (row) => row.solicitudes,
                color: [palette.blue, palette.gold, palette.slate],
            }],
        });
        renderChart({
            key: 'periods',
            type: 'line',
            rows: statistics.periodos,
            labels: statistics.periodos.map((row) => formatPeriod(row.periodo, period)),
            datasets: [{ label: 'Solicitudes', value: (row) => row.solicitudes, color: palette.blue }],
        });
    };

    const loadStatistics = async () => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const query = new URLSearchParams(new FormData(form));
        Array.from(query.entries()).forEach(([key, value]) => {
            if (!value) {
                query.delete(key);
            }
        });

        status.hidden = false;
        status.className = 'resource-statistics-status';
        status.querySelector('[data-statistics-message]').textContent = 'Cargando estadísticas…';
        status.querySelector('[data-statistics-retry]').hidden = true;
        clearCharts();

        try {
            const response = await fetch(`${report.dataset.endpoint}?${query}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error(`La API respondió con estado ${response.status}.`);
            }

            const data = await response.json();
            if (!form.dataset.optionsLoaded) {
                fillSelect(form.elements.espacio_id, data.opciones.espacios, 'id', (item) => item.name);
                fillSelect(
                    form.elements.subespacio_id,
                    data.opciones.subespacios,
                    'id',
                    (item) => `${item.name} · ${item.espacio?.name ?? 'Sin espacio'}`
                );
                Array.from(form.elements.subespacio_id.options).forEach((option, index) => {
                    if (index > 0) {
                        option.dataset.spaceId = String(data.opciones.subespacios[index - 1].espacio_id);
                    }
                });
                fillSelect(form.elements.recurso_id, data.opciones.recursos, 'id', (item) => item.name);
                fillSelect(form.elements.usuario_id, data.opciones.usuarios, 'id', (item) => item.name);
                form.dataset.optionsLoaded = 'true';
                updateSubspaceOptions();
            }

            updateCharts(data.estadisticas, data.filtros.periodo);
            status.hidden = true;
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            status.className = 'resource-statistics-status is-error';
            status.querySelector('[data-statistics-message]').textContent = 'No se pudieron cargar las estadísticas. Intenta nuevamente.';
            status.querySelector('[data-statistics-retry]').hidden = false;
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadStatistics();
    });

    form.addEventListener('reset', () => {
        window.setTimeout(loadStatistics, 0);
    });

    status.querySelector('[data-statistics-retry]').addEventListener('click', loadStatistics);
    spaceSelect.addEventListener('change', updateSubspaceOptions);
    loadStatistics();
}
