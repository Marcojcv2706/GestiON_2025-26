import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    const elemento = document.getElementById('calendario');

    if (!elemento) {
        return;
    }

    const calendario = new Calendar(elemento, {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin
        ],

        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        height: 'auto',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        events: '/api/calendario',
        eventClassNames: function (info) {
            return info.event.extendedProps.tipo === 'Historial'
                ? ['calendar-event-history']
                : ['calendar-event-scheduled'];
        },

        eventClick: function (info) {
            const evento = info.event;
            const espacio = evento.extendedProps.espacio || 'No especificado';
            const descripcion = evento.extendedProps.descripcion;
            const recursos = evento.extendedProps.recursos || [];

            let mensaje = evento.title;
            mensaje += '\nEspacio: ' + espacio;

            if (descripcion) {
                mensaje += '\nDescripción: ' + descripcion;
            }

            if (recursos.length) {
                mensaje += '\nRecursos: ' + recursos.join(', ');
            }

            alert(mensaje);
        }
    });

    calendario.render();
});