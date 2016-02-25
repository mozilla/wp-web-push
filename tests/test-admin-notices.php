<?php

require_once dirname( dirname( __FILE__ ) ) . '/build/wp-web-push-admin.php';

class AdminNoticesTest extends WP_UnitTestCase {
  function test_no_notice_for_normal_users() {
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertEquals('', $output);
  }

  function test_ssl_warning() {
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('HTTPS', $output);
  }

  function test_no_ssl_warning() {
    wp_set_current_user(1);

    $admin = $this->getMockBuilder('WebPush_Admin')
    ->setMethods(array('isSSL'))
    ->getMock();

    $admin->expects($this->once())
    ->method('isSSL')
    ->will($this->returnValue(true));

    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertNotContains('HTTPS', $output);
  }

  function test_gcm_warning() {
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('GCM', $output);
  }

  function test_gcm_warning_api_key_not_set() {
    update_option('webpush_gcm_key', '42');
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('GCM', $output);
  }

  function test_gcm_warning_sender_id_not_set() {
    update_option('webpush_gcm_sender_id', '42');
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('GCM', $output);
  }

  function test_gcm_warning_api_key_not_in_post() {
    update_option('webpush_gcm_key', '42');
    update_option('webpush_gcm_sender_id', '42');
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertNotContains('GCM', $output);
  }

  function test_gcm_warning_sender_id_not_in_post() {
    $_POST['webpush_gcm_key'] = '42';
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('GCM', $output);
  }

  function test_no_gcm_warning_options_set() {
    $_POST['webpush_gcm_sender_id'] = '42';
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('GCM', $output);
  }

  function test_no_gcm_warning_post_set() {
    $_POST['webpush_gcm_key'] = '42';
    $_POST['webpush_gcm_sender_id'] = '42';
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertNotContains('GCM', $output);
  }

  function test_gcm_and_ssl_warning() {
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('HTTPS', $output);
    $this->assertContains('GCM', $output);
  }

  function test_ssl_but_no_gcm_warning() {
    update_option('webpush_gcm_key', '42');
    update_option('webpush_gcm_sender_id', '42');
    wp_set_current_user(1);
    $admin = new WebPush_Admin();
    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertContains('HTTPS', $output);
    $this->assertNotContains('GCM', $output);
  }

  function test_gcm_but_no_ssl_warning() {
    wp_set_current_user(1);

    $admin = $this->getMockBuilder('WebPush_Admin')
    ->setMethods(array('isSSL'))
    ->getMock();

    $admin->expects($this->once())
    ->method('isSSL')
    ->will($this->returnValue(true));

    $output = get_echo(array($admin, 'on_admin_notices'));
    $this->assertNotContains('HTTPS', $output);
    $this->assertContains('GCM', $output);
  }
}

?>
