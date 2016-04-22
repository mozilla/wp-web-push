<?php

use Base64Url\Base64Url;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;

class WebPush_Admin {
  private static $instance;
  private $options_page, $tools_page;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    add_action('admin_notices', array($this, 'on_admin_notices'));
    add_action('add_meta_boxes', array($this, 'on_add_meta_boxes'));
    add_action('wp_ajax_webpush_get_public_key', array($this, 'handle_get_public_key'));
  }

  function get_public_key($privateKey) {
    $publicKeyVal = __('Your private key is invalid.', 'web-push');

    error_reporting(E_ERROR);

    try {
      $privKeySerializer = new PemPrivateKeySerializer(new DerPrivateKeySerializer());
      $privateKeyObject = $privKeySerializer->parse($privateKey);
      $publicKeyObject = $privateKeyObject->getPublicKey();
      $pointSerializer = new UncompressedPointSerializer(EccFactory::getAdapter());
      $publicKeyVal = Base64Url::encode(hex2bin($pointSerializer->serialize($publicKeyObject->getPoint())));
    } catch (Exception $e) {
      // Ignore exceptions while getting the public key from the private key.
    }

    error_reporting(E_ALL);

    return $publicKeyVal;
  }

  function handle_get_public_key() {
    check_ajax_referer('vapid_nonce');

    $privateKey = '';
    if (isset($_POST['privateKey'])) {
      $privateKey = $_POST['privateKey'];
    }

    echo $this->get_public_key($privateKey);

    wp_die();
  }

  function on_add_meta_boxes() {
    add_meta_box('webpush_send_notification', __('Web Push', 'web-push'), array($this, 'meta_box'), 'post', 'side');
  }

  function meta_box($post) {
    wp_nonce_field('webpush_send_notification', 'webpush_meta_box_nonce');

    $notificationsEnabled = get_post_meta($post->ID, '_notifications_enabled', true);

    echo '<label><input name="webpush_send_notification" type="checkbox" ';
    if ($notificationsEnabled !== 'd' &&
        (in_array('update-post', get_option('webpush_triggers')) or
        ($post->post_status !== 'publish' and in_array('new-post', get_option('webpush_triggers'))))) {
      echo 'checked ';
    }
    echo '/>' . __('Send push notification', 'web-push') . '</label>';
  }

  function isSSL() {
    return is_ssl() ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on');
  }

  function on_admin_notices() {
    if (!current_user_can('manage_options')) {
      // There's no point in showing notices to users that can't modify the options.
      return;
    }

    if (!$this->isSSL()) {
      echo '<div class="error"><p>' . __('You need to serve your website via HTTPS to make the Web Push plugin work.', 'web-push') . '</p></div>';
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
        $title = $post->post_title;
        if (strlen($title) > 20) {
          $title = substr($post->post_title, 0, 20) . '…';
        }
        $labels[] = $title;
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
      wp_enqueue_style('webpush-dashboard-theme', plugins_url('lib/style/dashboard.css', __FILE__));
    } else if ($hook === $this->options_page) {
      wp_enqueue_media();

      $icon_option = get_option('webpush_icon');
      $custom_icon_url = '';
      if (isset($_POST['webpush_icon']) && $_POST['webpush_icon'] === 'custom') {
        $custom_icon_url = $_POST['webpush_icon_custom'];
      } else if ($icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon') {
        $custom_icon_url = $icon_option;
      }

      wp_enqueue_style('wp-color-picker');
      wp_register_script('options-page-script', plugins_url('lib/js/options-page.js', __FILE__), array('wp-color-picker'));
      wp_localize_script('options-page-script', 'webPushOptions', array(
        'blog_title' => get_bloginfo('name'),
        'blog_icon' => function_exists('get_site_icon_url') ? get_site_icon_url() : '',
        'custom_icon' => $custom_icon_url,
        'post_icon_placeholder' => plugins_url('lib/placeholder.png', __FILE__),
        'vapid_show_button' => esc_attr__('Show advanced options', 'web-push'),
        'vapid_hide_button' => esc_attr__('Hide advanced options', 'web-push'),
        'vapid_nonce' => wp_create_nonce('vapid_nonce'),
      ));
      wp_enqueue_script('options-page-script');
    }
  }

  function add_dashboard_widgets() {
    wp_add_dashboard_widget('wp-web-push_dashboard_widget', __('Web Push', 'web-push'), array($this, 'dashboard_widget'));
  }

  function dashboard_widget() {
    $prompt_count = get_option('webpush_prompt_count');
    $accepted_prompt_count = get_option('webpush_accepted_prompt_count');
    printf(_n('<b>%s</b> user prompted to receive notifications.', '<b>%s</b> users prompted to receive notifications.', $prompt_count, 'web-push'), number_format_i18n($prompt_count));
    echo ' ';
    printf(_n('<b>%s</b> user confirmed.', '<b>%s</b> users confirmed.', $accepted_prompt_count, 'web-push'), number_format_i18n($accepted_prompt_count));
    echo '<br>';
    $current_subscription_number = WebPush_DB::count_subscriptions();
    printf(_n('<b>%s</b> user currently subscribed.', '<b>%s</b> users currently subscribed.', $current_subscription_number, 'web-push'), number_format_i18n($current_subscription_number));
    echo '<br><br>';
    echo '<div style="min-height:400px;"><canvas id="notifications-chart"></canvas></div>';
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function on_admin_menu() {
    $this->options_page = add_options_page(__('Web Push Settings', 'web-push'), __('Web Push', 'web-push'), 'manage_options', 'web-push-options', array($this, 'options'));

    $this->tools_page = add_management_page(__('Send a custom push notification', 'web-push'), __('Send a custom push notification', 'web-push'), 'manage_options', 'web-push-tools', array($this, 'tools'));
  }

  private function sanitize_hex_color($color) {
    if (empty($color) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
      return '';
    }

    return $color;
  }

  public function options() {
    $allowed_triggers = WebPush_Main::$ALLOWED_TRIGGERS;
    $title_option = get_option('webpush_title');
    $icon_option = get_option('webpush_icon');
    $min_visits_option = intval(get_option('webpush_min_visits'));
    $subscription_button_option = get_option('webpush_subscription_button');
    $subscription_button_color_option = get_option('webpush_subscription_button_color');
    $prompt_interval_option = get_option('webpush_prompt_interval');
    $triggers_option = get_option('webpush_triggers');
    $gcm_key_option = get_option('webpush_gcm_key');
    $gcm_sender_id_option = get_option('webpush_gcm_sender_id');
    $generate_manifest_option = get_option('webpush_generate_manifest');
    if (USE_VAPID) {
      // Regenerate VAPID info if needed (for example, when the user installs the needed
      // dependencies).
      WebPush_DB::generate_vapid_options();
      $vapid_key_option = get_option('webpush_vapid_key');
      $vapid_subject_option = get_option('webpush_vapid_subject');
      $vapid_audience_option = get_option('webpush_vapid_audience');
    }

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

      $subscription_button_option = isset($_POST['webpush_subscription_button']) ? true : false;

      $subscription_button_color_option = $this->sanitize_hex_color($_POST['webpush_subscription_button_color']);
      if (!$subscription_button_color_option) {
        wp_die(__('Invalid color for the subscription button', 'web-push'));
      } else if ($subscription_button_color_option !== get_option('webpush_subscription_button_color')) {
        update_option('webpush_subscription_button_color', $subscription_button_color_option);
        Mozilla\WP_Serve_File::getInstance()->invalidate_files(array('subscription_button.css', 'bell.svg'));
      }

      $prompt_interval_option = intval($_POST['webpush_prompt_interval']);

      $triggers_option = isset($_POST['webpush_triggers']) ? $_POST['webpush_triggers'] : array();
      foreach ($triggers_option as $trigger_option) {
        if (!WebPush_Main::get_trigger_by_key_value('key', $trigger_option)) {
          wp_die(sprintf(__('Invalid value in Push Triggers: %s'), $trigger_option), 'web-push');
        }
      }

      $gcm_key_option = $_POST['webpush_gcm_key'];
      $gcm_sender_id_option = $_POST['webpush_gcm_sender_id'];
      if ($gcm_sender_id_option != get_option('webpush_gcm_sender_id')) {
        $manifestGenerator = Mozilla\WebAppManifestGenerator::getInstance();
        $manifestGenerator->set_field('gcm_sender_id', $gcm_sender_id_option);
        $manifestGenerator->set_field('gcm_user_visible_only', true);
      }
      $generate_manifest_option = isset($_POST['webpush_generate_manifest']) ? true : false;

      if (USE_VAPID) {
        $vapid_key_option = $_POST['webpush_vapid_key'];
        $vapid_subject_option = $_POST['webpush_vapid_subject'];
        $vapid_audience_option = $_POST['webpush_vapid_audience'];
      }

      update_option('webpush_title', $title_option);
      update_option('webpush_icon', $icon_option);
      update_option('webpush_min_visits', $min_visits_option);
      update_option('webpush_subscription_button', $subscription_button_option);
      update_option('webpush_prompt_interval', $prompt_interval_option);
      update_option('webpush_triggers', $triggers_option);
      update_option('webpush_gcm_key', $gcm_key_option);
      update_option('webpush_gcm_sender_id', $gcm_sender_id_option);
      update_option('webpush_generate_manifest', $generate_manifest_option);
      if (USE_VAPID) {
        update_option('webpush_vapid_key', $vapid_key_option);
        update_option('webpush_vapid_audience', $vapid_audience_option);
        update_option('webpush_vapid_subject', $vapid_subject_option);
      }

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
<h2><?php _e('Web Push Settings', 'web-push'); ?></h2>

<form method="post" action="" enctype="multipart/form-data">
<h2 class="title"><?php _e('Notification UI Options', 'web-push'); ?></h2>
<p><?php _e('In this section, you can customize the information that appears in the notifications that will be shown to users.<br> Here\'s a preview of the notification:', 'web-push'); ?></p>

<div style="border: 1px solid darkgrey; overflow: auto; display: inline-block; background-color: lightgrey;">
<h3 id="notification-title" style="margin-left: 10px;"></h3>
<div style="float: left; margin-left: 10px; margin-bottom:10px;"><img id="notification-icon" style="display: block; max-width: 64px; max-height: 64px;"></div>
<div id="notification-text" style="margin-left: 90px; padding-right: 50px;"><p><?php _e('The title of your post.', 'web-push'); ?></p></div>
</div>

<input type="hidden" name="webpush_form" value="submitted" />

<table class="form-table">
<tr>
<th scope="row"><?php _e('Title', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Use the Site Title', 'web-push'); ?></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Custom:'); ?></label>
<input type="text" id="webpush_title_custom" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : esc_attr__('Your custom title', 'web-push'); ?>" class="regular-text" />
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
<br />
<?php
  }
?>
<label><input type="radio" name="webpush_icon" value="custom" <?php echo $icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon' ? 'checked' : ''; ?> /> <?php _e('Custom:'); ?></label>
<input type="hidden" id="webpush_icon_custom" name="webpush_icon_custom" value="<?php echo $icon_url; ?>" />
<input type="button" class="button" id="webpush_icon_custom_button" value="<?php esc_attr_e('Select'); ?>"></input>
</fieldset>
</td>
</tr>
</table>


<h2 class="title"><?php _e('Subscription Behavior', 'web-push'); ?></h2>
<p><?php _e('In this section, you can customize the subscription behavior and tailor it to your site. We suggest limiting automatic prompting to avoid nagging users and always giving the option to subscribe/unsubscribe through the subscription button.', 'web-push'); ?></p>

<table class="form-table">
<tr>
<th scope="row"></th>
<td>
<object id="webpush_subscription_button_svg" data="<?php echo Mozilla\WP_Serve_File::get_relative_to_host_root_url('bell.svg'); ?>" type="image/svg+xml" style="max-width:64px;max-height:64px;"></object><br>
<input type="text" value="<?php echo $subscription_button_color_option; ?>" name="webpush_subscription_button_color" class="webpush_subscription_button_color" data-default-color="#005189" /><br>
<label><input type="checkbox" name="webpush_subscription_button" <?php echo $subscription_button_option ? 'checked' : ''; ?> /> <?php _e('Show subscription icon', 'web-push'); ?></label>
<p class="description"><?php _e('A button in the bottom-right corner of the page that the user can use to subscribe/unsubscribe. We suggest enabling it to offer an easy way for users to manage their subscription.', 'web-push')?></p>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Automatic prompting', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_min_visits" value="0" <?php echo $min_visits_option === 0 ? 'checked' : ''; ?> /> <?php _e('Ask the user to register as soon as he visits the site', 'web-push'); ?></label><br />
<label><input type="radio" name="webpush_min_visits" value="custom" <?php echo $min_visits_option !== 0 && $min_visits_option !== -1 ? 'checked' : ''; ?> /> <?php _e('Ask the user to register after N visits:', 'web-push'); ?></label>
<input type="number" name="webpush_min_visits_custom" value="<?php echo $min_visits_option !== 0 && $min_visits_option !== -1 ? $min_visits_option : 3; ?>" class="small-text" /><br />
<label><input type="radio" name="webpush_min_visits" value="-1" <?php echo $min_visits_option === -1 ? 'checked' : ''; ?> /> <?php _e('Never automatically ask the user to register', 'web-push'); ?></label>
</fieldset>
<p class="description"><?php _e('Limiting automatic prompting is suggested to avoid nagging users (unless you know that your visitors are really interested).', 'web-push')?></p>
</td>
</tr>

<tr>
<th scope="row"><label for="webpush_prompt_interval"><?php _e('Interval between prompts', 'web-push'); ?></label></th>
<td><input name="webpush_prompt_interval" type="number" value="<?php echo $prompt_interval_option; ?>" class="small-text" />
<p class="description"><?php _e('If the user declines or dismisses the prompt, this is the time interval (in days) to wait before prompting again.', 'web-push')?></p>
</td>
</tr>
</table>


<h2 class="title"><?php _e('Push Triggers', 'web-push'); ?></h2>
<p><?php _e('Select which events should trigger sending a push notification to users.', 'web-push'); ?></p>

<table class="form-table">
<tr>
<th scope="row"></th>
<td>
<fieldset>
  <?php foreach ($allowed_triggers as $trigger): ?>
  <label><input type="checkbox" name="webpush_triggers[]" id="webpush_trigger_<?php echo $trigger['key']; ?>" value="<?php echo esc_attr($trigger['key']); ?>" <?php echo in_array($trigger['key'], $triggers_option) ? 'checked' : ''; ?> <?php if (array_key_exists('parentKey', $trigger)) { echo 'parent="' . esc_attr($trigger['parentKey']) . '"'; } ?> /> <?php echo $trigger['text']; ?></label><br />
  <?php endforeach; ?>
</fieldset>
<p class="description"><?php _e('N.B.: You can override these options for individual posts when you create/edit them.', 'web-push'); ?></p>
</td>
</tr>
</table>


<h2 class="title"><?php _e('Voluntary Application Server Identification (VAPID)', 'web-push'); ?></h2>
<p><?php _e('VAPID is useful to monitor your push messages. It allows your server to submit information about itself to the push service, which improves application stability, exception handling, and security. <b>It is automatically configured for you.</b>', 'web-push'); ?></p>

<?php
  if (USE_VAPID) {
?>
<input type="button" class="button" id="webpush_vapid_show_config" value="<?php esc_attr_e('Show advanced options', 'web-push'); ?>"></input>
<br>
<table class="form-table" id="vapid_config" style="display:none;">
<tr>
<th scope="row"><label for="webpush_vapid_key"><?php _e('Private Key', 'web-push'); ?></label></th>
<td><textarea name="webpush_vapid_key" id="webpush_vapid_key" type="text" rows="5" cols="65" class="regular-text code"><?php echo $vapid_key_option; ?></textarea>
<p class="description"><?php _e('The private key used to sign your push notifications.', 'web-push')?></p></td>
</tr>

<tr>
<th scope="row"><?php _e('Public Key', 'web-push'); ?></th>
<td><code><b><span id="webpush_vapid_public_key"><?php echo $this->get_public_key($vapid_key_option); ?></span></b></code></td>
</tr>

<tr>
<th scope="row"><label for="webpush_vapid_audience"><?php _e('Audience', 'web-push'); ?></label></th>
<td><input name="webpush_vapid_audience" type="url" value="<?php echo $vapid_audience_option; ?>" class="regular-text code" />
<p class="description"><?php _e('The origin URL of the sender.', 'web-push')?></p></td>
</tr>

<tr>
<th scope="row"><label for="webpush_vapid_subject"><?php _e('Subject', 'web-push'); ?></label></th>
<td><input name="webpush_vapid_subject" type="url" value="<?php echo $vapid_subject_option; ?>" class="regular-text code" />
<p class="description"><?php _e('The primary contact in case something goes wrong.', 'web-push')?></p></td>
</tr>
<?php
  } else {
?>
<p><?php _e('Unfortunately, VAPID can\'t be enabled on your website, because one or more prerequisites are missing.', 'web-push'); ?></p>
<table class="form-table">
<tr>
<th scope="row"><?php _e('VAPID Prerequisites:', 'web-push'); ?></th>
<td>
<ul style="list-style-type: circle;">
<li style="color:<?php echo (version_compare(phpversion(), '5.4') >= 0) ? 'green' : 'red'; ?>;">PHP 5.4+</li>
<li style="color:<?php echo function_exists('mcrypt_encrypt')           ? 'green' : 'red'; ?>;"><?php _e('mcrypt extension', 'web-push'); ?></li>
<li style="color:<?php echo function_exists('gmp_mod')                  ? 'green' : 'red'; ?>;"><?php _e('gmp extension', 'web-push'); ?></li>
</ul>
</td>
</tr>
<?php
  }
?>
</table>


<a href="#gcm" name="gcm" style="text-decoration:none;"><h2 class="title"><?php _e('Google Chrome Support', 'web-push'); ?></h2></a>
<p><?php _e('To configure Google Chrome support, follow the steps in <a href="https://developers.google.com/web/fundamentals/getting-started/push-notifications/step-04" target="_blank">Make a project on the Google Developer Console</a> to configure Google Cloud Messaging (GCM), then copy the <i>GCM API Key</i> and <i>Project Number</i> into the fields below.', 'web-push'); ?></p>

<table class="form-table">
<tr>
<th scope="row"><label for="webpush_gcm_key"><?php _e('GCM API Key', 'web-push'); ?></label></th>
<td><input name="webpush_gcm_key" type="text" value="<?php echo $gcm_key_option; ?>" class="regular-text code" /></td>
</tr>

<tr>
<th scope="row"><label for="webpush_gcm_sender_id"><?php _e('GCM Project Number', 'web-push'); ?></label></th>
<td><input name="webpush_gcm_sender_id" id="webpush_gcm_sender_id" type="text" value="<?php echo $gcm_sender_id_option; ?>" class="code" /></td>
</tr>
</table>

<p><?php _e('The GCM project number should be added to your Web App Manifest. You can either let the plugin generate a manifest for you or add the info to your own manifest (if you already have one). The plugin is compatible with the <a href="https://wordpress.org/plugins/add-to-home-screen">Add to Home Screen plugin</a>. <b>You only need to disable the following option if you have created your own manifest manually</b>.', 'web-push'); ?></p>
<label><input type="checkbox" name="webpush_generate_manifest" id="webpush_generate_manifest" <?php echo $generate_manifest_option ? 'checked' : ''; ?> /> <?php _e('The plugin will automatically create a Web App Manifest for you and add it to your page.', 'web-push'); ?></label>
<br><br>
<p id="webpush_generate_manifest_text" style="display:none;"><?php printf(__('You need to add %s and %s to your manifest', 'web-push'), '<b>"gcm_sender_id": "<span id="webpush_generate_manifest_sender_id_field">%s"</b>', '<b>"gcm_user_visible_only": true</b>'); ?></p>

<?php submit_button(__('Save Changes'), 'primary'); ?>

</form>

</div>

<?php
  }

  public function tools() {
    if (isset($_POST['webpush_form']) && $_POST['webpush_form'] === 'submitted') {
      $title = '';
      if ($_POST['webpush_title'] === 'blog_title') {
        $title = get_bloginfo('name');
      } else if ($_POST['webpush_title'] === 'custom') {
        $title = $_POST['webpush_title_custom'];
      } else {
        wp_die(__('Invalid value for the Notification Title', 'web-push'));
      }

      $icon = '';
      if ($_POST['webpush_icon'] === 'blog_icon') {
        $icon = get_site_icon_url();
      } else if ($_POST['webpush_icon'] === 'custom') {
        $icon = $_POST['webpush_icon_custom'];
      } else {
        wp_die(__('Invalid value for the Notification Icon', 'web-push'));
      }

      WebPush_Main::sendNotification($title, $_POST['webpush_body'], $icon, $_POST['webpush_url'], null);

?>
<div class="updated"><p><strong><?php _e('Notification sent.'); ?></strong></p></div>
<?php
    }

    $title_option = get_option('webpush_title');
    $icon_option = get_option('webpush_icon');

?>
<div class="wrap">
<h2><?php _e('Send a custom push notification', 'web-push'); ?></h2>

<form method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="webpush_form" value="submitted" />
<table class="form-table">
<tr>
<th scope="row"><?php _e('Title', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_title" value="blog_title" <?php echo $title_option === 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Use the Site Title', 'web-push'); ?></label><br />
<label><input type="radio" name="webpush_title" value="custom" <?php echo $title_option !== 'blog_title' ? 'checked' : ''; ?> /> <?php _e('Custom:'); ?></label>
<input type="text" id="webpush_title_custom" name="webpush_title_custom" value="<?php echo $title_option !== 'blog_title' ? $title_option : esc_attr__('Your custom title', 'web-push'); ?>" class="regular-text" />
</fieldset>
</td>
</tr>

<tr>
<th scope="row"><?php _e('Icon', 'web-push'); ?></th>
<td>
<fieldset>
<label><input type="radio" name="webpush_icon" value="" <?php echo $icon_option === '' ? 'checked' : ''; ?> /> <?php _e('Don\'t use any icon', 'web-push'); ?></label>
<br />
<?php
  if (function_exists('get_site_icon_url')) {
?>
<label><input type="radio" name="webpush_icon" value="blog_icon" <?php echo $icon_option === 'blog_icon' ? 'checked' : ''; ?> /> <?php _e('Use the Site Icon', 'web-push'); ?></label>
<br />
<?php
  }
?>
<label><input type="radio" name="webpush_icon" value="custom" <?php echo $icon_option !== 'blog_icon' && $icon_option !== '' && $icon_option !== 'post_icon' ? 'checked' : ''; ?> /> <?php _e('Custom:'); ?></label>
<input type="hidden" id="webpush_icon_custom" name="webpush_icon_custom" value="<?php echo $icon_url; ?>" />
<input type="button" class="button" id="webpush_icon_custom_button" value="<?php esc_attr_e('Select'); ?>"></input>
</fieldset>
</td>
</tr>

<tr>
<th scope="row"><label for="webpush_body"><?php _e('Body', 'web-push'); ?></label></th>
<td><textarea name="webpush_body" type="text" rows="5" cols="65" class="regular-text"><?php esc_attr_e('Notification Body', 'web-push'); ?></textarea></td>
</tr>

<tr>
<th scope="row"><label for="webpush_url"><?php _e('URL', 'web-push'); ?></label></th>
<td><input name="webpush_url" type="text" value="<?php echo esc_attr(home_url()); ?>" class="regular-text code" />
<p class="description"><?php _e('The URL to open when users click on the notification.', 'web-push')?></p></td>
</tr>
</table>

<?php submit_button(__('Send notification'), 'primary'); ?>

</form>
<?php
  }
}
?>
