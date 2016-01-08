<?php

class WebPush_Main {
  private static $instance;

  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function enqueue_frontend_scripts() {
    wp_register_script('sw-registration-script', plugins_url('lib/js/sw-manager.js', __FILE__ ));
    wp_localize_script('sw-registration-script', 'ServiceWorker', array(
      'url' => plugins_url('lib/js/sw.js', __FILE__),
    ));
    wp_enqueue_script('sw-registration-script');
  }
}

?>
