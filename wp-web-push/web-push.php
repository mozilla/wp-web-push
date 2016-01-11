<?php

function sendNotification($endpoint) {
  // TODO: Check if we actually want to make this blocking.
  $response = wp_remote_post($endpoint, array(
    'blocking' => true,
  ));
 
  if (is_wp_error($response)) {
    // Do something.
  }
}

?>
