window.onload = function() {
  // Icon chooser.
  var frame;

  document.getElementById('webpush_icon_custom_button').addEventListener('click', function(event) {
    event.preventDefault();

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      multiple: false,
    });

    frame.on('select', function() {
      attachment = frame.state().get('selection').first().toJSON();

      webPushOptions.custom_icon = attachment.url;

      document.getElementById('webpush_icon_custom').value = attachment.url;

      updateIcon();
    });

    frame.open();
  });


  // Triggers.
  function linkToParent(triggerElem, parentElem) {
    parentElem.addEventListener('change', function(event) {
      if (!parentElem.checked) {
        triggerElem.checked = false;
      }
    });

    triggerElem.addEventListener('change', function(event) {
      if (triggerElem.checked) {
        parentElem.checked = true;
      }
    });
  }

  var triggerElems = document.getElementsByName('webpush_triggers[]');
  for (var i = 0; i < triggerElems.length; i++) {
    var parent = triggerElems[i].getAttribute('parent');
    if (parent) {
      linkToParent(triggerElems[i], document.getElementById('webpush_trigger_' + parent));
    }
  }


  // Notification preview.
  var notificationTitle = document.getElementById('notification-title');
  var notificationIcon = document.getElementById('notification-icon');
  var notificationText = document.getElementById('notification-text');

  var customTitleElem = document.getElementById('webpush_title_custom');
  var titleElems = document.getElementsByName('webpush_title');
  function updateTitle() {
    for (var i = 0; i < titleElems.length; i++) {
      var titleElem = titleElems[i];
      if (titleElem.checked) {
        switch (titleElem.value) {
          case 'blog_title':
            notificationTitle.textContent = webPushOptions.blog_title;
            break;

          case 'custom':
            notificationTitle.textContent = customTitleElem.value;
            break;
        }
      }
    }
  }
  updateTitle();
  customTitleElem.addEventListener('input', updateTitle);
  for (var i = 0; i < titleElems.length; i++) {
    titleElems[i].addEventListener('change', updateTitle);
  }

  var customIconElem = document.getElementById('webpush_icon_custom');
  var iconElems = document.getElementsByName('webpush_icon');

  function setIcon(url) {
    if (!url) {
      notificationIcon.src = '';
      notificationIcon.style.display = 'none';
      notificationText.style.setProperty('margin-left', '10px');
    } else {
      notificationIcon.src = url || '';
      notificationIcon.style.display = 'block';
      notificationText.style.setProperty('margin-left', '90px');
    }
  }

  function updateIcon() {
    for (var i = 0; i < iconElems.length; i++) {
      var iconElem = iconElems[i];
      if (iconElem.checked) {
        switch (iconElem.value) {
          case '':
            setIcon('');
            break;

          case 'post_icon':
            setIcon(webPushOptions.post_icon_placeholder);
            break;

          case 'blog_icon':
            setIcon(webPushOptions.blog_icon);
            break;

          case 'custom':
            setIcon(webPushOptions.custom_icon);
            break;
        }
      }
    }
  }
  updateIcon();
  for (var i = 0; i < iconElems.length; i++) {
    iconElems[i].addEventListener('change', updateIcon);
  }

  // Subscription button color picker.
  var colorFieldElem = jQuery('.webpush_subscription_button_color');

  function updateButtonColor() {
    setTimeout(function() {
      document.getElementById('webpush_subscription_button_svg').getSVGDocument().getElementById('Base-Circle-Copy-5').style.fill = colorFieldElem.val();
    }, 0);
  }

  colorFieldElem.wpColorPicker({
    change: updateButtonColor,
  });

  updateButtonColor();


  // VAPID Config
  var vapidButton = document.getElementById('webpush_vapid_show_config');
  var vapidTable = document.getElementById('vapid_config');
  var vapidPrivateKey = document.getElementById('webpush_vapid_key');
  var vapidPublicKey = document.getElementById('webpush_vapid_public_key');

  if (vapidButton && vapidPrivateKey) {
    vapidButton.addEventListener('click', function(event) {
      if (vapidTable.style.display === 'none') {
        vapidTable.style.display = 'initial';
        vapidButton.value = webPushOptions.vapid_hide_button;
      } else {
        vapidTable.style.display = 'none';
        vapidButton.value = webPushOptions.vapid_show_button;
      }
    });

    vapidPrivateKey.addEventListener('input', function(event) {
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          vapidPublicKey.textContent = xhr.responseText;
        }
      };
      xhr.open('POST', ajaxurl + '?action=webpush_get_public_key', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send('_ajax_nonce=' + encodeURIComponent(webPushOptions.vapid_nonce) + '&privateKey=' + encodeURIComponent(vapidPrivateKey.value));
    });
  }


  // Automatic manifest generation
  var generateManifestElem = document.getElementById('webpush_generate_manifest');
  var generateManifestText = document.getElementById('webpush_generate_manifest_text');
  var generateManifestSenderIdField = document.getElementById('webpush_generate_manifest_sender_id_field');
  var gcmSenderId = document.getElementById('webpush_gcm_sender_id');

  function showManifestJSON() {
    generateManifestSenderIdField.textContent = gcmSenderId.value;

    if (generateManifestElem.checked) {
      generateManifestText.style.display = 'none';
    } else {
      generateManifestText.style.display = 'initial';
    }
  }

  generateManifestElem.addEventListener('change', showManifestJSON);
  gcmSenderId.addEventListener('input', showManifestJSON);
  showManifestJSON();
};
