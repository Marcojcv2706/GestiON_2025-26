import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const dockerDevServer = process.env.DOCKER_DEV_SERVER === 'true';
const viteClientPort = Number(process.env.VITE_DEV_SERVER_PORT || 5174);
const dockerAppOrigin = process.env.APP_URL || 'http://localhost:8082';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/calendario.js'],
            refresh: true,
        }),

        tailwindcss(),
    ],
    server: dockerDevServer
        ? {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            origin: `http://localhost:${viteClientPort}`,
            cors: {
                origin: dockerAppOrigin,
            },
            hmr: {
                host: 'localhost',
                clientPort: viteClientPort,
            },
        }
        : undefined,
});