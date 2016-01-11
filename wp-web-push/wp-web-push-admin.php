<?php

load_plugin_textdomain( 'wpwebpush', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );

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
    add_options_page(__('Web Push Options', 'wpwebpush'), __('Web Push', 'wpwebpush'), 'manage_options', 'web-push-options', array($this, 'options'));
  }

  public function options() {
    $title_option = get_option('webpush_title');

    if(isset($_POST['webpush_title'])) {
      if ($_POST['webpush_title'] === 'blog_title') {
        update_option('webpush_title', 'blog_title');
      } else if ($_POST['webpush_title'] === 'custom') {
        update_option('webpush_title', $_POST['webpush_title_custom']);
      } else {
        wp_die(__('Wrong value for the Notification Title', 'wpwebpush'));
      }
?>
<div class="updated"><p><strong><?php _e('Settings saved'); ?></strong></p></div>
<?php
    }
?>

<div class="wrap">
<h2><?php _e('Web Push', 'wpwebpush'); ?></h2>

<form method="post" action="">
<table class="form-table">

<tr>
<th scope="row"><?php _e('Notification Title', 'wpwebpush'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : '' ?> /> <?php _e('Use the Site Title', 'wpwebpush'); ?>: <b><?php echo get_bloginfo('name'); ?></b></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : '' ?> /> <?php _e('Custom', 'wpwebpush'); ?>:</label>
<input type="text" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : __('Your custom title', 'wpwebpush') ?>" class="long-text" />
</fieldset>
</td>
</tr>
</table>

<p class="submit">
<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'wpwebpush'); ?>" />
</p>

</form>

</div>

<?php
  }
}
?>
