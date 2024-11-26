import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/ts/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/src',
            '@ts': '/resources/ts',
            '@images': '/resources/images',
            '@css': '/resources/css',
            '@resources': '/resources',
        }
    }
});
