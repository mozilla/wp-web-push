<?php

require_once(plugin_dir_path(__FILE__) . 'web-push.php' );
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');

class WebPush_Main {
  private static $instance;

  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    add_action('wp_ajax_nopriv_webpush_register', array($this, 'handle_webpush_register'));

    add_action('transition_post_status', array($this, 'on_transition_post_status'), 10, 3);
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

    WebPush_DB::add_subscription($_POST['endpoint'], $_POST['key']);

    wp_die();
  }

  public static function on_transition_post_status($new_status, $old_status, $post) {
    if (empty($post) || $new_status !== "publish") {
      return;
    }

    // TODO: Send notification
  }
}

?>
