<?php

class WebAppManifestGenerator {
  private static $instance;
  private $fields = array(
    "start_url" => "/",
  );

  public function __construct() {
    add_action('wp_head', array($this, 'add_manifest'));
    add_filter('query_vars', array($this, 'on_query_vars'), 10, 1);
    add_action('parse_request', array($this, 'on_parse_request'));
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public function add_manifest() {
    echo '<link rel="manifest" href="' . home_url('/') . '?webappmanifest_file=manifest">';
  }

  public function on_query_vars($qvars) {
    $qvars[] = 'webappmanifest_file';
    return $qvars;
  }

  public function set_field($key, $value) {
    $this->fields[$key] = $value;
  }

  public function on_parse_request($query) {
    if (!array_key_exists('webappmanifest_file', $query->query_vars)) {
      return;
    }

    $file = $query->query_vars['webappmanifest_file'];

    if ($file === 'manifest') {
      wp_send_json($this->fields);
    }
  }
}

?>
