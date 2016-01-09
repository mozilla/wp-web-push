self.addEventListener('install', function(event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
  event.waitUntil(
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=webpush_get_payload')
    .then(function(response) {
      return response.json();
    })
    .then(function(data) {
      return self.registration.showNotification(data.title, {
        body: data.body,
      });
    })
  );
});
