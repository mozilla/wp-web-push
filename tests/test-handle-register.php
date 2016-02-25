<?php

class HandleRegisterTest extends WP_Ajax_UnitTestCase {
  function test_new_registration() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';

    try {
      $this->_handleAjax('nopriv_webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(count($subscriptions), 1);
    $this->assertEquals($subscriptions[0]->endpoint, 'http://localhost');
    $this->assertEquals($subscriptions[0]->userKey, 'aKey');

    $this->assertEquals(get_option('webpush_accepted_prompt_count'), 0);
  }

  function test_reregistration() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';
    $_POST['newRegistration'] = 'true';

    try {
      $this->_handleAjax('nopriv_webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(count($subscriptions), 1);
    $this->assertEquals($subscriptions[0]->endpoint, 'http://localhost');
    $this->assertEquals($subscriptions[0]->userKey, 'aKey');

    $this->assertEquals(get_option('webpush_accepted_prompt_count'), 1);
  }
}

?>
