<?php

require_once(plugin_dir_path(__FILE__) . 'web-push.php' );
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');
require_once(plugin_dir_path(__FILE__) . 'WebAppManifestGenerator.php');
require_once(plugin_dir_path(__FILE__) . 'vendor/mozilla/wp-sw-manager/class-wp-sw-manager.php');

class WebPush_Main {
  private static $instance;
  public static $ALLOWED_TRIGGERS;

  public function __construct() {
    self::$ALLOWED_TRIGGERS = array(
      array('text' => __('New Post', 'web-push'), 'key' => 'new-post', 'enable_by_default' => true, 'hook' => 'transition_post_status', 'action' => 'on_transition_post_status'),
      array('text' => __('On Subscription', 'web-push'), 'key' => 'on-subscription', 'enable_by_default' => true),
    );
    self::add_trigger_handlers();

    if (get_option('webpush_subscription_button')) {
      add_action('wp_footer', array($this, 'add_subscription_button'), 9999);
    }

    WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'service_worker'));

    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    add_filter('query_vars', array($this, 'on_query_vars'), 10, 1);
    add_action('parse_request', array($this, 'on_parse_request'));

    add_action('wp_ajax_nopriv_webpush_register', array($this, 'handle_register'));
    add_action('wp_ajax_nopriv_webpush_get_payload', array($this, 'handle_get_payload'));
    add_action('wp_ajax_nopriv_webpush_prompt', array($this, 'handle_prompt'));

    $manifestGenerator = WebAppManifestGenerator::getInstance();
    $manifestGenerator->set_field('gcm_sender_id', get_option('webpush_gcm_sender_id'));
    $manifestGenerator->set_field('gcm_user_visible_only', true);
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public static function add_subscription_button() {
    echo '<div id="webpush-subscription-container">';
    echo '<div id="webpush-subscription-button"><img id="webpush-subscription-button-image" src="' . plugins_url('lib/bell.svg', __FILE__) . '" alt="" /></div>';
    echo '<div id="webpush-explanatory-bubble"></div>';
    echo '</div>';
  }

  public function enqueue_frontend_scripts() {
    wp_enqueue_script('localforage-script', plugins_url('lib/js/localforage.min.js', __FILE__));

    $title_option = get_option('webpush_title');

    $icon = get_option('webpush_icon');
    if ($icon === 'blog_icon') {
      $icon = get_site_icon_url();
    } else if ($icon === 'post_icon') {
      // We don't have a post here.
      $icon = '';
    }

    wp_register_script('wp-web-push-script', plugins_url('lib/js/wp-web-push.js', __FILE__), array(WP_SW_Manager::SW_REGISTRAR_SCRIPT));
    wp_localize_script('wp-web-push-script', 'ServiceWorker', array(
      'sw_id' => WP_SW_Manager::get_manager()->sw_js_id(),
      'register_url' => admin_url('admin-ajax.php'),
      'min_visits' => get_option('webpush_min_visits'),
      'welcome_enabled' => in_array('on-subscription', get_option('webpush_triggers')),
      'welcome_title' => $title_option === 'blog_title' ? get_bloginfo('name') : $title_option,
      'welcome_body' => __('Successfully subscribed to notifications'),
      'welcome_icon' => $icon,
      'subscription_button' => get_option('webpush_subscription_button'),
      'notification_enabled_icon' => plugins_url('lib/bell.svg', __FILE__),
      'notification_disabled_icon' => plugins_url('lib/bell_disabled.svg', __FILE__),
      'prompt_interval' => get_option('webpush_prompt_interval'),
      'subscription_hint' => __('Welcome! Use this button to subscribe to notifications.'),
      'unsubscription_hint' => __('You can unsubscribe whenever you want using this button.'),
    ));
    wp_enqueue_script('wp-web-push-script');

    if (get_option('webpush_subscription_button')) {
      wp_enqueue_style('subscription-button-style', plugins_url('lib/style/subscription_button.css', __FILE__));
    }
  }

  public static function handle_register() {
    WebPush_DB::add_subscription($_POST['endpoint'], $_POST['key']);

    if (isset($_POST['newRegistration'])) {
      update_option('webpush_accepted_prompt_count', get_option('webpush_accepted_prompt_count') + 1);
    }

    wp_die();
  }

  public static function handle_get_payload() {
    wp_send_json(get_option('webpush_payload'));
  }

  public static function handle_prompt() {
    update_option('webpush_prompt_count', get_option('webpush_prompt_count') + 1);
    wp_die();
  }

  public static function on_query_vars($qvars) {
    $qvars[] = 'webpush_post_id';
    return $qvars;
  }

  public static function on_parse_request($query) {
    if (array_key_exists('webpush_post_id', $query->query_vars)) {
      $post_id = intval($query->query_vars['webpush_post_id']);
      $notifications_clicked = get_post_meta($post_id, '_notifications_clicked', true);
      update_post_meta($post_id, '_notifications_clicked', $notifications_clicked + 1);
    }
  }

  public function service_worker() {
    require_once(plugin_dir_path(__FILE__) . 'lib/js/sw.php');
  }

  public static function on_transition_post_status($new_status, $old_status, $post) {
    if (empty($post) || $new_status !== 'publish' ||
        !in_array('new-post', get_option('webpush_triggers'))) {
      return;
    }

    $title_option = get_option('webpush_title');

    $icon = get_option('webpush_icon');
    if ($icon === 'blog_icon') {
      $icon = get_site_icon_url();
    } else if ($icon === 'post_icon') {
      $post_thumbnail_id = get_post_thumbnail_id($post->ID);
      $icon = $post_thumbnail_id ? wp_get_attachment_url($post_thumbnail_id) : '';
    }

    update_option('webpush_payload', array(
      'title' => $title_option === 'blog_title' ? get_bloginfo('name') : $title_option,
      'body' => get_the_title($post->ID),
      'icon' => $icon,
      'url' => get_permalink($post->ID),
      'postID' => $post->ID,
    ));

    $gcmKey = get_option('webpush_gcm_key');

    $notification_count = 0;

    $subscriptions = WebPush_DB::get_subscriptions();
    foreach ($subscriptions as $subscription) {
      if (!sendNotification($subscription->endpoint, $gcmKey)) {
        // If there's an error while sending the push notification,
        // the subscription is no longer valid, hence we remove it.
        WebPush_DB::remove_subscription($subscription->endpoint);
      } else {
        $notification_count++;
      }
    }

    update_post_meta($post->ID, '_notifications_clicked', 0);
    update_post_meta($post->ID, '_notifications_sent', $notification_count);
  }

  public static function get_trigger_by_key_value($key, $value) {
    foreach(self::$ALLOWED_TRIGGERS as $trigger) {
      if($trigger[$key] === $value) {
        return $trigger;
      }
    }
    return false;
  }

  public static function get_triggers_by_key_value($key, $value) {
    $matches = array();

    foreach(self::$ALLOWED_TRIGGERS as $trigger) {
      if($trigger[$key] === $value) {
        $matches[] = $trigger;
      }
    }
    return $matches;
  }

  public function add_trigger_handlers() {
    $enabled_triggers = get_option('webpush_triggers');
    if(!$enabled_triggers) {
      $enabled_triggers = array();
    }
    foreach($enabled_triggers as $trigger) {
      $trigger_detail = self::get_trigger_by_key_value('key', $trigger);

      if ($trigger_detail and array_key_exists('hook', $trigger_detail)) {
        add_action($trigger_detail['hook'], array($this, $trigger_detail['action']), 10, 3);
      }
    }
  }
}

?>
