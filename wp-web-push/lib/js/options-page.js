window.onload = function() {
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

      document.getElementById('webpush_icon_custom').value = attachment.url;
      var imageEl = document.getElementById('webpush_icon_custom_image');
      imageEl.src = attachment.url;
      imageEl.style.display = 'inline-block';
    });

    frame.open();
  });

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
};
