<?php

class WebPush_Admin {
  private static $instance;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function on_admin_menu() {
    add_options_page('Web Push Options', 'Web Push', 'manage_options', 'web-push-options', array($this, 'options'));
  }

  public function options() {
    $title_option = get_option('webpush_title');

    if(isset($_POST['webpush_title'])) {
      if ($_POST['webpush_title'] === 'blog_title') {
        update_option('webpush_title', 'blog_title');
      } else if ($_POST['webpush_title'] === 'custom') {
        update_option('webpush_title', $_POST['webpush_title_custom']);
      } else {
        wp_die('Wrong value for the Notification Title');
      }
?>
<div class="updated"><p><strong>Settings saved.</strong></p></div>
<?php
    }
?>

<div class="wrap">
<h2>Web Push</h2>

<form method="post" action="">
<table class="form-table">

<tr>
<th scope="row">Notification Title</th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : '' ?> /> Use the Site Title: <b><?php echo get_bloginfo('name'); ?></b></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : '' ?> /> Custom:</label>
<input type="text" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : 'Write here the title' ?>" class="long-text" />
</fieldset>
</td>
</tr>
</table>

<p class="submit">
<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
</p>

</form>

</div>

<?php
  }
}
?>
