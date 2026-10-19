import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

import fs from 'node:fs';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: {
        host: '0.0.0.0',
        // Port tetap milik proyek ini: proyek Herd lain juga memakai 5173 (di [::1]),
        // sehingga "localhost:5173" bisa memuat app.js milik proyek lain
        port: 5190,
        strictPort: true,
        hmr: {
            // IPv4 eksplisit: "localhost" bisa diarahkan ke [::1] milik server lain
            host: '127.0.0.1',
        },
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

