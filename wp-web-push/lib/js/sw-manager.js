if (navigator.serviceWorker) {
  function setNotificationsIndicator(enabled) {
    if (!ServiceWorker.notification_icon) {
      return;
    }

    var notificationButtonImage = document.getElementById('webpush-notification-button-image');
    if (enabled) {
      notificationButtonImage.src = ServiceWorker.notification_enabled_icon;
    } else {
      notificationButtonImage.src = ServiceWorker.notification_disabled_icon;
    }
  }

  function notificationsEnabled() {
    return localforage.getItem('notificationsEnabled')
    .then(function(enabled) {
      // Set to 'true' by default.
      if (enabled !== true && enabled !== false) {
        return localforage.setItem('notificationsEnabled', true)
        .then(function() {
          return true;
        });
      }

      return enabled;
    });
  }

  function setNotificationsEnabled(enabled) {
    return localforage.setItem('notificationsEnabled', enabled)
    .then(setNotificationsIndicator);
  }

  function disableNotifications() {
    navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return registration.pushManager.getSubscription();
    })
    .then(function(subscription) {
      if (subscription) {
        return subscription.unsubscribe();
      }
    })
    .then(function() {
      setNotificationsEnabled(false);
    });
  }

  function showWelcome() {
    navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      localforage.getItem('welcomeShown')
      .then(function(welcomeShown) {
        if (welcomeShown) {
          return;
        }

        if (ServiceWorker.welcome_enabled) {
          registration.showNotification(ServiceWorker.welcome_title, {
            body: ServiceWorker.welcome_body,
            icon: ServiceWorker.welcome_icon,
          });
        }

        localforage.setItem('welcomeShown', true);
      });
    });
  }

  function enableNotifications() {
    navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return registration.pushManager.getSubscription()
      .then(function(subscription) {
        if (subscription) {
          return [ subscription, false ];
        }

        fetch(ServiceWorker.register_url + '?action=webpush_prompt');

        return registration.pushManager.subscribe({
          userVisibleOnly: true,
        })
        .then(function(subscription) {
          return [ subscription, true ];
        })
      });
    })
    .then(function([ subscription, newRegistration ]) {
      var key = subscription.getKey ? subscription.getKey('p256dh') : '';

      var formData = new FormData();
      formData.append('action', 'webpush_register');
      formData.append('endpoint', subscription.endpoint);
      formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
      if (newRegistration) {
        formData.append('newRegistration', true);
      }

      return fetch(ServiceWorker.register_url, {
        method: 'post',
        body: formData,
      });
    })
    .then(showWelcome)
    .then(function() {
      setNotificationsEnabled(true);
    });
  }

  var onLoad = new Promise(function(resolve, reject) {
    window.onload = resolve;
  });

  onLoad
  .then(function() {
    return navigator.serviceWorker.register(ServiceWorker.url);
  })
  .then(function() {
    if (!ServiceWorker.notification_icon) {
      return;
    }

    document.getElementById('webpush-notification-button').onclick = function() {
      notificationsEnabled()
      .then(function(enabled) {
        if (enabled) {
          disableNotifications();
        } else {
          enableNotifications();
        }
      });
    };
  })
  .then(function() {
    return notificationsEnabled();
  })
  .then(function(notificationsEnabled) {
    setNotificationsIndicator(notificationsEnabled);

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

      if (ServiceWorker.notification_icon && !notificationsEnabled) {
        return;
      }

      enableNotifications();
    });
  });
}
