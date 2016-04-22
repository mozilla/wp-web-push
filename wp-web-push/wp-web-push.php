<?php
/*
Plugin Name: Web Push
Plugin URI: https://github.com/marco-c/wp-web-push
Description: Web Push notifications for your website.
Version: 1.4.0
Author: Mozilla
Author URI: https://www.mozilla.org/
License: GPLv2 or later
Text Domain: web-push
*/

define('USE_VAPID', version_compare(phpversion(), '5.4') >= 0 && // PHP 5.4+
                    function_exists('mcrypt_encrypt') &&         // ext-mcrypt (https://packagist.org/packages/mdanter/ecc)
                    function_exists('gmp_mod')                   // ext-gmp (https://packagist.org/packages/mdanter/ecc)
      );

load_plugin_textdomain('web-push', false, dirname(plugin_basename(__FILE__)) . '/lang');

require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

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
