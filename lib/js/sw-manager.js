if (navigator.serviceWorker) {
  navigator.serviceWorker.register(ServiceWorker.url)
  .then(function(registration) {
    console.log('Service Worker successfully registered.');
  });

  navigator.serviceWorker.ready
  .then(function(registration) {
    return registration.pushManager.getSubscription()
    .then(function(subscription) {
      if (subscription) {
        return subscription;
      }

      return registration.pushManager.subscribe({ userVisibleOnly: true })
      .then(function(newSubscription) {
        return newSubscription;
      });
    });
  })
  .then(function(subscription) {
    var key = subscription.getKey ? subscription.getKey('p256dh') : '';

    // TODO: Send subscription info to the backend.
  });
}
