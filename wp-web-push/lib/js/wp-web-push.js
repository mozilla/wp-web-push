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

  (function () {
    // This is crazy: http://stackoverflow.com/questions/11381673/detecting-a-mobile-browser
    var isMobile = (function() {
      var check = false;
      (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true;})(navigator.userAgent||navigator.vendor||window.opera);
      return check;
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

          if (subscriptionButtonInteracted) {
            setNotificationsIndicator(false);
          }
        }
      }, 200);
    }

    function showSubscribe() {
      document.querySelector('#webpush-subscription .message').innerHTML = '<p>' + WP_Web_Push.subscription_prompt + '</p><p><img src="' + WP_Web_Push.notification_preview + '" alt="" /></p>';
      var actionButton = document.querySelector('#webpush-subscription .actions .default');
      actionButton.textContent = WP_Web_Push.subscription_button_text;
      actionButton.onclick = function () {
        enableNotifications(true)
        .then(dismissDialog)
        .then(function() {
          setSubscriptionTip(WP_Web_Push.mobile_unsubscription_hint);
        });
      };
      document.querySelector('#webpush-subscription .dialog').classList.add('shown');
    }

    function showUnsubscribe() {
      document.querySelector('#webpush-subscription .message').innerHTML = '<p>' + WP_Web_Push.unsubscription_prompt + '</p>';
      var actionButton = document.querySelector('#webpush-subscription .actions .default');
      actionButton.textContent = WP_Web_Push.unsubscription_button_text;
      actionButton.onclick = function () {
        disableNotifications()
        .then(dismissDialog);
      };
      document.querySelector('#webpush-subscription .dialog').classList.add('shown');
    }

    function dismissDialog() {
      document.querySelector('#webpush-subscription .dialog').classList.remove('shown');
    }

    var firstTooltipShown = false;
    var transientTooltipIntervalId;
    function setSubscriptionTip(tip, dontFade) {
      if (transientTooltipIntervalId) {
        clearInterval(transientTooltipIntervalId);
      }

      firstTooltipShown = true;

      var tooltipElement = document.querySelector('#webpush-subscription .bubble');
      if (tip) {
        tooltipElement.innerHTML = tip;
        requestAnimationFrame(function() {
          tooltipElement.classList.add('shown')
        });
        if (!dontFade) {
          transientTooltipIntervalId = setInterval(hideTooltip, 2000);
        }
      } else {
        tooltipElement.classList.remove('shown');
      }
    }

    function setNotificationsIndicator(enabled) {
      if (!WP_Web_Push.subscription_button) {
        return;
      }

      if (enabled) {
        document.getElementById('webpush-subscription').classList.remove('interacted');
      } else {
        document.getElementById('webpush-subscription').classList.add('interacted');
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
        return localforage.getItem('endpoint');
      })
      .then(function(endpoint) {
        if (!endpoint) {
          return;
        }

        // Notify server that the user has unregistered.
        var formData = new FormData();
        formData.append('action', 'webpush_unregister');
        formData.append('endpoint', endpoint);

        return fetch(WP_Web_Push.register_url, {
          method: 'post',
          body: formData,
        });
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

          if (WP_Web_Push.welcome_enabled) {
            registration.showNotification(WP_Web_Push.welcome_title, {
              body: WP_Web_Push.welcome_body,
              icon: WP_Web_Push.welcome_icon,
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
          fetch(WP_Web_Push.register_url + '?action=webpush_prompt');
        } else if (!ignorePromptInterval && (lastPrompted + WP_Web_Push.prompt_interval * 24 * 60 * 60 * 1000 > Date.now())) {
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
          return localforage.getItem('endpoint')
          .then(function(oldEndpoint) {
            localforage.setItem('hasRegistered', true);
            localforage.setItem('endpoint', subscription.endpoint);

            var key = subscription.getKey ? subscription.getKey('p256dh') : '';
            var auth = subscription.getKey ? subscription.getKey('auth') : '';

            var formData = new FormData();
            formData.append('action', 'webpush_register');
            formData.append('endpoint', subscription.endpoint);
            formData.append('key', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
            formData.append('auth', auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : '');
            if (!hasRegistered) {
              formData.append('newRegistration', true);
            }
            if (oldEndpoint) {
              formData.append('oldEndpoint', oldEndpoint);
            }

            return fetch(WP_Web_Push.register_url, {
              method: 'post',
              body: formData,
            })
            .then(function() {
              setNotificationsEnabled(true);
            });
          });
        });
      });
    }

    var subscriptionButtonInteracted = false;

    var onLoad = new Promise(function(resolve, reject) {
      window.onload = resolve;
    });

    onLoad
    .then(function() {
      return $swRegistrations[WP_Web_Push.sw_id];
    })
    .then(function() {
      if (/Chrome\/[\d\.]+/.test(navigator.userAgent) && !WP_Web_Push.gcm_enabled) {
        WP_Web_Push.subscription_button = false;
        document.getElementById('webpush-subscription').style.display = 'none';
      }
    })
    .then(function() {
      if (!WP_Web_Push.subscription_button) {
        return;
      }

      if (!isMobile) {
        document.querySelector('#webpush-subscription .bubble').onmouseover = function() {
          mouseOnTooltip = true;
          clearTimeout(hideTooltipTimeout);
        };

        document.querySelector('#webpush-subscription .bubble').onmouseout = function() {
          mouseOnTooltip = false;
          hideTooltip();
        };

        document.querySelector('#webpush-subscription .subscribe').onmouseover = function() {
          mouseOnButton = true;
          clearTimeout(hideTooltipTimeout);

          setNotificationsIndicator(true);

          notificationsEnabled()
          .then(function(enabled) {
            if (enabled && Notification.permission === 'granted') {
              setSubscriptionTip('<p>' + WP_Web_Push.unsubscription_prompt + '</p><p><button class="unsubscribe default">' + WP_Web_Push.unsubscription_button_text + '</button></p>', true);

              document.querySelector('#webpush-subscription .unsubscribe').onclick = function() {
                disableNotifications()
                .then(function() {
                  setSubscriptionTip(WP_Web_Push.unsubscribed_hint);
                });
              };
            } else {
              setSubscriptionTip('<p>' + WP_Web_Push.subscription_prompt + '</p><p><img class="notification-image" src="' + WP_Web_Push.notification_preview + '" alt="" /></p>', true);
            }
          });
        };

        document.querySelector('#webpush-subscription .subscribe').onmouseout = function() {
          mouseOnButton = false;
          hideTooltip();
        };

        document.querySelector('#webpush-subscription .subscribe').onclick = function() {
          localforage.setItem('button_interacted', true);
          subscriptionButtonInteracted = true;

          notificationsEnabled()
          .then(function(enabled) {
            if (enabled && Notification.permission === 'granted') {
              // Do nothing.
            } else {
              enableNotifications(true)
              .then(function() {
                setSubscriptionTip(WP_Web_Push.unsubscription_hint);
              });
            }
          });
        };
      }
      else {
        var closeButton = document.querySelector('#webpush-subscription .actions .dismiss');

        closeButton.textContent = WP_Web_Push.close_button_text;

        document.querySelector('#webpush-subscription .subscribe').onclick = function() {
          localforage.setItem('button_interacted', true);
          subscriptionButtonInteracted = true;
          setNotificationsIndicator(false);

          notificationsEnabled()
          .then(function(enabled) {
            if (enabled && Notification.permission === 'granted') {
              showUnsubscribe();
            } else {
              showSubscribe();
            }
          });
        };

        document.querySelector('#webpush-subscription .dismiss').onclick = function () {
          dismissDialog();
        };

        document.querySelector('#webpush-subscription .close').onclick = function () {
          dismissDialog();
        };
      }
    })
    .then(function() {
      return notificationsEnabled();
    })
    .then(function(notificationsEnabled) {
      localforage.getItem('button_interacted')
      .then(function(interacted) {
        subscriptionButtonInteracted = interacted;
        setNotificationsIndicator(!interacted);
      });

      localforage.getItem('visits')
      .then(function(visits) {
        if (!visits) {
          visits = 1;

          if (WP_Web_Push.subscription_button) {
            setTimeout(function() {
              localforage.getItem('button_interacted')
              .then(function(interacted) {
                subscriptionButtonInteracted = interacted;
                if (!interacted && !firstTooltipShown) {
                  setSubscriptionTip(isMobile ? WP_Web_Push.mobile_subscription_hint : WP_Web_Push.subscription_hint);
                }
              });
            }, 5000);
          }
        } else {
          visits++;
        }
        localforage.setItem('visits', visits);

        if (visits < WP_Web_Push.min_visits) {
          return;
        }

        if (WP_Web_Push.subscription_button && notificationsEnabled === false) {
          return;
        }

        if (WP_Web_Push.min_visits != -1) {
          enableNotifications();
        } else {
          sendSubscription();
        }
      });
    });
  })();
}
