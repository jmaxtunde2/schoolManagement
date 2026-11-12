import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { route } from 'ziggy-js';

// Le tag <script> injecté par la directive Blade @routes définit window.Ziggy
// avant que ce bundle ne s'exécute ; route() est donc utilisable partout dans l'app.
window.route = (name, params, absolute) => route(name, params, absolute, window.Ziggy);

// Les anciennes installations de la PWA pouvaient encore démarrer sur `/`.
// En mode application installée, on redirige vers le bootstrap parent `/app`.
if (typeof window !== 'undefined') {
    const standalone = window.matchMedia?.('(display-mode: standalone)')?.matches === true
        || window.navigator.standalone === true;
    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';

    if (standalone && currentPath === '/') {
        window.location.replace(window.route('public.pwa.start'));
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Gestion des Notes';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Page Inertia introuvable : ${name}.jsx`);
        }

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: 'var(--school-primary, #1E40AF)',
    },
});

// PWA : enregistrement du service worker (production uniquement pour ne pas
// interférer avec le serveur de développement Vite).
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none',
        }).catch(() => {
            // L'application reste pleinement fonctionnelle sans service worker.
        });
    });
}
