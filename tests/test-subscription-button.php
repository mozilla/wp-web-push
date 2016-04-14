<?php

class SubscriptionButtonTest extends WP_UnitTestCase {
  function setUp() {
    parent::setUp();
    remove_all_actions('wp_footer');
    wp_dequeue_style('subscription-button-style');
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

  function test_subscription_style_enqueued() {
    $main = new WebPush_Main();
    do_action('wp_enqueue_scripts');
    $this->assertTrue(wp_style_is('subscription-button-style'));
  }

  function test_subscription_style_not_enqueued() {
    update_option('webpush_subscription_button', false);

    $main = new WebPush_Main();
    do_action('wp_enqueue_scripts');
    $this->assertFalse(wp_style_is('subscription-button-style'));
  }
}

?>
