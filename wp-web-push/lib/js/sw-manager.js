if (navigator.serviceWorker) {
  function setNotificationsIndicator(enabled) {
    if (!ServiceWorker.subscription_button) {
      return;
    }

    var subscriptionButtonImage = document.getElementById('webpush-subscription-button-image');
    if (enabled) {
      subscriptionButtonImage.src = ServiceWorker.notification_enabled_icon;
    } else {
      subscriptionButtonImage.src = ServiceWorker.notification_disabled_icon;
    }
  }

  function notificationsEnabled() {
    return localforage.getItem('notificationsEnabled');
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
          return false;
        }

        localforage.getItem('lastPrompted')
        .then(function(lastPrompted) {
          if (!lastPrompted) {
            fetch(ServiceWorker.register_url + '?action=webpush_prompt');
          }

          localforage.setItem('lastPrompted', Date.now());
        });

        return registration.pushManager.subscribe({
          userVisibleOnly: true,
        })
        .then(function() {
          return true;
        });
      });
    })
    .then(sendSubscription)
    .then(showWelcome);
  }

  function sendSubscription(isNewRegistration) {
    navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return registration.pushManager.getSubscription();
    })
    .then(function(subscription) {
      if (!subscription) {
        return;
      }

      var key = subscription.getKey ? subscription.getKey('p256dh') : '';

      var formData = new FormData();
      formData.append('action', 'webpush_register');
      formData.append('endpoint', subscription.endpoint);
      formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
      if (isNewRegistration) {
        formData.append('newRegistration', true);
      }

      return fetch(ServiceWorker.register_url, {
        method: 'post',
        body: formData,
      })
      .then(function() {
        setNotificationsEnabled(true);
      })
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
    if (!ServiceWorker.subscription_button) {
      return;
    }

    document.getElementById('webpush-subscription-button-image').onclick = function() {
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

      if (ServiceWorker.subscription_button && notificationsEnabled === false) {
        return;
      }

      if (ServiceWorker.min_visits != -1) {
        enableNotifications();
      } else {
        sendSubscription();
      }
    });
  });
}
