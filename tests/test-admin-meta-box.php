<?php

require_once dirname( dirname( __FILE__ ) ) . '/build/wp-web-push-admin.php';

class AdminMetaBoxTest extends WP_UnitTestCase {
  function test_checked_draft_with_update_and_new() {
    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'draft'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertContains('checked', $box);
  }

  function test_checked_publish_with_update_and_new() {
    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'publish'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertContains('checked', $box);
  }

  function test_checked_draft_with_no_update_and_new() {
    update_option('webpush_triggers', array('new-post'));

    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'draft'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertContains('checked', $box);
  }

  function test_checked_publish_with_no_update_and_new() {
    update_option('webpush_triggers', array('new-post'));

    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'publish'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertNotContains('checked', $box);
  }

  function test_checked_draft_with_no_update_and_no_new() {
    update_option('webpush_triggers', array());

    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'draft'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertNotContains('checked', $box);
  }

  function test_checked_publish_with_no_update_and_no_new() {
    update_option('webpush_triggers', array());

    $admin = new WebPush_Admin();
    $post = new WP_Post((object)array('post_status' => 'publish'));
    $box = get_echo(array($admin, 'meta_box'), array($post));
    $this->assertNotContains('checked', $box);
  }
}

?>
