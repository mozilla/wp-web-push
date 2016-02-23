<?php

class SendNotificationTest extends WP_UnitTestCase {
  function test_send_webpush_notification_success() {
    $self = $this;
    add_filter('pre_http_request', function($url, $r) use ($self) {
      $self->assertTrue($r['headers']['TTL'] > 0);

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 201,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    }, 10, 2);

    $this->assertTrue(sendNotification('endpoint', 'aKey'));

    remove_all_filters('pre_http_request');
  }

  function test_send_webpush_notification_success_no_key() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 201,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $this->assertTrue(sendNotification('endpoint', ''));

    remove_all_filters('pre_http_request');
  }

  function test_send_webpush_notification_failure() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 400,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $this->assertFalse(sendNotification('endpoint', 'aKey'));

    remove_all_filters('pre_http_request');
  }

  function test_send_gcm_notification_success() {
    $self = $this;
    add_filter('pre_http_request', function($url, $r) use ($self) {
      $self->assertEquals($r['headers']['Authorization'], 'key=aKey');
      $self->assertEquals($r['headers']['Content-Type'], 'application/json');
      $self->assertEquals($r['headers']['Content-Length'], 33);

      $data = json_decode($r['body']);
      $self->assertEquals(count($data->registration_ids), 1);
      $self->assertEquals($data->registration_ids[0], 'endpoint');

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 200,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    }, 10, 2);

    $this->assertTrue(sendNotification('https://android.googleapis.com/gcm/send/endpoint', 'aKey'));

    remove_all_filters('pre_http_request');
  }

  function test_send_gcm_notification_failure() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 400,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $this->assertFalse(sendNotification('https://android.googleapis.com/gcm/send/endpoint', 'aKey'));

    remove_all_filters('pre_http_request');
  }

  function test_send_gcm_notification_no_key() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(false);

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 201,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $this->assertTrue(sendNotification('https://android.googleapis.com/gcm/send/endpoint', ''));

    remove_all_filters('pre_http_request');
  }

  function test_send_notification_error() {
    add_filter('pre_http_request', function() {
      return new WP_Error('Error');
    });

    $this->assertTrue(sendNotification('endpoint', 'aKey'));

    remove_all_filters('pre_http_request');
  }
}

?>
