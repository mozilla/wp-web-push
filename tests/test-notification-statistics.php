<?php

error_reporting(E_ALL ^ E_DEPRECATED);

class NotificationStatisticsTest extends WP_UnitTestCase {
  function test_query_vars() {
    $this->assertContains('webpush_post_id', WebPush_Main::on_query_vars(array()));
  }

  function test_dont_overwrite_query_vars() {
    $query_vars = WebPush_Main::on_query_vars(array('an_already_existing_query_var'));
    $this->assertContains('webpush_post_id', $query_vars);
    $this->assertContains('an_already_existing_query_var', $query_vars);
  }

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

    $this->assertEquals(1, get_post_meta($postID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($postID, '_notifications_sent', true));

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(2, get_post_meta($postID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($postID, '_notifications_sent', true));
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

    $this->assertEquals('', get_post_meta(99999, '_notifications_clicked', true));
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

    $this->assertEquals(1, get_post_meta($post1ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post1ID, '_notifications_sent', true));
    $this->assertEquals(0, get_post_meta($post2ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post2ID, '_notifications_sent', true));
    $this->assertEquals(0, get_post_meta($post3ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post3ID, '_notifications_sent', true));

    WebPush_Main::on_parse_request($query);

    $this->assertEquals(2, get_post_meta($post1ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post1ID, '_notifications_sent', true));
    $this->assertEquals(0, get_post_meta($post2ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post2ID, '_notifications_sent', true));
    $this->assertEquals(0, get_post_meta($post3ID, '_notifications_clicked', true));
    $this->assertEquals(0, get_post_meta($post3ID, '_notifications_sent', true));
  }
}

?>
