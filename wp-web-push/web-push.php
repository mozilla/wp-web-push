<?php

require_once(plugin_dir_path(__FILE__) . 'class-wp-http-curl-multi.php' );

define('GCM_REQUEST_URL', 'https://android.googleapis.com/gcm/send');
define('GCM_REQUEST_URL_LEN', strlen(GCM_REQUEST_URL) + 1);

class WebPush {
  private $useMulti;
  private $httpCurlMulti;
  private $requests = array();

  function __construct() {
    $this->useMulti = WP_Http_Curl_Multi::test();
    if ($this->useMulti) {
      $this->httpCurlMulti = new WP_Http_Curl_Multi();
    }
  }

  function addRecipient($endpoint, $isGCM, $gcmKey) {
    $headers = array();
    $expectedResponseCode = 201;
    $requestURL = $endpoint;
    $body = '';

    if ($isGCM) {
      $subscriptionId = substr($endpoint, GCM_REQUEST_URL_LEN);
      $body = '{"registration_ids":["' . $subscriptionId . '"]}';

      $headers['Authorization'] = 'key=' . $gcmKey;
      $headers['Content-Type'] = 'application/json';
      $headers['Content-Length'] = strlen($body);
      $requestURL = GCM_REQUEST_URL;
      $expectedResponseCode = 200;
    } else {
      // Ask the push service to store the message for 4 weeks.
      $headers['TTL'] = 2419200;
    }

    $this->requests[] = array(
      'url' => $requestURL,
      'args' => array(
        'headers' => $headers,
        'body' => $body,
        'method' => 'POST',
      )
    );
  }

  function sendNotifications() {
    if ($this->useMulti) {
      $handles = array();
      foreach ($this->requests as $request) {
        $handles[] = $this->httpCurlMulti->createHandle($request['url'], $request['args']);
      }

      $mh = curl_multi_init();
      foreach ($handles as $handle) {
        curl_multi_add_handle($mh, $handle);
      }

      $still_running = true;
      do {
        curl_multi_exec($mh, $still_running);
        curl_multi_select($mh);
      } while ($still_running);

      foreach ($handles as $handle) {
        curl_multi_remove_handle($mh, $handle);
      }

      curl_multi_close($mh);
    } else {
      foreach ($this->requests as $request) {
        wp_remote_post($requestURL, array(
          'blocking' => false,
          'headers' => $headers,
          'body' => $body,
        ));
      }
    }
  }
}

?>
