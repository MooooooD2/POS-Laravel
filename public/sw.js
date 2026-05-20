// POS Service Worker — static asset caching + offline fallback
const CACHE  = 'pos-v1';
const ASSETS = [
    '/css/styles.css',
    '/js/app.js',
    '/js/pos-offline.js',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE)
            .then(c => c.addAll(ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => k !== CACHE).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    const { request } = e;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) return;

    // Skip API / form POST calls — let them fail naturally so JS can handle offline queuing
    if (url.pathname.startsWith('/api/') || request.method !== 'GET') return;

    // Static assets: cache-first
    if (ASSETS.includes(url.pathname)) {
        e.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(res => {
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(request, clone));
                return res;
            }))
        );
        return;
    }

    // Navigation (POS page load): network-first, fall back to cache
    if (request.mode === 'navigate') {
        e.respondWith(
            fetch(request)
                .then(res => {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(request, clone));
                    return res;
                })
                .catch(() => caches.match(request))
        );
    }
});
