<?php

class WebPush_DB {
  private static $instance;

  public function __construct() {
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public function on_activate() {
  }

  public function on_deactivate() {
  }

  public function on_uninstall() {
  }
}

?>
