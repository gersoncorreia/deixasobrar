// DeixaSobrar PWA Service Worker v1.1
const CACHE_NAME = 'deixasobrar-v1.1';
const STATIC_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/icons/icon.svg',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('Erro ao pré-armazenar assets no cache:', err);
            });
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Apenas requisições GET
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Evita cachear endpoints de API, scanner capture, CSRF e logout
    if (
        url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/scanner/capture') ||
        url.pathname.startsWith('/extratos/import') ||
        url.pathname.startsWith('/admin')
    ) {
        return;
    }

    // Cache-First para fontes do Google e Ícones
    if (
        url.hostname === 'fonts.googleapis.com' ||
        url.hostname === 'fonts.gstatic.com' ||
        url.pathname.startsWith('/icons/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Network-First para páginas e scripts (com fallback para cache se offline)
    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                // Atualiza cache de arquivos estáticos de compilação
                if (
                    networkResponse &&
                    networkResponse.status === 200 &&
                    (url.pathname.startsWith('/build/') || url.pathname.endsWith('.css') || url.pathname.endsWith('.js'))
                ) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});
