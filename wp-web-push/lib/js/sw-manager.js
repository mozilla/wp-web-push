if (navigator.serviceWorker) {
  navigator.serviceWorker.register(ServiceWorker.url)
  .then(function(registration) {
    console.log('Service Worker successfully registered.');

    localforage.getItem('visits')
    .then(function(visits) {
      if (!visits) {
        visits = 1;
      } else {
        visits++;
      }
      localforage.setItem('visits', visits);

      if (visits < ServiceWorker.min_visits) {
        return;
      }

      registration.pushManager.getSubscription()
      .then(function(subscription) {
        if (subscription) {
          return subscription;
        }

        return registration.pushManager.subscribe({
          userVisibleOnly: true,
        });
      })
      .then(function(subscription) {
        var key = subscription.getKey ? subscription.getKey('p256dh') : '';

        var formData = new FormData();
        formData.append('action', 'webpush_register');
        formData.append('endpoint', subscription.endpoint);
        formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');

        fetch(ServiceWorker.register_url, {
          method: 'post',
          body: formData,
        })
        .then(function(response) {
          return response.json();
        })
        .then(function(data) {
          localforage.getItem('welcomeShown')
          .then(function(welcomeShown) {
            if (welcomeShown) {
              return;
            }

            if (data.showWelcome) {
              registration.showNotification(data.title, {
                body: data.body,
                icon: data.icon,
              });
            }

            localforage.setItem('welcomeShown', true);
          });
        });
      });
    });
  });
}
