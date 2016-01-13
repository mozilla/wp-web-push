<?php

function sendNotification($endpoint) {
  $result = wp_remote_post($endpoint, array(
    'blocking' => true,
  ));

  return $result['response']['code'] === 201;
}

?>
