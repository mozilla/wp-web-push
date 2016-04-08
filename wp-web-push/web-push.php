<?php

require_once(plugin_dir_path(__FILE__) . 'class-wp-http-curl-multi.php' );

define('GCM_REQUEST_URL', 'https://android.googleapis.com/gcm/send');
define('GCM_REQUEST_URL_LEN', strlen(GCM_REQUEST_URL) + 1);

class WebPush {
  private $useMulti;
  private $httpCurlMulti;
  public $requests = array();

  function __construct($forceWP = false) {
    $this->useMulti = !$forceWP && WP_Http_Curl_Multi::test();
    if ($this->useMulti) {
      $this->httpCurlMulti = new WP_Http_Curl_Multi();
    }
  }

  function addRecipient($endpoint, $isGCM, $gcmKey, $callback) {
    $headers = array();
    $requestURL = $endpoint;
    $body = '';

    if ($isGCM) {
      $subscriptionId = substr($endpoint, GCM_REQUEST_URL_LEN);
      $body = '{"registration_ids":["' . $subscriptionId . '"]}';

      $headers['Authorization'] = 'key=' . $gcmKey;
      $headers['Content-Type'] = 'application/json';
      $headers['Content-Length'] = strlen($body);
      $requestURL = GCM_REQUEST_URL;
    } else {
      // Ask the push service to store the message for 4 weeks.
      $headers['TTL'] = 2419200;
    }

    $this->requests[] = array(
      'url' => $requestURL,
      'headers' => $headers,
      'body' => $body,
      'callback' => $callback,
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
        while (($curlCode = curl_multi_exec($mh, $still_running)) == CURLM_CALL_MULTI_PERFORM) {
          curl_multi_select($mh);
        }

        if ($curlCode != CURLM_OK) {
          break;
        }

        while ($res = curl_multi_info_read($mh)) {
          call_user_func($request['callback'], in_array(curl_getinfo($res['handle'], CURLINFO_HTTP_CODE), array(200, 201)));
          curl_multi_remove_handle($mh, $res['handle']);
        }
      } while ($still_running);

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
               in_array($result['response']['code'], array(200, 201));

        call_user_func($request['callback'], $ret);
      }
    }
  }
}

?>
