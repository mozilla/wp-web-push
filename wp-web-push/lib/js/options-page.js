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
};
