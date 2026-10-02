import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { route } from 'ziggy-js';

// Le tag <script> injecté par la directive Blade @routes définit window.Ziggy
// avant que ce bundle ne s'exécute ; route() est donc utilisable partout dans l'app.
window.route = (name, params, absolute) => route(name, params, absolute, window.Ziggy);

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
