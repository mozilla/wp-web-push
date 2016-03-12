<?php

if (!class_exists('WPServeFile')) {

class WPServeFile {
  private static $instance;

  public function __construct() {
    add_action('wp_ajax_wpservefile', array($this, 'serve_file'));
    add_action('wp_ajax_nopriv_wpservefile', array($this, 'serve_file'));
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public static function serve_file() {
    $name = $_GET['wpservefile_file'];

    $content = get_option('wpservefile_files_' . $name . '_content');
    if (!$content) {
      return;
    }
    $contentType = get_option('wpservefile_files_' . $name . '_contentType');

    header('Content-Type: ' . $contentType);
    echo $content;
    wp_die();
  }

  public static function add_file($name, $content, $contentType) {
    update_option('wpservefile_files_' . $name . '_content', $content);
    update_option('wpservefile_files_' . $name . '_contentType', $contentType);
  }

  public static function get_relative_to_host_root_url($name) {
    return admin_url('admin-ajax.php', 'relative') . '?action=wpservefile&wpservefile_file=' . $name;
  }

  public static function get_relative_to_wp_root_url($name) {
    $url = self::get_relative_to_host_root_url($name);
    $site_url = site_url('', 'relative');
    if (substr($url, 0, strlen($site_url)) === $site_url) {
      $url = substr($url, strlen($site_url));
    }

    return $url;
  }
}

}

?>
