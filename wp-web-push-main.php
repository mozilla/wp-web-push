<?php

class WebPush_Main {
  private static $instance;

  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    add_action('wp_ajax_nopriv_webpush_register', array($this, 'handle_webpush_register'));
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
      'register_url' => admin_url('admin-ajax.php'),
      // 'nonce' => wp_create_nonce('register_nonce'),
    ));
    wp_enqueue_script('sw-registration-script');
  }

  public function handle_webpush_register() {
    // TODO: Enable nonce verification.
    // check_ajax_referer('register_nonce');

    // TODO: Use $_POST['endpoint'] and $_POST['key']

    echo $_POST['endpoint'] . ' & ' . $_POST['key'];

    wp_die();
  }
}

?>
