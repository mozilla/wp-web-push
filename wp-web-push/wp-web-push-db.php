<?php

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;

class WebPush_DB {
  private static $instance;
  const VERSION = '1.4.0';

  public function __construct() {
    add_action('plugins_loaded', array($this, 'update_check'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public static function add_subscription($endpoint, $userKey, $userAuth) {
    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'webpush_subscription', array(
      'endpoint' => $endpoint,
      'userKey' => $userKey,
      'userAuth' => $userAuth,
    ));
  }

  public static function is_subscription($endpoint) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'webpush_subscription';
    return $wpdb->get_var($wpdb->prepare('SELECT count(1) FROM ' . $table_name . ' WHERE `endpoint` = %s', $endpoint)) != 0;
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
    return $wpdb->get_results('SELECT `endpoint`,`userKey`,`userAuth` FROM ' . $table_name);
  }

  public static function count_subscriptions() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'webpush_subscription';
    return $wpdb->get_var('SELECT COUNT(*) FROM ' . $table_name);
  }

  public static function generate_vapid_options() {
    if (USE_VAPID) {
      if (!get_option('webpush_vapid_key')) {
        $generator = EccFactory::getNistCurves()->generator256();
        $privKeySerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer());

        update_option('webpush_vapid_key', $privKeySerializer->serialize($generator->createPrivateKey()));
      }

      if (!get_option('webpush_vapid_subject')) {
        update_option('webpush_vapid_subject', 'mailto:' . get_option('admin_email'));
      }

      if (!get_option('webpush_vapid_audience')) {
        $parsedURL = parse_url(home_url('/', 'https'));
        update_option('webpush_vapid_audience', $parsedURL['scheme'] . '://' . $parsedURL['host'] . (isset($parsedURL['port']) ? ':' . $parsedURL['port'] : ''));
      }
    }
  }

  public function update_check() {
    global $wpdb;

    if (self::VERSION === get_option('webpush_db_version')) {
      return;
    }

    $table_name = $wpdb->prefix . 'webpush_subscription';

    $sql = 'CREATE TABLE ' . $table_name . ' (
      `id` INT NOT NULL AUTO_INCREMENT,
      `endpoint` VARCHAR(300) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
      `userKey` VARCHAR(300) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
      `userAuth` BINARY(16) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE (`endpoint`)
    );';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('webpush_db_version', self::VERSION);

    // Set default options.
    add_option('webpush_title', 'blog_title');
    add_option('webpush_icon', function_exists('get_site_icon_url') ? 'blog_icon' : '');
    add_option('webpush_min_visits', -1);
    add_option('webpush_subscription_button', true);
    add_option('webpush_prompt_interval', 3);
    add_option('webpush_gcm_key', '');
    add_option('webpush_gcm_sender_id', '');
    add_option('webpush_generate_manifest', true);

    self::generate_vapid_options();

    add_option('webpush_prompt_count', 0);
    add_option('webpush_accepted_prompt_count', 0);
    add_option('webpush_subscription_button_color', '#005189');
    Mozilla\WP_Serve_File::getInstance()->invalidate_files(array('subscription_button.css', 'bell.svg'));

    $default_triggers = WebPush_Main::get_triggers_by_key_value('enable_by_default', true);
    $default_triggers_keys = array();
    foreach ($default_triggers as $trigger) {
      $default_triggers_keys[] = $trigger['key'];
    }

    add_option('webpush_triggers', $default_triggers_keys);
  }

  public static function on_activate() {
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
    delete_option('webpush_subscription_button');
    delete_option('webpush_subscription_button_color');
    delete_option('webpush_prompt_interval');
    delete_option('webpush_triggers');
    delete_option('webpush_gcm_key');
    delete_option('webpush_gcm_sender_id');
    delete_option('webpush_vapid_key');
    delete_option('webpush_vapid_audience');
    delete_option('webpush_vapid_subject');
    delete_option('webpush_prompt_count');
    delete_option('webpush_accepted_prompt_count');
    delete_post_meta_by_key('_notifications_sent');
    delete_post_meta_by_key('_notifications_clicked');
    delete_post_meta_by_key('_notifications_enabled');
  }
}

?>
