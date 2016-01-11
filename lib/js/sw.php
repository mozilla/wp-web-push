self.addEventListener('install', function(event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', function(event) {
  event.waitUntil(
    self.clients.matchAll()
    .then(function(clientList) {
      if (clientList.length > 0) {
        return clientList[0].focus();
      }

      return self.clients.openWindow('<?php bloginfo('url'); ?>');
    })
  );
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
