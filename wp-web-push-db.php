<?php

class WebPush_DB {
  private static $instance;
  private $version = "0.0.1";

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

  public static function on_activate() {
    global $wpdb;

    if (WebPush_DB::getInstance()->version == get_option('webpush_db_version')) {
      return;
    }

    $table_name = $wpdb->prefix . 'webpush_subscription';

    $sql = 'CREATE TABLE ' . $table_name . ' (
      `id` INT NOT NULL AUTO_INCREMENT,
      `endpoint` VARCHAR(300) NOT NULL,
      `userKey` VARCHAR(300) NOT NULL,
      PRIMARY KEY (`id`)
    );';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('webpush_db_version', WebPush_DB::getInstance()->version);
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    // TODO: Remove table
  }
}

?>
