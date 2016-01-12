<?php

load_plugin_textdomain('wpwebpush', '', dirname(plugin_basename(__FILE__)) . '/lang');

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
    $min_visits_option = get_option('webpush_min_visits');

    if (isset($_POST['webpush_form']) && $_POST['webpush_form'] === 'submitted') {
      if ($_POST['webpush_title'] === 'blog_title') {
        $title_option = 'blog_title';
      } else if ($_POST['webpush_title'] === 'custom') {
        $title_option = $_POST['webpush_title_custom'];
      } else {
        wp_die(__('Wrong value for the Notification Title', 'wpwebpush'));
      }

      if ($_POST['webpush_min_visits'] === '0') {
        $min_visits_option = 0;
      } else if ($_POST['webpush_min_visits'] === 'custom') {
        $min_visits_option = intval($_POST['webpush_min_visits_custom']);
      } else {
        wp_die(__('Wrong value for `Registration Behavior`', 'wpwebpush'));
      }

      update_option('webpush_title', $title_option);
      update_option('webpush_min_visits', $min_visits_option);

?>
<div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div>
<?php
    }
?>

<div class="wrap">
<h2><?php _e('Web Push', 'wpwebpush'); ?></h2>

<form method="post" action="">
<table class="form-table">

<input type="hidden" name="webpush_form" value="submitted" />

<tr>
<th scope="row"><?php _e('Notification Title', 'wpwebpush'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : '' ?> /> <?php _e('Use the Site Title', 'wpwebpush'); ?>: <b><?php echo get_bloginfo('name'); ?></b></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : '' ?> /> <?php _e('Custom:'); ?></label>
<input type="text" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : esc_attr__('Your custom title', 'wpwebpush') ?>" class="long-text" />
</fieldset>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Registration Behavior', 'wpwebpush'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_min_visits" value="0" <?php echo $min_visits_option === 0 ? 'checked' : '' ?> /> <?php _e('Ask the user to register as soon as he visits the site.', 'wpwebpush'); ?></label><br />
<label><input type="radio" name="webpush_min_visits" value="custom" <?php echo $min_visits_option !== 0 ? 'checked' : '' ?> /> <?php _e('Ask the user to register after N visits:'); ?></label>
<input type="text" name="webpush_min_visits_custom" value="<?php echo $min_visits_option !== 0 ? $min_visits_option : 3 ?>" class="small-text" />
</fieldset>
</td>
</tr>


</table>

<?php submit_button(__('Save Changes'), 'primary'); ?>

</form>

</div>

<?php
  }
}
?>
