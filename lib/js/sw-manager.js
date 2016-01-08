if (navigator.serviceWorker) {
  navigator.serviceWorker.register(ServiceWorker.url)
  .then(function(registration) {
    console.log('Service Worker successfully registered.');
  });
}
