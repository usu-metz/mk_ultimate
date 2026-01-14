// Nom du cache
const CACHE_NAME = 'pwa-cache-v1.1';
const FILES_TO_CACHE = [
	'./',
	'./index.php',
	'./style.css',
	'./manifest.json',
	'./img/icon-192.png',
	'./img/icon-512.png'
];

// Installation du service worker
self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(CACHE_NAME).then((cache) => cache.addAll(FILES_TO_CACHE))
	);
	self.skipWaiting();
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys().then((keyList) => {
			return Promise.all(
				keyList.map((key) => {
					if (key !== CACHE_NAME) return caches.delete(key);
				})
			);
		})
	);
	self.clients.claim();
});

// Interception des requêtes réseau
self.addEventListener('fetch', (event) => {
	event.respondWith(
		caches.match(event.request).then((response) => {
			return response || fetch(event.request);
		})
	);
});
