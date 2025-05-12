import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h, nextTick } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { flashToastsFromPage, showNetworkErrorToast } from './utils/toast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Vue3Toastify, {
                autoClose: 4500,
                newestOnTop: true,
                pauseOnFocusLoss: false,
                transition: 'slide',
                position: 'top-right',
                limit: 3,
                containerClassName: 'portal-toast-container',
            });

        vueApp.mount(el);

        nextTick(() => {
            flashToastsFromPage(router.page);
        });

        router.on('success', (event) => {
            flashToastsFromPage(event.detail.page);
        });

        router.on('exception', (event) => {
            const exception = event.detail.exception;
            const message = exception instanceof Error ? exception.message : String(exception ?? '');
            const lowered = message.toLowerCase();

            if (lowered.includes('network') || lowered.includes('connection') || lowered.includes('timeout')) {
                showNetworkErrorToast();
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
