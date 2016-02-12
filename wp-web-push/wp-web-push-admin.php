<?php

class WebPush_Admin {
  private static $instance;
  private $options_page;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    add_action('admin_notices', array($this, 'on_admin_notices'));
  }

  function on_admin_notices() {
    if (!current_user_can('manage_options')) {
      // There's no point in showing the notice to users that can't modify the options.
      return;
    }

    if (get_option('webpush_gcm_key') && get_option('webpush_gcm_sender_id')) {
      // No need to show the notice if the settings are set.
      return;
    }

    if (isset($_POST['webpush_gcm_key']) && trim($_POST['webpush_gcm_key']) &&
        isset($_POST['webpush_gcm_sender_id']) && trim($_POST['webpush_gcm_sender_id'])) {
      // No need to show the notice if the admin has just inserted the values.
      return;
    }

    $options_url = add_query_arg(array('page' => 'web-push-options#gcm'), admin_url('options-general.php'));
    echo '<div class="error"><p>' . sprintf(__('You need to set up the GCM-specific information in order to make push notifications work on Google Chrome. <a href="%s">Do it now</a>.', 'web-push'), $options_url) . '</p></div>';
  }

  function enqueue_scripts($hook) {
    if ($hook === 'index.php') {
      // Load Chart.js only in the Dashboard.

      $posts = get_posts(array(
        'numberposts' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish',
      ));

      // Older posts on the left, newer on the right.
      $posts = array_reverse($posts);

      $labels = array();
      $sent = array();
      $opened = array();
      foreach ($posts as $post) {
        $labels[] = $post->post_title;
        $sent[] = intval($post->_notifications_sent);
        $opened[] = intval($post->_notifications_clicked);
      }

      wp_enqueue_script('Chart.js-script', plugins_url('lib/js/Chart.min.js' , __FILE__));
      wp_register_script('dashboard-chart-script', plugins_url('lib/js/dashboard-chart.js', __FILE__), array('Chart.js-script'));
      wp_localize_script('dashboard-chart-script', 'webPushChartData', array(
        'legendSent' => __('Sent notifications', 'web-push'),
        'legendOpened' => __('Opened notifications', 'web-push'),
        'labels' => $labels,
        'sent' => $sent,
        'opened' => $opened,
      ));
      wp_enqueue_script('dashboard-chart-script');
    } else if ($hook === $this->options_page) {
      wp_enqueue_media();
      wp_enqueue_script('options-page-script', plugins_url('lib/js/options-page.js', __FILE__));
    }
  }

  function add_dashboard_widgets() {
    wp_add_dashboard_widget('wp-web-push_dashboard_widget', __('Web Push', 'web-push'), array($this, 'dashboard_widget'));
  }

  function dashboard_widget() {
    $prompt_count = get_option('webpush_prompt_count');
    $accepted_prompt_count = get_option('webpush_accepted_prompt_count');
    printf(_n('%s user prompted.', '%s users prompted.', $prompt_count, 'web-push'), number_format_i18n($prompt_count));
    echo '<br>';
    printf(_n('%s user accepted to receive notifications.', '%s users accepted to receive notifications.', $accepted_prompt_count, 'web-push'), number_format_i18n($accepted_prompt_count));
    echo '<br><br>';
    echo '<canvas id="notifications-chart"></canvas>';
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function on_admin_menu() {
    $this->options_page = add_options_page(__('Web Push Options', 'web-push'), __('Web Push', 'web-push'), 'manage_options', 'web-push-options', array($this, 'options'));
  }

  public function options() {
    $allowed_triggers = WebPush_Main::$ALLOWED_TRIGGERS;
    $title_option = get_option('webpush_title');
    $icon_option = get_option('webpush_icon');
    $min_visits_option = intval(get_option('webpush_min_visits'));
    $subscription_button_option = get_option('webpush_subscription_button');
    $prompt_interval_option = get_option('webpush_prompt_interval');
    $triggers_option = get_option('webpush_triggers');
    $gcm_key_option = get_option('webpush_gcm_key');
    $gcm_sender_id_option = get_option('webpush_gcm_sender_id');

    if (isset($_POST['webpush_form']) && $_POST['webpush_form'] === 'submitted') {
      if ($_POST['webpush_title'] === 'blog_title') {
        $title_option = 'blog_title';
      } else if ($_POST['webpush_title'] === 'custom') {
        $title_option = $_POST['webpush_title_custom'];
      } else {
        wp_die(__('Invalid value for the Notification Title', 'web-push'));
      }

      if ($_POST['webpush_icon'] === '') {
        $icon_option = '';
      } else if ($_POST['webpush_icon'] === 'blog_icon') {
        $icon_option = 'blog_icon';
      } else if ($_POST['webpush_icon'] === 'post_icon') {
        $icon_option = 'post_icon';
      } else if ($_POST['webpush_icon'] === 'custom') {
        $icon_option = $_POST['webpush_icon_custom'];
      } else {
        wp_die(__('Invalid value for the Notification Icon', 'web-push'));
      }

      if ($_POST['webpush_min_visits'] === '0') {
        $min_visits_option = 0;
      } else if ($_POST['webpush_min_visits'] === 'custom') {
        $min_visits_option = intval($_POST['webpush_min_visits_custom']);
      } else if ($_POST['webpush_min_visits'] === '-1') {
        $min_visits_option = -1;
      } else {
        wp_die(__('Invalid value for `Registration Behavior`', 'web-push'));
      }

      if (isset($_POST['webpush_subscription_button'])) {
        $subscription_button_option = true;
      } else {
        $subscription_button_option = false;
      }

      $prompt_interval_option = intval($_POST['webpush_prompt_interval']);

      if(isset($_POST['webpush_triggers'])) {
        $triggers_option = $_POST['webpush_triggers'];
        foreach($triggers_option as $trigger_option) {
          if(!WebPush_Main::get_trigger_by_key_value('key', $trigger_option)) {
            wp_die(__('Invalid value in Push Triggers: '.$trigger_option, 'web-push'));
          }
        }
      }
      else {
        // If it's not set it means they've removed all options
        $triggers_option = array();
      }

      $gcm_key_option = $_POST['webpush_gcm_key'];
      $gcm_sender_id_option = $_POST['webpush_gcm_sender_id'];

      update_option('webpush_title', $title_option);
      update_option('webpush_icon', $icon_option);
      update_option('webpush_min_visits', $min_visits_option);
      update_option('webpush_subscription_button', $subscription_button_option);
      update_option('webpush_prompt_interval', $prompt_interval_option);
      update_option('webpush_triggers', $triggers_option);
      update_option('webpush_gcm_key', $gcm_key_option);
      update_option('webpush_gcm_sender_id', $gcm_sender_id_option);

?>
<div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div>
<?php
    }

    $icon_url = '';
    if ($icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon') {
      $icon_url = $icon_option;
    }
?>

<div class="wrap">
<h2><?php _e('Web Push', 'web-push'); ?></h2>

<form method="post" action="" enctype="multipart/form-data">
<table class="form-table">
<h2 class="title"><?php _e('Notification UI Options', 'web-push'); ?></h2>
<p><?php _e('In this section, you can customize the information that appears in the notifications that will be shown to users.'); ?></p>

<input type="hidden" name="webpush_form" value="submitted" />

<tr>
<th scope="row"><?php _e('Title', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Use the Site Title', 'web-push'); ?>: <b><?php echo get_bloginfo('name'); ?></b></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Custom:'); ?></label>
<input type="text" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : esc_attr__('Your custom title', 'web-push'); ?>" class="long-text" />
</fieldset>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Icon', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_icon" value="" <?php echo $icon_option === '' ? 'checked' : ''; ?> /> <?php _e('Don\'t use any icon', 'web-push'); ?></label>
<br />
<label><input type="radio" name="webpush_icon" value="post_icon" <?php echo $icon_option === 'post_icon' ? 'checked' : ''; ?> /> <?php _e('Use the Post Thumbnail', 'web-push'); ?></label>
<br />
<?php
  if (function_exists('get_site_icon_url')) {
?>
<label><input type="radio" name="webpush_icon" value="blog_icon" <?php echo $icon_option === 'blog_icon' ? 'checked' : ''; ?> /> <?php _e('Use the Site Icon', 'web-push'); ?></label>
<?php
    $site_icon_url = get_site_icon_url();
    if ($site_icon_url) {
      echo '<img src="' . $site_icon_url . '">';
    }
?>
<br />
<?php
  }
?>
<label><input type="radio" name="webpush_icon" value="custom" <?php echo $icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon' ? 'checked' : ''; ?> /> <?php _e('Custom'); ?></label>
<img id="webpush_icon_custom_image" style="max-width:128px;max-height:128px;<?php if (!$icon_url) { echo 'display:none;'; } ?>" src="<?php echo $icon_url; ?>">
<input type="hidden" id="webpush_icon_custom" name="webpush_icon_custom" value="<?php echo $icon_url; ?>" />
<input type="button" class="button" id="webpush_icon_custom_button" value="Select..."></input>
</fieldset>
</td>
</tr>
</table>


<table class="form-table">
<h2 class="title"><?php _e('Subscription Behavior', 'web-push'); ?></h2>
<p><?php _e('In this section, you can customize the subscription behavior and tailor it to your site. We suggest limiting automatic prompting to avoid nagging users (unless you know that your visitors are really interested) and always giving the option to subscribe/unsubscribe through the subscription button.'); ?></p>

<tr>
<th scope="row"></th>
<td>
<label><input type="checkbox" name="webpush_subscription_button" <?php echo $subscription_button_option ? 'checked' : ''; ?> /> <?php _e('Show subscription button', 'web-push'); ?></label>
<p class="description"><?php _e('A button in the bottom-right corner of the page that the user can use to subscribe/unsubscribe.')?></p>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Automatic prompting', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_min_visits" value="0" <?php echo $min_visits_option === 0 ? 'checked' : ''; ?> /> <?php _e('Ask the user to register as soon as he visits the site', 'web-push'); ?></label><br />
<label><input type="radio" name="webpush_min_visits" value="custom" <?php echo $min_visits_option !== 0 && $min_visits_option !== -1 ? 'checked' : ''; ?> /> <?php _e('Ask the user to register after N visits:'); ?></label>
<input type="number" name="webpush_min_visits_custom" value="<?php echo $min_visits_option !== 0 && $min_visits_option !== -1 ? $min_visits_option : 3; ?>" class="small-text" /><br />
<label><input type="radio" name="webpush_min_visits" value="-1" <?php echo $min_visits_option === -1 ? 'checked' : ''; ?> /> <?php _e('Never automatically ask the user to register', 'web-push'); ?></label>
</fieldset>
</td>
</tr>

<tr>
<th scope="row"><label for="webpush_prompt_interval"><?php _e('Interval between prompts', 'web-push'); ?></label></th>
<td><input name="webpush_prompt_interval" type="number" value="<?php echo $prompt_interval_option; ?>" class="small-text" />
<p class="description"><?php _e('If the user declines or dismisses the prompt, this is the time interval (in days) to wait before prompting again.')?></p>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Push Triggers', 'web-push'); ?></th>
<td>
<fieldset>
  <?php foreach($allowed_triggers as $trigger): ?>
  <label><input type="checkbox" name="webpush_triggers[]" value="<?php echo esc_attr($trigger['key']); ?>" <?php echo in_array($trigger['key'], $triggers_option) ? 'checked' : ''; ?> /> <?php _e($trigger['text'], 'web-push'); ?></label><br />
  <?php endforeach; ?>
</fieldset>
</td>
</tr>
</table>


<table class="form-table">
<a href="#gcm" name="gcm" style="text-decoration:none;"><h2 class="title"><?php _e('GCM (Google Chrome) Configuration', 'web-push'); ?></h2></a>
<p><?php _e('To set up GCM (Google Chrome) support, you need to follow the steps outlined <a href="https://developers.google.com/web/fundamentals/getting-started/push-notifications/step-04" target="_blank">here</a>. Once you have the required values, insert them in this section.'); ?></p>

<tr>
<th scope="row"><label for="webpush_gcm_key"><?php _e('GCM API Key', 'web-push'); ?></label></th>
<td><input name="webpush_gcm_key" type="text" value="<?php echo $gcm_key_option; ?>" class="regular-text code" /></td>
</tr>

<tr>
<th scope="row"><label for="webpush_gcm_sender_id"><?php _e('GCM Project Number', 'web-push'); ?></label></th>
<td><input name="webpush_gcm_sender_id" type="text" value="<?php echo $gcm_sender_id_option; ?>" class="code" /></td>
</tr>
</table>

<?php submit_button(__('Save Changes'), 'primary'); ?>

</form>

</div>

<?php
  }
}
?>
