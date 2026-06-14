import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import path from 'path';

export default defineConfig({
    plugins: [
        svelte({
            compilerOptions: {
                customElement: true,
                // Поддържа Svelte 3/4 Component API ($set/$on/$destroy) за стари компоненти
                compatibility: { componentApi: 4 }
            }
        })
    ],
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        cors: true
    },
    build: {
        outDir: path.resolve('./themes/default/assets/js'),
        emptyOutDir: false,
        rollupOptions: {
            input: path.resolve('./svelte/svelte-all.js'),
            output: {
                entryFileNames: 'all.js',  // Генерира bundle директно в outDir
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]'
            }
        }
    }
});
