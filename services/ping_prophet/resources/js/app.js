import './bootstrap';
import '../css/app.css';

import nuxtLabsTheme from 'nuxt-ui-vue/dist/theme/nuxtlabsTheme'
import install from 'nuxt-ui-vue'

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import PrimeVue from "primevue/config";
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
import DataTablesLib from 'datatables.net';
import DataTable from 'datatables.net-vue3';
localStorage.theme = 'light';
DataTable.use(DataTablesLib);
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue)
            .use(install, nuxtLabsTheme)
            .component('DataTable', DataTable)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
