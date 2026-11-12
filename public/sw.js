/* CoriSchool — service worker (PWA + Web Push)
 *
 * Stratégie volontairement prudente :
 *  - les assets Vite hachés (/build/...) sont immutables -> cache-first ;
 *  - les ressources statiques publiques (icônes, manifest) -> cache-first ;
 *  - les navigations (HTML) sont network-first avec page hors-ligne en secours ;
 *  - tout le reste (JSON Inertia, endpoints authentifiés, pages privées) n'est
 *    JAMAIS mis en cache afin de ne pas exposer de données personnelles.
 */

const VERSION = 'corischool-v3';
const OFFLINE_URL = '/offline.html';
const STATIC_PATHS = [
    '/favicon.ico',
    '/favicon.svg',
    '/favicon-96x96.png',
    '/apple-touch-icon.png',
    '/web-app-manifest-192x192.png',
    '/web-app-manifest-512x512.png',
];

const PRECACHE = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/favicon.svg',
    '/favicon-96x96.png',
    '/apple-touch-icon.png',
    '/web-app-manifest-192x192.png',
    '/web-app-manifest-512x512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(VERSION)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

function cacheFirst(request) {
    return caches.match(request).then((cached) => {
        if (cached) {
            return cached;
        }

        return fetch(request).then((response) => {
            if (response.ok) {
                const copy = response.clone();
                caches.open(VERSION).then((cache) => cache.put(request, copy));
            }

            return response;
        });
    });
}

function staleWhileRevalidate(request) {
    return caches.match(request).then((cached) => {
        const network = fetch(request)
            .then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(VERSION).then((cache) => cache.put(request, copy));
                }

                return response;
            })
            .catch(() => cached);

        return cached || network;
    });
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Même origine uniquement.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Assets hachés de Vite : cache-first (immutables).
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));

        return;
    }

    // Le manifest doit pouvoir être mis à jour sans attendre un nouveau SW.
    if (url.pathname === '/manifest.webmanifest') {
        event.respondWith(staleWhileRevalidate(request));

        return;
    }

    // Ressources statiques publiques : cache-first.
    if (
        url.pathname.startsWith('/icons/')
        || STATIC_PATHS.includes(url.pathname)
    ) {
        event.respondWith(cacheFirst(request));

        return;
    }

    // Navigations : réseau d'abord, page hors-ligne en secours.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );
    }

    // Les autres requêtes (dont les appels JSON Inertia) passent en direct.
});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'CoriSchool';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/web-app-manifest-192x192.png',
        badge: payload.badge || '/favicon-96x96.png',
        tag: payload.tag || undefined,
        renotify: Boolean(payload.tag),
        data: { url: payload.url || '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
