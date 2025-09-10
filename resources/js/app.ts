import '../css/app.css';

import { useToast } from '@/composables/useToast';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import 'vue3-perfect-scrollbar/style.css';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const { success: showSuccess } = useToast();
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        initXpUpdateCheck();
    },
    progress: {
        color: '#4B5563',
    },
});

let lastXpUpdate: string | null = null;

async function checkXpUpdate() {
    try {
        const res = await fetch('/api/xp-settings/last-update');
        const data = await res.json();

        if (data.updated_at && data.updated_at !== lastXpUpdate) {
            lastXpUpdate = data.updated_at;
            showSuccess('XP settings have been updated! Progression may differ from now on.', undefined, true);
        }
    } catch (e) {
        console.error('Failed to check XP update', e);
    }
}

async function initXpUpdateCheck() {
    const res = await fetch('/api/xp-settings/last-update');
    const data = await res.json();
    lastXpUpdate = data.updated_at || null;

    setInterval(checkXpUpdate, 10 * 60 * 1000);
}

// This will set light / dark mode on page load...
initializeTheme();
