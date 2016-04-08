<?php

require_once dirname(dirname(__FILE__)) . '/build/web-push.php';

class SendNotificationTest extends WP_UnitTestCase {
  function test_send_webpush_notification_success() {
    $webPush = new WebPush();
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/201', false, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_webpush_notification_success_no_key() {
    $webPush = new WebPush();
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/201', false, '', function($success) use ($self) {
      $self->assertTrue($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_webpush_notification_failure() {
    $webPush = new WebPush();
    $self = $this;
    $webPush->addRecipient('http://localhost:55555/400', false, 'aKey', function($success) use ($self) {
      $self->assertFalse($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_gcm_notification_success() {
    $webPush = new WebPush();
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) use ($self) {
      $self->assertTrue($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/200/gcm';

    $webPush->sendNotifications();
  }

  function test_send_gcm_notification_failure() {
    $webPush = new WebPush();
    $self = $this;
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) use ($self) {
      $self->assertFalse($success);
    });

    $webPush->requests[0]['url'] = 'http://localhost:55555/400/gcm';

    $webPush->sendNotifications();
  }

  /*function test_send_notification_error() {
    add_filter('pre_http_request', function() {
      return new WP_Error('Error');
    });

    $webPush = new WebPush();
    $webPush->addRecipient('endpoint', false, 'aKey', function($success) {
      $this->assertTrue($success);
    });
    $webPush->sendNotifications();
  }*/
}

?>
