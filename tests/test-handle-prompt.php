<?php

class HandlePromptTest extends WP_Ajax_UnitTestCase {
  function test_prompt() {
    try {
      $this->_handleAjax('nopriv_webpush_prompt');
      $this->assertTrue(false);
    } catch (WPAjaxDieStopException $e) {
      $this->assertTrue(true);
    }

    $this->assertEquals(get_option('webpush_prompt_count'), 1);
  }
}

?>
