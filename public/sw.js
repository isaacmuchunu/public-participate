// Public Participation Platform - Service Worker
const CACHE_NAME = 'participate-v1';
const STATIC_CACHE_URLS = ['/', '/manifest.json', '/favicon.ico', '/favicon.svg', '/apple-touch-icon.png'];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => {
                console.log('Service Worker: Static assets cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('Service Worker: Failed to cache static assets', error);
            }),
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    }),
                );
            })
            .then(() => {
                console.log('Service Worker: Activated');
                return self.clients.claim();
            }),
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            // Return cached version if available
            if (cachedResponse) {
                return cachedResponse;
            }

            // Otherwise, fetch from network
            return fetch(event.request)
                .then((response) => {
                    // Don't cache non-successful responses
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone the response for caching
                    const responseToCache = response.clone();

                    // Cache the response for future use
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });

                    return response;
                })
                .catch((error) => {
                    console.error('Service Worker: Fetch failed', error);

                    // Return offline page for navigation requests
                    if (event.request.mode === 'navigate') {
                        return caches.match('/');
                    }

                    throw error;
                });
        }),
    );
});

// Push event - handle push notifications (if supported)
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const options = {
        body: data.body || 'New notification from Participate',
        icon: '/favicon.svg',
        badge: '/favicon.svg',
        tag: data.tag || 'participate-notification',
        requireInteraction: data.requireInteraction || false,
        actions: data.actions || [],
        data: data.data || {},
    };

    event.waitUntil(self.registration.showNotification(data.title || 'Participate', options));
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action) {
        // Handle action buttons
        clients.openWindow(event.action);
    } else {
        // Handle notification click
        clients.openWindow('/');
    }
});
