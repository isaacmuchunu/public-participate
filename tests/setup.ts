import { beforeAll, vi } from 'vitest';
import { createApp } from 'vue';
import i18n from '../resources/js/lib/i18n';

// Mock Inertia.js
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        createInertiaApp: vi.fn(),
    };
});

// Global test setup
beforeAll(() => {
    // Setup i18n for tests
    const app = createApp({});
    app.use(i18n);
});
