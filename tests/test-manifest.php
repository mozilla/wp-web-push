<?php

// TODO: Some of these tests should be moved to the WebAppManifestGenerator repository (https://github.com/marco-c/wp-web-app-manifest-generator).

class ManifestTest extends WP_Ajax_UnitTestCase {
  function test_generate_manifest_link() {
    $this->assertEquals('<link rel="manifest" href="/?webappmanifest_file=manifest">', get_echo(array(WebAppManifestGenerator::getInstance(), 'add_manifest')));
  }

  function test_manifest_request_no_gcm_sender_id() {
    $main = new WebPush_Main();

    $query = (object)array(
      'query_vars' => array(
        'webappmanifest_file' => 'manifest',
      ),
    );

    $result = '';
    try {
      $result += get_echo(array(WebAppManifestGenerator::getInstance(), 'on_parse_request'), array($query));
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('{"start_url":"\/"}', $this->_last_response);
  }

  function test_manifest_request() {
    update_option('webpush_gcm_sender_id', '42');

    $main = new WebPush_Main();

    $query = (object)array(
      'query_vars' => array(
        'webappmanifest_file' => 'manifest',
      ),
    );

    $result = '';
    try {
      $result += get_echo(array(WebAppManifestGenerator::getInstance(), 'on_parse_request'), array($query));
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('{"start_url":"\/","gcm_sender_id":"42","gcm_user_visible_only":true}', $this->_last_response);
  }

  function test_manifest_request_multiple_users() {
    update_option('webpush_gcm_sender_id', '42');

    require dirname(dirname(__FILE__)) . '/build/vendor/marco-c/wp-web-app-manifest-generator/WebAppManifestGenerator.php';

    WebAppManifestGenerator::getInstance()->set_field('test', 'Marco');

    $main = new WebPush_Main();

    $query = (object)array(
      'query_vars' => array(
        'webappmanifest_file' => 'manifest',
      ),
    );

    $result = '';
    try {
      $result += get_echo(array(WebAppManifestGenerator::getInstance(), 'on_parse_request'), array($query));
      $this->assertTrue(false);
    } catch (WPAjaxDieContinueException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals('{"start_url":"\/","gcm_sender_id":"42","gcm_user_visible_only":true,"test":"Marco"}', $this->_last_response);
  }

  function test_dont_echo_manifest_if_query_doesnt_contain_required_param() {
    $query = (object)array(
      'query_vars' => array(),
    );

    WebAppManifestGenerator::getInstance()->on_parse_request($query);
  }

  function test_query_vars() {
    $this->assertContains('webappmanifest_file', WebAppManifestGenerator::getInstance()->on_query_vars(array()));
  }

  function test_dont_overwrite_query_vars() {
    $query_vars = WebAppManifestGenerator::getInstance()->on_query_vars(array('an_already_existing_query_var'));
    $this->assertContains('webappmanifest_file', $query_vars);
    $this->assertContains('an_already_existing_query_var', $query_vars);
  }
}

?>
