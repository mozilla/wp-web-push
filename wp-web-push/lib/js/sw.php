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
    self.clients.matchAll({
      type: "window",
    })
    .then(function(clientList) {
      for (var i = 0; i < clientList.length; i++) {
        if (clientList[i].url === url) {
          return clientList[i].focus();
        }
      }

      var newURL = new URL(url);
      newURL.searchParams.set('webpush_from_notification', 1);

      return self.clients.openWindow(newURL);
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
        icon: data.icon,
        data: data,
      });
    })
  );
});

self.addEventListener('pushsubscriptionchange', function(event) {
  event.waitUntil(
    self.registration.pushManager.subscribe({
      userVisibleOnly: true,
    })
    .then(function(subscription) {
      var key = subscription.getKey ? subscription.getKey('p256dh') : '';

      var formData = new FormData();
      formData.append('action', 'webpush_register');
      formData.append('endpoint', subscription.endpoint);
      formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');

      return fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'post',
        body: formData,
      });
    })
  );
});
