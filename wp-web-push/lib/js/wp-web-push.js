if (navigator.serviceWorker) {
  // Remove the webpush_post_id query parameter from the URL.
  (function() {
    var url = new URL(location.href);

    if (!url.search || url.search.indexOf('webpush_post_id') === -1) {
      return;
    }

    var queryVars = url.search.substring(1).split('&').filter(function(queryVar) {
      return !queryVar.startsWith('webpush_post_id');
    });

    if (queryVars.length) {
      url.search = '?' + queryVars.join('&');
    } else {
      url.search = '';
    }

    history.replaceState({}, document.title, url.href);
  })();

  var mouseOnTooltip = false;
  var mouseOnButton = false;

  var hideTooltipTimeout;
  function hideTooltip() {
    clearTimeout(hideTooltipTimeout);

    if (mouseOnTooltip || mouseOnButton) {
      return;
    }

    hideTooltipTimeout = setTimeout(function() {
      if (!mouseOnTooltip && !mouseOnButton) {
        setSubscriptionTip(null);
      }
    }, 200);
  }

  var transientTooltipIntervalId;
  function setSubscriptionTip(tip, dontFade) {
    if (transientTooltipIntervalId) {
      clearInterval(transientTooltipIntervalId);
    }

    var tooltipElement = document.getElementById('webpush-explanatory-bubble');
    var bubbleTip = document.getElementById('webpush-subscription-bubble-tip')
    if (tip) {
      tooltipElement.classList.add('showing-bubble');
      bubbleTip.classList.add('showing-bubble');
      tooltipElement.innerHTML = tip;
      if (!dontFade) {
        transientTooltipIntervalId = setInterval(hideTooltip, 2000);
      }
    } else {
      tooltipElement.classList.remove('showing-bubble');
      bubbleTip.classList.remove('showing-bubble');
    }
  }

  function setNotificationsIndicator(enabled) {
    if (!ServiceWorker.subscription_button) {
      return;
    }

    var bubbleTip = document.getElementById('webpush-subscription-bubble-tip');
    var subscriptionButtonImage = document.getElementById('webpush-subscription-button-image');
    if (enabled) {
      subscriptionButtonImage.style.opacity = 1;
      bubbleTip.style.width = bubbleTip.style.height = subscriptionButtonImage.style.width = subscriptionButtonImage.style.height = '64px';
    } else {
      subscriptionButtonImage.style.opacity = 0.5;
      bubbleTip.style.width = bubbleTip.style.height = subscriptionButtonImage.style.width = subscriptionButtonImage.style.height = '48px';
    }
  }

  function notificationsEnabled() {
    return localforage.getItem('notificationsEnabled');
  }

  function setNotificationsEnabled(enabled) {
    return localforage.setItem('notificationsEnabled', enabled);
  }

  function disableNotifications() {
    return navigator.serviceWorker.getRegistration()
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
    return navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return localforage.getItem('welcomeShown')
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

        return localforage.setItem('welcomeShown', true);
      });
    });
  }

  function promptSubscription(pushManager, ignorePromptInterval) {
    if (Notification.permission === 'granted') {
      // No need to prompt, directly subscribe.
      return pushManager.subscribe({
        userVisibleOnly: true,
      });
    }

    return localforage.getItem('lastPrompted')
    .then(function(lastPrompted) {
      if (!lastPrompted) {
        fetch(ServiceWorker.register_url + '?action=webpush_prompt');
      } else if (!ignorePromptInterval && (lastPrompted + ServiceWorker.prompt_interval * 24 * 60 * 60 * 1000 > Date.now())) {
        // The permission was denied during the last three days, so we don't prompt
        // the user again to avoid bothering them (unless the user explicitly clicked
        // on the subscription button).
        throw new Error('Already prompted not long ago. Don\'t prompt again for a while.');
      }

      localforage.setItem('lastPrompted', Date.now());

      return new Promise(function(resolve, reject) {
        Notification.requestPermission(function(permission) {
          if (permission !== 'granted') {
            reject(new Error('Permission denied.'));
            return;
          }

          resolve();
        });
      });
    })
    .then(function() {
      return pushManager.subscribe({
        userVisibleOnly: true,
      });
    });
  }

  function enableNotifications(ignorePromptInterval) {
    return navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return registration.pushManager.getSubscription()
      .then(function(subscription) {
        if (subscription) {
          return;
        }

        return promptSubscription(registration.pushManager, ignorePromptInterval);
      });
    })
    .then(sendSubscription)
    .then(showWelcome);
  }

  function sendSubscription() {
    return navigator.serviceWorker.getRegistration()
    .then(function(registration) {
      return registration.pushManager.getSubscription();
    })
    .then(function(subscription) {
      if (!subscription) {
        return;
      }

      return localforage.getItem('hasRegistered')
      .then(function(hasRegistered) {
        localforage.setItem('hasRegistered', true);

        var key = subscription.getKey ? subscription.getKey('p256dh') : '';

        var formData = new FormData();
        formData.append('action', 'webpush_register');
        formData.append('endpoint', subscription.endpoint);
        formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
        if (!hasRegistered) {
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
    });
  }

  var onLoad = new Promise(function(resolve, reject) {
    window.onload = resolve;
  });

  onLoad
  .then(function() {
    return $swRegistrations[ServiceWorker.sw_id];
  })
  .then(function() {
    if (!ServiceWorker.subscription_button) {
      return;
    }

    document.getElementById('webpush-explanatory-bubble').onmouseover = function() {
      mouseOnTooltip = true;
      clearTimeout(hideTooltipTimeout);
    };

    document.getElementById('webpush-explanatory-bubble').onmouseout = function() {
      mouseOnTooltip = false;
      hideTooltip();
    };

    document.getElementById('webpush-subscription-button-image').onmouseover = function() {
      mouseOnButton = true;
      clearTimeout(hideTooltipTimeout);

      localforage.setItem('button_interacted', true);

      notificationsEnabled()
      .then(function(enabled) {
        if (enabled && Notification.permission === 'granted') {
          setSubscriptionTip(ServiceWorker.unsubscription_prompt + '<br><button id="webpush-prompt-button" class="webpush-prompt-button">' + ServiceWorker.unsubscription_button_text + '</button>', true);

          document.getElementById('webpush-prompt-button').onclick = function() {
            disableNotifications()
            .then(function() {
              setSubscriptionTip(ServiceWorker.unsubscribed_hint);
            });
          };
        } else {
          setSubscriptionTip(ServiceWorker.subscription_prompt + '<br><img src="' + ServiceWorker.notification_preview + '" alt="" />', true);
        }
      });
    };

    document.getElementById('webpush-subscription-button-image').onmouseout = function() {
      mouseOnButton = false;
      hideTooltip();
    };

    document.getElementById('webpush-subscription-button-image').onclick = function() {
      localforage.setItem('button_interacted', true);
      setNotificationsIndicator(false);

      notificationsEnabled()
      .then(function(enabled) {
        if (enabled && Notification.permission === 'granted') {
          // Do nothing.
        } else {
          enableNotifications(true)
          .then(function() {
            setSubscriptionTip(ServiceWorker.unsubscription_hint);
          });
        }
      });
    };
  })
  .then(function() {
    return notificationsEnabled();
  })
  .then(function(notificationsEnabled) {
    localforage.getItem('button_interacted')
    .then(function(interacted) {
      setNotificationsIndicator(!interacted);
    });

    localforage.getItem('visits')
    .then(function(visits) {
      if (!visits) {
        visits = 1;

        if (ServiceWorker.subscription_button) {
          setTimeout(function() {
            localforage.getItem('button_interacted')
            .then(function(interacted) {
              if (!interacted) {
                setSubscriptionTip(ServiceWorker.subscription_hint);
              }
            });
          }, 5000);
        }
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
