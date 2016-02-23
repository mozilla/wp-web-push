<?php

class HandleRegisterTest extends WP_UnitTestCase {
  function test_add_get_remove() {
    WebPush_DB::add_subscription('http://localhost/1', 'aKey1');
    WebPush_DB::add_subscription('http://localhost/2', 'aKey2');

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(count($subscriptions), 2);
    $this->assertEquals($subscriptions[0]->endpoint, 'http://localhost/1');
    $this->assertEquals($subscriptions[0]->userKey, 'aKey1');
    $this->assertEquals($subscriptions[1]->endpoint, 'http://localhost/2');
    $this->assertEquals($subscriptions[1]->userKey, 'aKey2');

    WebPush_DB::remove_subscription('http://localhost/1');

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(count($subscriptions), 1);
    $this->assertEquals($subscriptions[0]->endpoint, 'http://localhost/2');
    $this->assertEquals($subscriptions[0]->userKey, 'aKey2');

    WebPush_DB::remove_subscription('http://localhost/2');

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(count($subscriptions), 0);
  }
}

?>
