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
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_priv() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';

    try {
      $this->_handleAjax('webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
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
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);

    $this->assertEquals(1, get_option('webpush_accepted_prompt_count'));
  }

  function test_reregistration_with_removal() {
    $_POST['endpoint'] = 'http://localhost/1';
    $_POST['key'] = 'aKey';
    $_POST['newRegistration'] = 'true';
    $_POST['oldEndpoint'] = 'http://localhost/2';

    WebPush_DB::add_subscription('http://localhost/2', '');

    try {
      $this->_handleAjax('nopriv_webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost/1', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);

    $this->assertEquals(1, get_option('webpush_accepted_prompt_count'));
  }
}

?>
