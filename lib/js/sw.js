self.addEventListener('install', function(event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
  event.waitUntil(
    self.registration.showNotification('A Title', {
      body: 'A Body',
    })
  );
});
