<?php

class WebPush_Admin {
  private static $instance;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function on_admin_menu() {
    add_options_page('Web Push Options', 'Web Push', 'manage_options', 'web-push-options', array($this, 'options'));
  }

  public function options() {
    echo '<div class="wrap">';
    echo '<p>Web Push form options.</p>';
    echo '</div>';
  }
}

?>
