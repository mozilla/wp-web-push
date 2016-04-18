<?php

class TransitionPostStatusTest extends WP_UnitTestCase {
  function setUp() {
    parent::setUp();

    WebPush_DB::add_subscription('http://localhost:55555/201', 'aKey');

    $_REQUEST['webpush_meta_box_nonce'] = wp_create_nonce('webpush_send_notification');
    $_REQUEST['webpush_send_notification'] = 1;

    remove_all_actions('transition_post_status');
  }

  function test_empty_post() {
    $oldNum = getSentNotificationNum();

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', null);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_non_post() {
    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create(array('post_type' => 'page')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_non_published() {
    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('draft', 'draft', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_invalid_nonce() {
    $oldNum = getSentNotificationNum();

    $_REQUEST['webpush_meta_box_nonce'] = 'invalid';

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_checkbox_not_set() {
    $oldNum = getSentNotificationNum();

    unset($_REQUEST['webpush_send_notification']);

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals('d', get_post_meta($post->ID, '_notifications_enabled', true));

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_checkbox_not_set_not_published() {
    $oldNum = getSentNotificationNum();

    unset($_REQUEST['webpush_send_notification']);

    $post = get_post($this->factory->post->create());
    $main = new WebPush_Main();
    $main->on_transition_post_status('draft', 'draft', $post);

    $this->assertEquals('d', get_post_meta($post->ID, '_notifications_enabled', true));

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_checkbox_not_set_and_then_set() {
    $oldNum = getSentNotificationNum();

    unset($_REQUEST['webpush_send_notification']);

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals('d', get_post_meta($post->ID, '_notifications_enabled', true));

    $this->assertEquals($oldNum, getSentNotificationNum());

    $_REQUEST['webpush_send_notification'] = 1;
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals('', get_post_meta($post->ID, '_notifications_enabled', true));

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success() {
    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals('', get_post_meta($post->ID, '_notifications_enabled', true));

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_nonce_not_set_but_meta_not_set() {
    $oldNum = getSentNotificationNum();

    unset($_REQUEST['webpush_meta_box_nonce']);
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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_nonce_not_set_but_meta_set_to_disabled() {
    $oldNum = getSentNotificationNum();

    unset($_REQUEST['webpush_meta_box_nonce']);
    $post = get_post($this->factory->post->create());
    update_post_meta($post->ID, '_notifications_enabled', 'd');

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_nonce_invalid_but_meta_not_set() {
    $oldNum = getSentNotificationNum();

    $_REQUEST['webpush_meta_box_nonce'] = 'invalid';
    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_success_scheduled() {
    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('scheduled', 'draft', $post);

    $this->assertEquals('', get_post_meta($post->ID, '_notifications_enabled', true));

    unset($_REQUEST['webpush_meta_box_nonce']);
    $main->on_transition_post_status('publish', 'scheduled', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_quickedit_updates_enabled() {
    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    unset($_REQUEST['webpush_meta_box_nonce']);
    $main->on_transition_post_status('publish', 'publish', $post);

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(1, get_post_meta($post->ID, '_notifications_sent', true));

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_quickedit_updates_disabled() {
    update_option('webpush_triggers', array('new-post'));

    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create());
    unset($_REQUEST['webpush_meta_box_nonce']);

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum + 1, getSentNotificationNum());

    $main->on_transition_post_status('draft', 'publish', $post);

    $this->assertEquals($oldNum + 1, getSentNotificationNum());

    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_from_publish_updates_disabled() {
    update_option('webpush_triggers', array('new-post'));

    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create());
    unset($_REQUEST['webpush_meta_box_nonce']);

    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'publish', $post);

    $this->assertEquals($oldNum, getSentNotificationNum());
  }

  function test_success_with_vapid() {
    WebPush_DB::add_subscription('http://localhost:55555/200' . (USE_VAPID ? '//vapid' : ''), 'aKey3');

    update_option('webpush_vapid_key', file_get_contents('tests/example_ec_key_with_public_key.pem'));
    update_option('webpush_vapid_audience', 'https://example.org');
    update_option('webpush_vapid_subject', 'mailto:webpush_ops@catfacts.example.com');

    $oldNum = getSentNotificationNum();

    $post = get_post($this->factory->post->create(array('post_title' => 'Test Post Title')));
    $main = new WebPush_Main();
    $main->on_transition_post_status('publish', 'draft', $post);

    $this->assertEquals('', get_post_meta($post->ID, '_notifications_enabled', true));

    $payload = get_option('webpush_payload');
    $this->assertEquals('Test Blog', $payload['title']);
    $this->assertEquals('Test Post Title', $payload['body']);
    $this->assertEquals('', $payload['icon']);
    $this->assertEquals('http://example.org/?p=' . $post->ID, $payload['url']);
    $this->assertEquals($post->ID, $payload['postID']);
    $this->assertEquals(0, get_post_meta($post->ID, '_notifications_clicked', true));
    $this->assertEquals(2, get_post_meta($post->ID, '_notifications_sent', true));

    $this->assertEquals($oldNum + 2, getSentNotificationNum());
  }

  function test_success_custom_title() {
    $oldNum = getSentNotificationNum();

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_custom_icon() {
    $oldNum = getSentNotificationNum();

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_no_icon() {
    $oldNum = getSentNotificationNum();

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_post_thumbnail_icon() {
    $oldNum = getSentNotificationNum();

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_site_icon() {
    // Skip test on WP versions that don't have this function.
    if (!function_exists('get_site_icon_url')) {
      return;
    }

    $oldNum = getSentNotificationNum();

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());
  }

  function test_success_multiple_subscribers() {
    $oldNum = getSentNotificationNum();

    WebPush_DB::add_subscription('http://localhost:55555/200', 'aKey2');

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

    $this->assertEquals($oldNum + 2, getSentNotificationNum());
  }

  function test_success_no_gcm_key() {
    $oldNum = getSentNotificationNum();

    WebPush_DB::add_subscription('https://android.googleapis.com/gcm/send/endpoint', '');

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

    $this->assertEquals($oldNum + 1, getSentNotificationNum());

    // Test that the GCM endpoint isn't removed.
    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(2, count($subscriptions));
    $this->assertEquals('http://localhost:55555/201', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);
    $this->assertEquals('https://android.googleapis.com/gcm/send/endpoint', $subscriptions[1]->endpoint);
    $this->assertEquals('', $subscriptions[1]->userKey);
  }

  function test_success_remove_invalid_subscription() {
    $oldNum = getSentNotificationNum();

    WebPush_DB::add_subscription('http://localhost:55555/400', 'aKey2');

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

    $this->assertEquals($oldNum + 2, getSentNotificationNum());

    // Test that the invalid subscription gets removed.
    $subscriptions = WebPush_DB::get_subscriptions();
    $this->assertEquals(1, count($subscriptions));
    $this->assertEquals('http://localhost:55555/201', $subscriptions[0]->endpoint);
    $this->assertEquals('aKey', $subscriptions[0]->userKey);
  }
}

?>
