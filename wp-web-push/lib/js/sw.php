(function(self) {
  self.addEventListener('install', function(event) {
    event.waitUntil(self.skipWaiting());
  });

  self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
  });

  self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    var data = event.notification.data;
    if (!data) {
      return;
    }

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
        if (data.postID) {
          if (newURL.search) {
            newURL.search += '&';
          } else {
            newURL.search += '?';
          }
          newURL.search += 'webpush_post_id=' + data.postID;
        }

        return self.clients.openWindow(newURL);
      })
    );
  });

  var lastNotificationTime = 0;

  self.addEventListener('push', function(event) {
    event.waitUntil(
      Promise.resolve()
      .then(function() {
        if (Date.now() < lastNotificationTime + 30000) {
          return;
        }

        lastNotificationTime = Date.now();

        return fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=webpush_get_payload')
        .then(function(response) {
          return response.json();
        })
        .then(function(data) {
          return self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            data: data,
            tag: 'wp-web-push',
          });
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
        return localforage.getItem('endpoint')
        .then(function(oldEndpoint) {
          var key = subscription.getKey ? subscription.getKey('p256dh') : '';
          var auth = subscription.getKey ? subscription.getKey('auth') : '';

          var formData = new FormData();
          formData.append('action', 'webpush_register');
          formData.append('endpoint', subscription.endpoint);
          formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
          formData.append('auth', auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : '');
          if (oldEndpoint) {
            formData.append('oldEndpoint', oldEndpoint);
          }

          return fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'post',
            body: formData,
          });
        });
      })
    );
  });
}(self));
