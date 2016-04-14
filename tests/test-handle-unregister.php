<?php

error_reporting(E_ALL ^ E_DEPRECATED);

class HandleUnregisterTest extends WP_Ajax_UnitTestCase {
  function test_unregister() {
    WebPush_DB::add_subscription('http://localhost', 'aKey');

    $_POST['endpoint'] = 'http://localhost';

    try {
      $this->_handleAjax('nopriv_webpush_unregister');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(0, count($subscriptions));
  }

  function test_unregister_priv() {
    WebPush_DB::add_subscription('http://localhost', 'aKey');

    $_POST['endpoint'] = 'http://localhost';

    try {
      $this->_handleAjax('webpush_unregister');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(0, count($subscriptions));
  }
}

?>
