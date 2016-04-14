<?php

class HandlePromptTest extends WP_Ajax_UnitTestCase {
  public static function setUpBeforeClass() {
    WP_Ajax_UnitTestCase::setUpBeforeClass();
    error_reporting(E_ALL ^ E_DEPRECATED);
  }

  public static function tearDownAfterClass() {
    error_reporting(E_ALL);
    WP_Ajax_UnitTestCase::tearDownAfterClass();
  }

  function test_prompt() {
    try {
      $this->_handleAjax('nopriv_webpush_prompt');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals(1, get_option('webpush_prompt_count'));
  }

  function test_prompt_priv() {
    try {
      $this->_handleAjax('webpush_prompt');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals(1, get_option('webpush_prompt_count'));
  }
}

?>
