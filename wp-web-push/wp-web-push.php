<?php
/*
Plugin Name: WordPress Web Push
Text Domain: wpwebpush
*/

require_once(plugin_dir_path(__FILE__) . 'wp-web-push-main.php');
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-db.php');
require_once(plugin_dir_path(__FILE__) . 'wp-web-push-admin.php');

WebPush_Main::init();
WebPush_Admin::init();

register_activation_hook(__FILE__, array('WebPush_DB', 'on_activate'));
register_deactivation_hook(__FILE__, array('WebPush_DB', 'on_deactivate'));
register_uninstall_hook(__FILE__, array('WebPush_DB', 'on_uninstall'));

?>
