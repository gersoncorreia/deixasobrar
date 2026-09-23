import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { vReveal } from './Directives/vReveal';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'DeixaSobrar';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .directive('reveal', vReveal)
            .mount(el);
    },
    progress: {
        color: '#10b981',
        showSpinner: true,
    },
});

// PWA Service Worker Registration
if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((reg) => {
            console.log('DeixaSobrar PWA Service Worker registrado com sucesso:', reg.scope);
        }).catch((err) => {
            console.warn('Falha no registro do Service Worker:', err);
        });
    });
}
