<?php

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Base64Url\Base64Url;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;

require_once(plugin_dir_path(__FILE__) . 'class-wp-http-curl-multi.php' );

define('GCM_REQUEST_URL', 'https://android.googleapis.com/gcm/send');
define('GCM_REQUEST_URL_LEN', strlen(GCM_REQUEST_URL) + 1);
define('USE_VAPID', version_compare(phpversion(), '5.5') >= 0);

class WebPush {
  private $useMulti;
  private $httpCurlMulti;
  public $requests = array();
  private $gcmKey;

  function __construct($forceWP = false) {
    $this->useMulti = !$forceWP && WP_Http_Curl_Multi::test();
    if ($this->useMulti) {
      $this->httpCurlMulti = new WP_Http_Curl_Multi();
    }
  }

  function setGCMKey($gcmKey) {
    $this->gcmKey = $gcmKey;
  }

  function addRecipient($endpoint, $callback) {
    $headers = array();
    $requestURL = $endpoint;
    $body = '';

    if (strpos($endpoint, GCM_REQUEST_URL) === 0) {
      $subscriptionId = substr($endpoint, GCM_REQUEST_URL_LEN);
      $body = '{"registration_ids":["' . $subscriptionId . '"]}';

      $headers['Authorization'] = 'key=' . $this->gcmKey;
      $headers['Content-Type'] = 'application/json';
      $headers['Content-Length'] = strlen($body);
      $requestURL = GCM_REQUEST_URL;
    } else {
      // Ask the push service to store the message for 4 weeks.
      $headers['TTL'] = 2419200;

      if (USE_VAPID) {
        $privateKey = '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIDYEX2yQlhJXDIwBEwcfyAn2eICEKJxqsAPGChey1a2toAoGCCqGSM49
AwEHoUQDQgAEJXwAdITiPFcSUsaRI2nlzTNRn++q6F38XrH8m8sf28DQ+2Oob5SU
zvgjVS0e70pIqH6bSXDgPc8mKtSs9Zi26Q==
-----END EC PRIVATE KEY-----';

        $token = (new Builder())->setAudience('http://catfacts.example.com')
                                ->setExpiration(time() + 86400)
                                ->setSubject('mailto:webpush_ops@catfacts.example.com')
                                ->sign(new Sha256(),  new Key($privateKey))
                                ->getToken();

        $headers['Authorization'] = 'Bearer ' . $token;

        $privKeySerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer());
        $privateKeyObject = $privKeySerializer->parse($privateKey);
        $publicKeyObject = $privateKeyObject->getPublicKey();
        $pointSerializer = new UncompressedPointSerializer(EccFactory::getAdapter());
        $headers['Crypto-Key'] = 'p256ecdsa=' . Base64Url::encode(hex2bin($pointSerializer->serialize($publicKeyObject->getPoint())));
      }
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
      $handleToReq = array();
      foreach ($this->requests as $request) {
        $handle = $this->httpCurlMulti->createHandle($request['url'], array(
          'headers' => $request['headers'],
          'body' => $request['body'],
          'method' => 'POST',
        ));
        $handles[] = $handle;
        $handleToReq[intval($handle)] = $request;
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
          $handle = $res['handle'];
          call_user_func($handleToReq[intval($handle)]['callback'], in_array(curl_getinfo($handle, CURLINFO_HTTP_CODE), array(200, 201)));
          curl_multi_remove_handle($mh, $handle);
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
