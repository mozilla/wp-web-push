<?php

use Base64Url\Base64Url;

require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');

class WebPush_Main {
  private static $instance;
  public static $ALLOWED_TRIGGERS;

  public function __construct() {
    self::$ALLOWED_TRIGGERS = array(
      array(
        'text' => __('When a post is published.', 'web-push'),
        'key' => 'new-post',
        'enable_by_default' => true,
        'hook' => 'transition_post_status',
        'action' => 'on_transition_post_status',
      ),
      array(
        'text' => __('When a post is updated.', 'web-push'),
        'key' => 'update-post',
        'parentKey' => 'new-post',
        'enable_by_default' => true,
        'hook' => 'transition_post_status',
        'action' => 'on_transition_post_status',
      ),
      array(
        'text' => __('Right after subscription (useful to show to users what notifications look like).', 'web-push'),
        'key' => 'on-subscription',
        'enable_by_default' => true,
      ),
    );
    self::add_trigger_handlers();

    if (get_option('webpush_subscription_button')) {
      add_action('wp_footer', array($this, 'add_subscription_button'), 9999);
    }

    Mozilla\WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'service_worker'));

    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    add_filter('query_vars', array($this, 'on_query_vars'), 10, 1);
    add_action('parse_request', array($this, 'on_parse_request'));

    add_action('wp_ajax_webpush_register', array($this, 'handle_register'));
    add_action('wp_ajax_nopriv_webpush_register', array($this, 'handle_register'));
    add_action('wp_ajax_webpush_unregister', array($this, 'handle_unregister'));
    add_action('wp_ajax_nopriv_webpush_unregister', array($this, 'handle_unregister'));
    add_action('wp_ajax_webpush_get_payload', array($this, 'handle_get_payload'));
    add_action('wp_ajax_nopriv_webpush_get_payload', array($this, 'handle_get_payload'));
    add_action('wp_ajax_webpush_prompt', array($this, 'handle_prompt'));
    add_action('wp_ajax_nopriv_webpush_prompt', array($this, 'handle_prompt'));

    $senderID = get_option('webpush_gcm_sender_id');
    if ($senderID && get_option('webpush_generate_manifest')) {
      $manifestGenerator = Mozilla\WebAppManifestGenerator::getInstance();
    }

    $wpServeFile = Mozilla\WP_Serve_File::getInstance();
    $wpServeFile->add_file('subscription_button.css', array($this, 'subscriptionButtonCSSGenerator'));
    $wpServeFile->add_file('bell.svg', array($this, 'bellSVGGenerator'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public static function add_subscription_button() {
    echo '<section id="webpush-subscription">';
    echo '  <button class="subscribe"></button>';
    echo '  <section class="dialog">';
    echo '    <div>';
    echo '      <button class="close"></button>';
    echo '      <div class="message"></div>';
    echo '    </div>';
    echo '    <section class="actions"><button class="dismiss"></button><button class="default"></button></section>';
    echo '  </section>';
    echo '  <section class="bubble"></section>';
    echo '</section>';
  }

  public function enqueue_frontend_scripts() {
    wp_enqueue_script('localforage-script', plugins_url('lib/js/localforage.nopromises.min.js', __FILE__));

    $title_option = get_option('webpush_title');

    $icon = get_option('webpush_icon');
    if ($icon === 'blog_icon') {
      $icon = get_site_icon_url();
    } else if ($icon === 'post_icon') {
      // We don't have a post here.
      $icon = '';
    }

    wp_register_script('wp-web-push-script', plugins_url('lib/js/wp-web-push.js', __FILE__), array(Mozilla\WP_SW_Manager::SW_REGISTRAR_SCRIPT, 'localforage-script'));
    wp_localize_script('wp-web-push-script', 'WP_Web_Push', array(
      'sw_id' => Mozilla\WP_SW_Manager::get_manager()->sw_js_id(),
      'register_url' => admin_url('admin-ajax.php'),
      'min_visits' => get_option('webpush_min_visits'),
      'welcome_enabled' => in_array('on-subscription', get_option('webpush_triggers')),
      'welcome_title' => $title_option === 'blog_title' ? get_bloginfo('name') : $title_option,
      'welcome_body' => __('Successfully subscribed to notifications', 'web-push'),
      'welcome_icon' => $icon,
      'subscription_button' => get_option('webpush_subscription_button'),
      'prompt_interval' => get_option('webpush_prompt_interval'),
      'subscription_hint' => __('Welcome! Use this button to subscribe to notifications.', 'web-push'),
      'mobile_subscription_hint' => __('Subscribe to notifications', 'web-push'),
      'unsubscription_hint' => __('You can unsubscribe whenever you want using this button.', 'web-push'),
      'mobile_unsubscription_hint' => __('You are now subscribed!', 'web-push'),
      'unsubscribed_hint' => __('You are unsubscribed!', 'web-push'),
      'notification_preview' => plugins_url('lib/notification.svg', __FILE__),
      'subscription_prompt' => sprintf(__('<b>%s</b> can send you notifications for new posts.', 'web-push'), get_bloginfo('name')),
      'unsubscription_prompt' => sprintf(__('You are subscribed to <b>%s</b>\'s notifications.', 'web-push'), get_bloginfo('name')),
      'unsubscription_button_text' => __('Unsubscribe', 'web-push'),
      'subscription_button_text' => __('Subscribe', 'web-push'),
      'close_button_text' => __('Close', 'web-push'),
      'gcm_enabled' => get_option('webpush_gcm_sender_id') && get_option('webpush_gcm_key'),
    ));
    wp_enqueue_script('wp-web-push-script');

    if (get_option('webpush_subscription_button')) {
      wp_enqueue_style('subscription-button-style', Mozilla\WP_Serve_File::get_relative_to_wp_root_url('subscription_button.css'));
    }
  }

  public static function handle_register() {
    if (isset($_POST['oldEndpoint']) && $_POST['oldEndpoint'] !== $_POST['endpoint']) {
      WebPush_DB::remove_subscription($_POST['oldEndpoint']);
    }

    WebPush_DB::add_subscription($_POST['endpoint'], isset($_POST['key']) ? $_POST['key'] : '', isset($_POST['auth']) ? Base64Url::decode($_POST['auth']) : '');

    if (isset($_POST['newRegistration'])) {
      update_option('webpush_accepted_prompt_count', get_option('webpush_accepted_prompt_count') + 1);
    }

    wp_die();
  }

  public static function handle_unregister() {
    if (isset($_POST['endpoint'])) {
      WebPush_DB::remove_subscription($_POST['endpoint']);
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
    if (array_key_exists('webpush_post_id', $query->query_vars) && is_numeric($query->query_vars['webpush_post_id'])) {
      $post_id = intval($query->query_vars['webpush_post_id']);
      $notifications_clicked = get_post_meta($post_id, '_notifications_clicked', true);
      if ($notifications_clicked !== '') {
        update_post_meta($post_id, '_notifications_clicked', $notifications_clicked + 1);
      }
    }
  }

  public function service_worker() {
    require_once(plugin_dir_path(__FILE__) . 'lib/js/sw.php');
  }

  public static function sendNotification($title, $body, $icon, $url, $post) {
    require_once(plugin_dir_path(__FILE__) . 'web-push.php' );

    $webPush = new WebPush();

    update_option('webpush_payload', array(
      'title' => html_entity_decode($title, ENT_COMPAT, get_option('blog_charset')),
      'body' => html_entity_decode($body, ENT_COMPAT, get_option('blog_charset')),
      'icon' => $icon,
      'url' => $url,
      'postID' => $post ? $post->ID : '',
    ));

    $gcmKey = get_option('webpush_gcm_key');
    $webPush->setGCMKey($gcmKey);
    $webPush->setVAPIDInfo(get_option('webpush_vapid_key'), get_option('webpush_vapid_audience'), get_option('webpush_vapid_subject'));

    $notification_count = 0;

    // Sending notifications could take some time, so we extend the maximum
    // execution time for the script.
    set_time_limit(120);

    $subscriptions = WebPush_DB::get_subscriptions();
    $subscription_num = count($subscriptions);
    foreach ($subscriptions as $subscription) {
      // Ignore GCM endpoints if we don't have a GCM key.
      $isGCM = strpos($subscription->endpoint, GCM_REQUEST_URL) === 0;
      if (!$gcmKey && $isGCM) {
        continue;
      }

      $webPush->addRecipient($subscription->endpoint, function($success) use ($subscription, &$notification_count) {
        if (!$success) {
          // If there's an error while sending the push notification,
          // the subscription is no longer valid, hence we remove it.
          WebPush_DB::remove_subscription($subscription->endpoint);
        } else {
          $notification_count++;
        }
      });
    }

    $webPush->sendNotifications();

    return $notification_count;
  }

  public static function on_transition_post_status($new_status, $old_status, $post) {
    if (empty($post) or get_post_type($post) !== 'post') {
      return;
    }

    if (isset($_REQUEST['webpush_meta_box_nonce'])) {
      if (!wp_verify_nonce($_REQUEST['webpush_meta_box_nonce'], 'webpush_send_notification')) {
        return;
      }

      if (!isset($_REQUEST['webpush_send_notification'])) {
        update_post_meta($post->ID, '_notifications_enabled', 'd');
      } else {
        delete_post_meta($post->ID, '_notifications_enabled');
      }
    } else {
      if (($old_status === 'publish' || isset($post->_notifications_sent)) && !in_array('update-post', get_option('webpush_triggers'))) {
        return;
      }
    }

    if ($new_status !== 'publish') {
      return;
    }

    $notificationsEnabled = get_post_meta($post->ID, '_notifications_enabled', true);

    if ($notificationsEnabled === 'd') {
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

    $notification_count = self::sendNotification(
      $title_option === 'blog_title' ? get_bloginfo('name') : $title_option,
      get_the_title($post->ID),
      $icon,
      get_permalink($post->ID),
      $post
    );

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
    if (!$enabled_triggers) {
      return;
    }

    $registered_hooks = array();

    foreach ($enabled_triggers as $trigger) {
      $trigger_detail = self::get_trigger_by_key_value('key', $trigger);

      if ($trigger_detail and array_key_exists('hook', $trigger_detail) and
          !in_array($trigger_detail['hook'], $registered_hooks)) {
        $registered_hooks[] = $trigger_detail['hook'];
        add_action($trigger_detail['hook'], array($this, $trigger_detail['action']), 10, 3);
      }
    }
  }

  public static function subscriptionButtonCSSGenerator() {
    ob_start();
    require_once(plugin_dir_path(__FILE__) . 'lib/style/subscription_button.css');
    return array(
      'content' => ob_get_clean(),
      'contentType' => 'text/css',
    );
  }

  public static function bellSVGGenerator() {
    ob_start();
    require_once(plugin_dir_path(__FILE__) . 'lib/bell.svg');
    return array(
      'content' => ob_get_clean(),
      'contentType' => 'image/svg+xml',
    );
  }
}

?>
