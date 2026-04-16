import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { initializeWorkspaceTheme } from '@/composables/useWorkspaceTheme';
import { route as ziggyRoute } from '../../vendor/tightenco/ziggy';

initializeWorkspaceTheme();

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.config.globalProperties.route = ziggyRoute;
        app.mount(el);
    },
    progress: {
        color: '#0f766e',
    },
});
