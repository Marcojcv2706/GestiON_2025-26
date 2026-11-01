import './bootstrap';
import '../css/custom.css';
import '../css/app.css';
import '../css/gestion.css';
import './gestion';
import Alpine from 'alpinejs';
import './controles';

if (document.querySelector('[data-resource-statistics]')) {
    import('./reportes-recursos');
}

window.Alpine = Alpine;

Alpine.start();
