<?php

class TransitionPostStatusTest extends WP_UnitTestCase {
  function setUp() {
    parent::setUp();

    WebPush_DB::add_subscription('endpoint', 'aKey');

    $_REQUEST['webpush_meta_box_nonce'] = wp_create_nonce('webpush_send_notification');
    $_REQUEST['webpush_send_notification'] = 1;
  }

  function tearDown() {
    parent::tearDown();
    remove_all_filters('pre_http_request');
  }

  function test_empty_post() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', null);
  }

  function test_non_post() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    $post = get_post($this->factory->post->create(array('post_type' => 'page')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);
  }

  function test_non_published() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('draft', 'draft', $post);
  }

  function test_nonce_not_set() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    unset($_REQUEST['webpush_meta_box_nonce']);

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);
  }

  function test_invalid_nonce() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    $_REQUEST['webpush_meta_box_nonce'] = 'invalid';

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);
  }

  function test_checkbox_not_set() {
    add_filter('pre_http_request', function() {
      $self->assertTrue(false);
    });

    unset($_REQUEST['webpush_send_notification']);

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);
  }

  function test_success() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_custom_title() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    update_option('webpush_title', 'A Custom Title');

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('A Custom Title', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_custom_icon() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    update_option('webpush_icon', 'https://www.mozilla.org/icon.svg');

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('https://www.mozilla.org/icon.svg', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_no_icon() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    update_option('webpush_icon', '');

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_post_thumbnail_icon() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    update_option('webpush_icon', 'post_icon');

    $attachment_post = $this->factory->post->create(array('post_status' => 'publish'));
    $attachment_id = $this->factory->attachment->create_object('42.png', $attachment_post, array(
      'post_mime_type' => 'image/png',
    ));
    $postID = $this->factory->post->create(array('post_title' => 'Test Post Title'));
    set_post_thumbnail($postID, $attachment_id);
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', get_post($postID));

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('http://example.org/wp-content/uploads/42.png', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $postID, $payload['url']);
    $this->assertEquals($postID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($postID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($postID, '_notifications_sent', true));
  }

  function test_success_site_icon() {
    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    update_option('webpush_icon', 'blog_icon');

    $attachment_post = $this->factory->post->create(array('post_status' => 'publish'));
    $attachment_id = $this->factory->attachment->create_object('marco.png', $attachment_post, array(
      'post_mime_type' => 'image/png',
    ));
    update_option('site_icon', $attachment_id);

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('http://example.org/wp-content/uploads/marco.png', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_multiple_subscribers() {
    WebPush_DB::add_subscription('endpoint2', 'aKey2');

    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(2, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_no_gcm_key() {
    WebPush_DB::add_subscription('https://android.googleapis.com/gcm/send/endpoint', '');

    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      $self->assertTrue(true);

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

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    // Test that the GCM endpoint isn't removed.
    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(2, count($subscriptions));
    $this->assertEquals('endpoint', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);
    $this->assertEquals('https://android.googleapis.com/gcm/send/endpoint', $subscriptions[1]->endpoint);
    $this->assertEquals('', $subscriptions[1]->userKey);
  }

  function test_success_with_gcm_key() {
    WebPush_DB::add_subscription('https://android.googleapis.com/gcm/send/endpoint', '');

    $self = $this;
    add_filter('pre_http_request', function($url, $r) use ($self) {
      $self->assertTrue(true);

      $code = isset($r['headers']['TTL']) ? 201 : 200;

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => $code,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    }, 10, 2);

    update_option('webpush_gcm_key', 'ASD');

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(2, get_post_meta($post->ID, '_notifications_sent', true));
  }

  function test_success_remove_invalid_subscription() {
    global $i;
    $i = 0;

    WebPush_DB::add_subscription('endpoint2', 'aKey2');

    $self = $this;
    add_filter('pre_http_request', function() use ($self) {
      global $i;

      $self->assertTrue(true);

      $code = 201;
      if ($i++ === 1) {
        $code = 404;
      }

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => $code,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    // Test that the invalid subscription gets removed.
    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('endpoint', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);
  }
}

?>
