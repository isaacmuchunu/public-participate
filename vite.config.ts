import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Separate vendor chunks for better caching
                    'reka-ui': ['reka-ui'],
                    'icons': ['lucide-vue-next'],
                    'charts': ['chart.js', 'vue-chartjs'],
                    'utils': ['clsx', 'tailwind-merge', 'class-variance-authority'],
                    'editor': ['@tiptap/vue-3', '@tiptap/starter-kit'],
                    'validation': ['@vuelidate/core', '@vuelidate/validators'],
                    'vueuse': ['@vueuse/core'],
                    'i18n': ['vue-i18n'],
                    'date': ['date-fns'],
                },
            },
        },
        // Optimize chunk size warnings
        chunkSizeWarningLimit: 1000,
    },
    optimizeDeps: {
        // Pre-bundle dependencies for faster dev server
        include: [
            'vue',
            '@inertiajs/vue3',
            'reka-ui',
            'lucide-vue-next',
            '@vueuse/core',
            'vue-i18n',
            'date-fns',
        ],
    },
});
