<?php

class WebPush_Main {
  private static $instance;

  public function __construct() {
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }
}

?>
