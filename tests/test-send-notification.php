<?php

require_once dirname(dirname(__FILE__)) . '/build/web-push.php';

class SendNotificationTest extends WP_UnitTestCase {
  function send_webpush_notification_success($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/201', false, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_webpush_notification_success() {
    $this->send_webpush_notification_success(false);
    $this->send_webpush_notification_success(true);
  }

  function send_webpush_notification_success_no_key($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/201', false, '', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_webpush_notification_success_no_key() {
    $this->send_webpush_notification_success_no_key(false);
    $this->send_webpush_notification_success_no_key(true);
  }

  function send_webpush_notification_failure($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/400', false, 'aKey', function($success) use ($self) {
      $self->assertFalse($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_webpush_notification_failure() {
    $this->send_webpush_notification_failure(false);
    $this->send_webpush_notification_failure(true);
  }

  function send_gcm_notification_success($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/200/gcm';

    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_gcm_notification_success() {
    $this->send_gcm_notification_success(false);
    $this->send_gcm_notification_success(true);
  }

  function send_gcm_notification_failure($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) use ($self) {
      $self->assertFalse($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/400/gcm';

    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_send_gcm_notification_failure() {
    $this->send_gcm_notification_failure(false);
    $this->send_gcm_notification_failure(true);
  }

  function test_send_notification_error() {
    $oldNum = getSentNotificationNum();

    add_filter('pre_http_request', function() {
      return new WP_Error('Error');
    });

    $webPush = new WebPush(true);
    $self = $this;
    $webPush->addRecipient('endpoint', false, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function send_multiple_notifications_success($forceWP) {
    $oldNum = getSentNotificationNum();

    $webPush = new WebPush($forceWP);
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->addRecipient('http://localhost:55555/400', false, 'aKey', function($success) use ($self) {
      $self->assertFalse($success);
    });
    $webPush->addRecipient('http://localhost:55555/201', false, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/200/gcm';

    $webPush->sendNotifications();

    $this->assertEquals($oldNum + 3, getSentNotificationNum());
  }

  function test_send_multiple_notifications_success() {
    $this->send_multiple_notifications_success(false);
    $this->send_multiple_notifications_success(true);
  }
}

?>
