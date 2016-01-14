<?php

function sendNotification($endpoint) {
  $result = wp_remote_post($endpoint, array(
    'blocking' => true,
    'headers' => array(
      // Ask the push service to store the message for 4 weeks.
      'TTL': 2419200,
    ),
  ));

  return $result['response']['code'] === 201;
}

?>
