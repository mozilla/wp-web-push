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
      var imageEl = document.getElementById('webpush_icon_custom_image');
      imageEl.src = attachment.url;
      imageEl.style.display = 'inline-block';

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
      notificationText.style.setProperty('margin-left', '100px');
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
            // TODO: Use a placeholder.
            setIcon('');
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
};
