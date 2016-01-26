<?php

class WebPush_DB {
  private static $instance;
  private $version = '0.0.1';

  public function __construct() {
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public static function add_subscription($endpoint, $userKey) {
    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'webpush_subscription', array(
      'endpoint' => $endpoint,
      'userKey' => $userKey,
    ));
  }

  public static function remove_subscription($endpoint) {
    global $wpdb;

    $wpdb->delete($wpdb->prefix . 'webpush_subscription', array(
      'endpoint' => $endpoint,
    ));
  }

  public static function get_subscriptions() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'webpush_subscription';
    return $wpdb->get_results('SELECT `endpoint`,`userKey` FROM ' . $table_name);
  }

  public static function on_activate() {
    global $wpdb;

    if (WebPush_DB::getInstance()->version === get_option('webpush_db_version')) {
      return;
    }

    $table_name = $wpdb->prefix . 'webpush_subscription';

    $sql = 'CREATE TABLE ' . $table_name . ' (
      `id` INT NOT NULL AUTO_INCREMENT,
      `endpoint` VARCHAR(300) NOT NULL,
      `userKey` VARCHAR(300) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE (`endpoint`)
    );';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('webpush_db_version', WebPush_DB::getInstance()->version);

    // Set default options.
    update_option('webpush_title', 'blog_title');
    update_option('webpush_icon', function_exists('get_site_icon_url') ? 'blog_icon' : '');
    update_option('webpush_min_visits', 3);
    update_option('webpush_notification_button', true);
    update_option('webpush_gcm_key', '');
    update_option('webpush_gcm_sender_id', '');
    update_option('webpush_notification_count', 0);
    update_option('webpush_opened_notification_count', 0);
    update_option('webpush_prompt_count', 0);
    update_option('webpush_accepted_prompt_count', 0);

    $default_triggers = WebPush_Main::get_triggers_by_key_value('enable_by_default', true);
    $default_triggers_keys = array();
    foreach($default_triggers as $trigger) {
      $default_triggers_keys[] = $trigger['key'];
    }

    update_option('webpush_triggers', $default_triggers_keys);
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'webpush_subscription';
    $wpdb->query('DROP TABLE ' . $table_name);

    delete_option('webpush_db_version');
    delete_option('webpush_payload');
    delete_option('webpush_title');
    delete_option('webpush_icon');
    delete_option('webpush_min_visits');
    delete_option('webpush_notification_button');
    delete_option('webpush_triggers');
    delete_option('webpush_gcm_key');
    delete_option('webpush_gcm_sender_id');
    delete_option('webpush_notification_count');
    delete_option('webpush_opened_notification_count');
    delete_option('webpush_prompt_count');
    delete_option('webpush_accepted_prompt_count');
  }
}

?>
