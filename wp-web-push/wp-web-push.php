<?php
/*
Plugin Name: Web Push
Plugin URI: https://github.com/marco-c/wp-web-push
Description: Web Push notifications for your website.
Version: 0.0.7
Author: Mozilla
Author URI: https://www.mozilla.org/
License: GPLv2 or later
Text Domain: web-push
*/

load_plugin_textdomain('web-push', false, dirname(plugin_basename(__FILE__)) . '/lang');

require_once(plugin_dir_path(__FILE__) . 'wp-web-push-main.php');
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');

WebPush_DB::init();
WebPush_Main::init();

if (is_admin()) {
  require_once(plugin_dir_path(__FILE__) . 'wp-web-push-admin.php');
  WebPush_Admin::init();
}

register_activation_hook(__FILE__, array('WebPush_DB', 'on_activate'));
register_deactivation_hook(__FILE__, array('WebPush_DB', 'on_deactivate'));
register_uninstall_hook(__FILE__, array('WebPush_DB', 'on_uninstall'));

?>
