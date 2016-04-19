<?php

use Base64Url\Base64Url;

class HandleRegisterTest extends WP_Ajax_UnitTestCase {
  function test_new_registration() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';
    $_POST['auth'] = '89IdFKBhvi9H5LlvawK9Iw==';

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
    $this->assertEquals('89IdFKBhvi9H5LlvawK9Iw', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_priv() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';
    $_POST['auth'] = '89IdFKBhvi9H5LlvawK9Iw==';

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
    $this->assertEquals('89IdFKBhvi9H5LlvawK9Iw', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_reregistration() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';
    $_POST['auth'] = '89IdFKBhvi9H5LlvawK9Iw==';
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
    $this->assertEquals('89IdFKBhvi9H5LlvawK9Iw', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(1, get_option('webpush_accepted_prompt_count'));
  }

  function test_reregistration_with_removal() {
    $_POST['endpoint'] = 'http://localhost/1';
    $_POST['key'] = 'aKey';
    $_POST['auth'] = '89IdFKBhvi9H5LlvawK9Iw==';
    $_POST['newRegistration'] = 'true';
    $_POST['oldEndpoint'] = 'http://localhost/2';

    WebPush_DB::add_subscription('http://localhost/2', '', '');

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
    $this->assertEquals('89IdFKBhvi9H5LlvawK9Iw', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(1, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_with_auth_empty_string() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = 'aKey';
    $_POST['auth'] = '';

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
    $this->assertEquals('AAAAAAAAAAAAAAAAAAAAAA', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_without_auth() {
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
    $this->assertEquals('AAAAAAAAAAAAAAAAAAAAAA', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_with_key_and_auth_empty_strings() {
    $_POST['endpoint'] = 'http://localhost';
    $_POST['key'] = '';
    $_POST['auth'] = '';

    try {
      $this->_handleAjax('nopriv_webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost', $subscriptions[0]->endpoint);
    $this->assertEquals('', $subscriptions[0]->userKey);
    $this->assertEquals('AAAAAAAAAAAAAAAAAAAAAA', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }

  function test_new_registration_without_key_and_auth() {
    $_POST['endpoint'] = 'http://localhost';

    try {
      $this->_handleAjax('nopriv_webpush_register');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost', $subscriptions[0]->endpoint);
    $this->assertEquals('', $subscriptions[0]->userKey);
    $this->assertEquals('AAAAAAAAAAAAAAAAAAAAAA', Base64Url::encode($subscriptions[0]->userAuth));

    $this->assertEquals(0, get_option('webpush_accepted_prompt_count'));
  }
}

?>
