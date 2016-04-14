<?php

require_once dirname(dirname(__FILE__)) . '/build/web-push.php';

class SendNotificationTest extends WP_UnitTestCase {
  public static function setUpBeforeClass() {
    WP_UnitTestCase::setUpBeforeClass();
    error_reporting(E_ALL ^ E_DEPRECATED);
  }
  public static function tearDownAfterClass() {
    error_reporting(E_ALL);
    WP_UnitTestCase::tearDownAfterClass();
  }

  function test_send_notification_error() {
    $oldNum = getSentNotificationNum();

    add_filter('pre_http_request', function() {
      return new WP_Error('Error');
    });

    $webPush = new WebPush(true);
    $self = $this;
    $webPush->addRecipient('endpoint', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  /*function send_multiple_notifications_success($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $webPush->setGCMKey('aKey');
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->addRecipient('http://localhost:55555/400', function($success) use ($self) {
      $self->assertFalse($success);
    });
    $webPush->addRecipient('http://localhost:55555/201', function($success) use ($self) {
      $self->assertTrue($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/200/gcm';

    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 3, getSentNotificationNum());
  }

  function test_send_multiple_notifications_success() {
    $this->send_multiple_notifications_success(true);
  }*/

  function send_webpush_notification_success_with_VAPID($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $webPush->setVAPIDInfo(file_get_contents('tests/example_ec_key_with_public_key.pem'), 'https://example.org', 'mailto:webpush_ops@catfacts.example.com');
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/201' . (USE_VAPID ? '//vapid' : ''), function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_webpush_notification_success_with_VAPID() {
    $this->send_webpush_notification_success_with_VAPID(true);
  }
}

?>
