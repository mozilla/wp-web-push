<?php

class NotificationStatisticsTest extends WP_UnitTestCase {
  function test_update_notifications_clicked() {
    $postID = $this->factory->post->create();
    update_post_meta($postID, '_notifications_clicked', 0);
    update_post_meta($postID, '_notifications_sent', 0);

    $query = (object)array(
      'query_vars' => array(
        'webpush_post_id' => $postID,
      ),
    );

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(get_post_meta($postID, '_notifications_clicked', true), 1);
    $this->assertEquals(get_post_meta($postID, '_notifications_sent', true), 0);

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(get_post_meta($postID, '_notifications_clicked', true), 2);
    $this->assertEquals(get_post_meta($postID, '_notifications_sent', true), 0);
  }

  function test_dont_update_notifications_clicked_if_not_number() {
    $query = (object)array(
      'query_vars' => array(
        'webpush_post_id' => 'asd',
      ),
    );

    WebPush_Main::on_parse_request($query);
  }

  function test_dont_update_notifications_clicked_if_not_a_post() {
    $query = (object)array(
      'query_vars' => array(
        'webpush_post_id' => 99999,
      ),
    );

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(get_post_meta(99999, '_notifications_clicked', true), '');
  }

  function test_dont_update_notifications_clicked_if_query_doesnt_contain_required_param() {
    $query = (object)array(
      'query_vars' => array(),
    );

    WebPush_Main::on_parse_request($query);
  }

  function test_update_notifications_clicked_on_correct_post() {
    $post1ID = $this->factory->post->create();
    update_post_meta($post1ID, '_notifications_clicked', 0);
    update_post_meta($post1ID, '_notifications_sent', 0);

    $post2ID = $this->factory->post->create();
    update_post_meta($post2ID, '_notifications_clicked', 0);
    update_post_meta($post2ID, '_notifications_sent', 0);

    $post3ID = $this->factory->post->create();
    update_post_meta($post3ID, '_notifications_clicked', 0);
    update_post_meta($post3ID, '_notifications_sent', 0);

    $query = (object)array(
      'query_vars' => array(
        'webpush_post_id' => $post1ID,
      ),
    );

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(get_post_meta($post1ID, '_notifications_clicked', true), 1);
    $this->assertEquals(get_post_meta($post1ID, '_notifications_sent', true), 0);
    $this->assertEquals(get_post_meta($post2ID, '_notifications_clicked', true), 0);
    $this->assertEquals(get_post_meta($post2ID, '_notifications_sent', true), 0);
    $this->assertEquals(get_post_meta($post3ID, '_notifications_clicked', true), 0);
    $this->assertEquals(get_post_meta($post3ID, '_notifications_sent', true), 0);

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(get_post_meta($post1ID, '_notifications_clicked', true), 2);
    $this->assertEquals(get_post_meta($post1ID, '_notifications_sent', true), 0);
    $this->assertEquals(get_post_meta($post2ID, '_notifications_clicked', true), 0);
    $this->assertEquals(get_post_meta($post2ID, '_notifications_sent', true), 0);
    $this->assertEquals(get_post_meta($post3ID, '_notifications_clicked', true), 0);
    $this->assertEquals(get_post_meta($post3ID, '_notifications_sent', true), 0);
  }
}

?>
