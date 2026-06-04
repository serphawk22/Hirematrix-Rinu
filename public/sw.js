const CACHE_NAME = 'ai-job-portal-v2';
const OFFLINE_CACHE = 'offline-course-v1';

const STATIC_ASSETS = [
    '/ai-job-portal/public/',
    '/ai-job-portal/public/jobboard/css/custom-bs.css',
    '/ai-job-portal/public/jobboard/css/style.css',
    '/ai-job-portal/public/jobboard/css/hirematrix-style.css',
    '/ai-job-portal/public/jobboard/js/jquery.min.js',
    '/ai-job-portal/public/jobboard/js/bootstrap.bundle.min.js'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(err => {
                console.log('Cache addAll error:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Cache course content and modules
    if (url.pathname.includes('/career-transition/course') || 
        url.pathname.includes('/career-transition/module')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(OFFLINE_CACHE).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request);
                })
        );
        return;
    }
    
    // Default: network first, fallback to cache
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});





