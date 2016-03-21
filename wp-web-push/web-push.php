<?php

define('GCM_REQUEST_URL', 'https://android.googleapis.com/gcm/send');

function sendNotification($endpoint, $gcmKey, $sync) {
  $headers = array();
  $expectedResponseCode = 201;
  $requestURL = $endpoint;
  $body = '';

  if (strpos($endpoint, GCM_REQUEST_URL) === 0) {
    if (!$gcmKey) {
      // If the GCM key isn't set, ignore the request to send a notification
      // and return true so the caller doesn't think the request failed.
      return true;
    }

    $endpointSections = explode('/', $endpoint);
    $subscriptionId = $endpointSections[sizeof($endpointSections) - 1];
    $body = json_encode(array('registration_ids' => array($subscriptionId)));

    $headers['Authorization'] = 'key=' . $gcmKey;
    $headers['Content-Type'] = 'application/json';
    $headers['Content-Length'] = strlen($body);
    $requestURL = GCM_REQUEST_URL;
    $expectedResponseCode = 200;
  } else {
    // Ask the push service to store the message for 4 weeks.
    $headers['TTL'] = 2419200;
  }

  $result = wp_remote_post($requestURL, array(
    'blocking' => $sync ? true : false,
    'headers' => $headers,
    'body' => $body,
  ));

  if (!$sync) {
    return true;
  }

  if (is_wp_error($result)) {
    // If there's an error during the request, return true
    // so the caller doesn't think the request failed.
    return true;
  }

  return $result['response']['code'] === $expectedResponseCode;
}

?>
