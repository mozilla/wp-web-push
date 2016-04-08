<?php

function getSentNotificationNum() {
  return intval(file_get_contents('http://localhost:55555/'));
}

?>
