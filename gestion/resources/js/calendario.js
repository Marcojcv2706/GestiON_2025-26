
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    const elemento = document.getElementById('calendario');

    if (!elemento) return;

    const calendario = new Calendar(elemento, {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin,
        ],

        initialView: 'dayGridMonth',
        locale: 'es',
        height: 'auto',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },

        events: '/api/calendario',

        eventClick: function (info) {
            const evento = info.event;
            const espacio = evento.extendedProps.espacio
                || 'No especificado';

            alert(
                evento.title
                + '\nEspacio: ' + espacio
                + (evento.extendedProps.descripcion
                    ? '\nDescripción: ' + evento.extendedProps.descripcion
                    : '')
            );
        },
    });

    calendario.render();
});
