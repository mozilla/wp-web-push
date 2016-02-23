<?php

class SubscriptionButtonTest extends WP_UnitTestCase {
  function setUp() {
    parent::setUp();
    remove_all_actions('wp_footer');
  }

  function tearDown() {
    parent::tearDown();
    update_option('webpush_subscription_button', true);
  }

  function test_registers_action() {
    $main = new WebPush_Main();
    $this->assertTrue(has_action('wp_footer'));
  }

  function test_doesnt_register_action() {
    update_option('webpush_subscription_button', false);

    $main = new WebPush_Main();
    $this->assertFalse(has_action('wp_footer'));
  }

  function test_action_doesnt_throw() {
    $main = new WebPush_Main();
    do_action('wp_footer');
  }
}

?>
