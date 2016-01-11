self.addEventListener('install', function(event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', function(event) {
  var data = event.notification.data;
  var url = data.url;

  event.waitUntil(
    self.clients.matchAll()
    .then(function(clientList) {
      for (var i = 0; i < clientList.length; i++) {
        if (clientList[i].url === url) {
          return clientList[i].focus();
        }
      }

      return self.clients.openWindow(url);
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
        data: data,
      });
    })
  );
});
