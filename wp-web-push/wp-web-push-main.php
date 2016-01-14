<?php

require_once(plugin_dir_path(__FILE__) . 'web-push.php' );
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');

class WebPush_Main {
  private static $instance;

  public function __construct() {
    add_action('wp_head', array($this, 'add_manifest'));

    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    add_filter('query_vars', array($this, 'on_query_vars'), 10, 1);
    add_action('parse_request', array($this, 'on_parse_request'));

    add_action('wp_ajax_nopriv_webpush_register', array($this, 'handle_webpush_register'));
    add_action('wp_ajax_nopriv_webpush_get_payload', array($this, 'handle_webpush_get_payload'));

    add_action('transition_post_status', array($this, 'on_transition_post_status'), 10, 3);
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public static function add_manifest() {
    echo '<link rel="manifest" href="' . plugins_url('lib/manifest.json', __FILE__) . '">';
  }

  public function enqueue_frontend_scripts() {
    wp_enqueue_script('localforage-script', plugins_url('lib/js/localforage.min.js', __FILE__));

    wp_register_script('sw-manager-script', plugins_url('lib/js/sw-manager.js', __FILE__));
    wp_localize_script('sw-manager-script', 'ServiceWorker', array(
      'url' => home_url('/') . '?webpush_file=worker',
      'register_url' => admin_url('admin-ajax.php'),
      'min_visits' => get_option('webpush_min_visits'),
    ));
    wp_enqueue_script('sw-manager-script');
  }

  public static function handle_webpush_register() {
    WebPush_DB::add_subscription($_POST['endpoint'], $_POST['key']);

    wp_die();
  }

  public static function handle_webpush_get_payload() {
    wp_send_json(get_option('webpush_payload'));
  }

  public static function on_query_vars($qvars) {
    $qvars[] = 'webpush_file';
    return $qvars;
  }

  public static function on_parse_request($query) {
    $file = $query->query_vars['webpush_file'];

    if ($file === 'worker') {
      header('Content-Type: application/javascript');
      require_once(plugin_dir_path(__FILE__) . 'lib/js/sw.php');
      exit;
    }
  }

  public static function on_transition_post_status($new_status, $old_status, $post) {
    if (empty($post) || $new_status !== "publish") {
      return;
    }

    $title_option = get_option('webpush_title');

    update_option('webpush_payload', array(
      'title' => $title_option === 'blog_title' ? get_bloginfo('name') : $title_option,
      'body' => get_the_title($post->ID),
      'url' => get_permalink($post->ID),
    ));

    $subscriptions = WebPush_DB::get_subscriptions();
    foreach ($subscriptions as $subscription) {
      if (!sendNotification($subscription->endpoint)) {
        // If there's an error while sending the push notification,
        // the subscription is no longer valid, hence we remove it.
        WebPush_DB::remove_subscription($subscription->endpoint);
      }
    }
  }
}

?>
