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

    $lastModified = get_option('wpservefile_files_' . $name . '_lastModified');
    if (!$lastModified) {
      return;
    }

    $maxAge = DAY_IN_SECONDS;
    $etag = md5($lastModified);

    if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($lastModified) || $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
      header('HTTP/1.1 304 Not Modified');
      exit;
    }

    $content = get_option('wpservefile_files_' . $name . '_content');
    $contentType = get_option('wpservefile_files_' . $name . '_contentType');

    header('HTTP/1.1 200 OK');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    header('Cache-Control: max-age=' . $maxAge . ', public, must-revalidate');
    header('Last-Modified: ' . $lastModified);
    header('ETag: ' . $etag);
    header('Content-Type: ' . $contentType);
    echo $content;
    wp_die();
  }

  public static function add_file($name, $content, $contentType) {
    update_option('wpservefile_files_' . $name . '_content', $content);
    update_option('wpservefile_files_' . $name . '_contentType', $contentType);
    update_option('wpservefile_files_' . $name . '_lastModified', gmdate('D, d M Y H:i:s', time()) . ' GMT');
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
