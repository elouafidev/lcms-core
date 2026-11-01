import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'Core/resources/sass/app.scss',
                'Core/resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
