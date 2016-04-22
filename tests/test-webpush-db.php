<?php

use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;

class WebPushDBTest extends WP_UnitTestCase {
  function test_add_get_remove() {
    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/1'));
    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/2'));

    WebPush_DB::add_subscription('http://localhost/1', 'aKey1', '89IdFKBhvi9H5LlvawK9Iw==');

    $this->assertTrue(WebPush_DB::is_subscription('http://localhost/1'));
    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/2'));

    WebPush_DB::add_subscription('http://localhost/2', 'aKey2', '89IdFKBhvi9H5LlvawK9Iw==');

    $this->assertTrue(WebPush_DB::is_subscription('http://localhost/1'));
    $this->assertTrue(WebPush_DB::is_subscription('http://localhost/2'));

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(2, count($subscriptions));
    $this->assertEquals('http://localhost/1', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey1', $subscriptions[0]->userKey);
    $this->assertEquals('http://localhost/2', $subscriptions[1]->endpoint);
    $this->assertEquals('aKey2', $subscriptions[1]->userKey);

    WebPush_DB::remove_subscription('http://localhost/1');

    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/1'));
    $this->assertTrue(WebPush_DB::is_subscription('http://localhost/2'));

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost/2', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey2', $subscriptions[0]->userKey);

    WebPush_DB::remove_subscription('http://localhost/2');

    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/1'));
    $this->assertFalse(WebPush_DB::is_subscription('http://localhost/2'));

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(0, count($subscriptions));
  }

  function test_generate_vapid_options() {
    if (!USE_VAPID) {
      return;
    }

    // Test that when the plugin is installed it has valid VAPID info.

    $privKeySerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer());
    $privateKeyObject = $privKeySerializer->parse(get_option('webpush_vapid_key'));
    $publicKeyObject = $privateKeyObject->getPublicKey();

    $this->assertEquals('mailto:admin@example.org', get_option('webpush_vapid_subject'));
    $this->assertEquals('https://example.org', get_option('webpush_vapid_audience'));

    // Test regenerating the VAPID info.

    update_option('webpush_vapid_key', '');
    update_option('webpush_vapid_subject', '');
    update_option('webpush_vapid_audience', '');

    WebPush_DB::generate_vapid_options();

    $privKeySerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer());
    $privateKeyObject = $privKeySerializer->parse(get_option('webpush_vapid_key'));
    $publicKeyObject = $privateKeyObject->getPublicKey();

    $this->assertEquals('mailto:admin@example.org', get_option('webpush_vapid_subject'));
    $this->assertEquals('https://example.org', get_option('webpush_vapid_audience'));
  }
}

?>
