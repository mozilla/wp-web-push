<?php

require_once dirname(dirname(__FILE__)) . '/build/wp-web-push-admin.php';

class HandleGetPublicKeyTest extends WP_Ajax_UnitTestCase {
  function setUp() {
    parent::setUp();
    new WebPush_Admin();
  }

  function test_get_public_key_no_priv() {
    try {
      $this->_handleAjax('nopriv_webpush_get_public_key');
      $this->assertTrue(true);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(false);
    }

    $this->assertEquals('', $this->_last_response);
  }

  function test_get_public_key() {
    wp_set_current_user(1);

    $_POST['_ajax_nonce'] = wp_create_nonce('vapid_nonce');
    $_POST['privateKey'] = '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIM84un6Hdk08ulYyxK3F+AK6DqYfvn0HHXTQo0Oey4ZxoAoGCCqGSM49
AwEHoUQDQgAEponEt39Fh27E5mz0DwUTT3PrJS67l+oeTdXg3KmIEXxjTmVsbOTq
IynAAx6EGnJX/9UbNS7oY8XJVkIkgtz4Eg==
-----END EC PRIVATE KEY-----';

    try {
      $this->_handleAjax('webpush_get_public_key');
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('BKaJxLd_RYduxOZs9A8FE09z6yUuu5fqHk3V4NypiBF8Y05lbGzk6iMpwAMehBpyV__VGzUu6GPFyVZCJILc-BI', $this->_last_response);
  }

  function test_get_public_key_without_private_key() {
    $_POST['_ajax_nonce'] = wp_create_nonce('vapid_nonce');

    try {
      $this->_handleAjax('webpush_get_public_key');
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('Your private key is invalid.', $this->_last_response);
  }

  function test_get_public_key_invalid_private_key() {
    $_POST['_ajax_nonce'] = wp_create_nonce('vapid_nonce');
    $_POST['privateKey'] = '-----BEGIN EC PRIVATE KEY-----
invalid
-----END EC PRIVATE KEY-----';

    try {
      $this->_handleAjax('webpush_get_public_key');
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('Your private key is invalid.', $this->_last_response);
  }

  function test_get_public_key_without_nonce() {
    $_POST['privateKey'] = '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIM84un6Hdk08ulYyxK3F+AK6DqYfvn0HHXTQo0Oey4ZxoAoGCCqGSM49
AwEHoUQDQgAEponEt39Fh27E5mz0DwUTT3PrJS67l+oeTdXg3KmIEXxjTmVsbOTq
IynAAx6EGnJX/9UbNS7oY8XJVkIkgtz4Eg==
-----END EC PRIVATE KEY-----';

    try {
      $this->_handleAjax('webpush_get_public_key');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('', $this->_last_response);
  }

  function test_get_public_key_invalid_nonce() {
    $_POST['_ajax_nonce'] = 'invalid';
    $_POST['privateKey'] = '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIM84un6Hdk08ulYyxK3F+AK6DqYfvn0HHXTQo0Oey4ZxoAoGCCqGSM49
AwEHoUQDQgAEponEt39Fh27E5mz0DwUTT3PrJS67l+oeTdXg3KmIEXxjTmVsbOTq
IynAAx6EGnJX/9UbNS7oY8XJVkIkgtz4Eg==
-----END EC PRIVATE KEY-----';

    try {
      $this->_handleAjax('webpush_get_public_key');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('', $this->_last_response);
  }
}

?>
