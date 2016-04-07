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

  function addRecipient($endpoint, $isGCM, $gcmKey, $callback) {
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
      'headers' => $headers,
      'body' => $body,
      'callback' => $callback,
      'expected' => $expectedResponseCode,
    );
  }

  function sendNotifications() {
    if ($this->useMulti) {
      $handles = array();
      foreach ($this->requests as $request) {
        $handles[] = $this->httpCurlMulti->createHandle($request['url'], array(
          'headers' => $request['headers'],
          'body' => $request['body'],
          'method' => 'POST',
        ));
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
      $num = count($this->requests);
      foreach ($this->requests as $request) {
        // Clean approximately ten random subscriptions, to avoid performance problems
        // with sending too many synchronous requests.
        $sync = mt_rand(1, $num) <= 10;

        $result = wp_remote_post($request['url'], array(
          'headers' => $request['headers'],
          'body' => $request['body'],
          'blocking' => $sync,
        ));

        $ret = !$sync ||
               // If there's an error during the request, return true
               // so the caller doesn't think the request failed.
               is_wp_error($result) ||
               $result['response']['code'] === $request['expected'];

        call_user_func($request['callback'], $ret);
      }
    }
  }
}

?>
