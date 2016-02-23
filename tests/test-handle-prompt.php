<?php

class HandlePromptTest extends WP_UnitTestCase {
  function wp_die_handler($message) {
    // Ignore wp_die.
  }

  function test_prompt() {
    WebPush_Main::handle_prompt();
    $this->assertEquals(get_option('webpush_prompt_count'), 1);
  }
}

?>
