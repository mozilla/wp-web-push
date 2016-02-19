=== Web Push ===
Contributors: marco-c-1, mozillawebapps
Tags: web push, push, notifications, web push notifications, push notifications, desktop notifications, mobile notifications
Requires at least: 3.5
Tested up to: 4.4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send push notifications to your visitors! Uses the W3C Push API.

== Description ==
This plugin uses the [W3C Push API](https://www.w3.org/TR/push-api/) to allow you to send push notifications to anyone who visits your site and signs up for notifications.

Once you've installed and configured this plugin, anyone visiting your site/app in [browsers that support the W3C Push API](http://caniuse.com/#feat=push-api) (Firefox and Chrome, at the time of writing) will be asked whether they want to receive notifications from your site. You will immediately be able to send push notifications to anyone who consents.

Configure the plugin in the Settings > Web Push section of your WordPress installation. Here you can set various pieces of configuration, including:

- The title for push notifications that appear from your site
- When to attempt to register your visitor for push notifications (e.g. after how many visits)
- Which types of events should trigger push notifications (e.g. new posts, comments, updates to posts)

N.B.: This plugin requires your website to be served via HTTPS. Indeed, for security reasons, browsers enforce the HTTPS policy for the Push API and for Service Workers.

== Installation ==
1. Download and install the plugin from the WordPress.org plugin directory
2. Activate the plugin through the "Plugins" menu in WordPress

Follow these additional [steps for setting up GCM (Google Chrome) support](https://developers.google.com/web/fundamentals/getting-started/push-notifications/step-04). In the Settings > Web Push section of your WordPress installation are configuration settings for this plugin; there you will find additional information for setting up GCM.

== Frequently Asked Questions ==
= What browsers support the W3C Push API? =
[browser support for the W3C Push API](http://caniuse.com/#feat=push-api) currently exists in Firefox, Chrome, and Chrome for Android, with others likely to follow

= What will push notifications look like? =
That depends on the browser! Each browser will display your notifications somewhat differently, but in general the notifications will look appropriate for the device/OS/browser on which they are displayed

= When / how often will visitors be asked about accepting push notifications? =
The plugin is configurable; it is possible to modify when a visitor is prompted to accept push notifications (e.g. on the third visit to the site). Additionally, browsers may decide to suppress this notification, for example, if a user at one point selected a "never ask me again" response.

== Screenshots ==
1. Firefox prompt dialog on Mac.
2. Firefox notification on Mac.
3. Chrome prompt dialog on Android.
4. Chrome notification on the Android lockscreen.
5. Statistics in the dashboard.

== Changelog ==
= 0.0.8 =
* Fixed an update bug.

= 0.0.7 =
* Improved update process.

= 0.0.5 =
* Add meta box to choose whether to send notifications or not for each post.
* Improve statistics interface.
* Warn if the plugin is used without SSL.
* Make the plugin work when WordPress is behind a SSL proxy.

= 0.0.4 =
* Improved admin interface.
* Support coexistance with other plugins using Service Workers.

= 0.0.3 =
* Show graph for notification statistics in the WordPress Dashboard.

= 0.0.2 =
* Support more detailed analytics (number of notifications sent and opened per post).
* Workaround issues with WordPress running behind a HTTPS proxy.

= 0.0.1 =
* Initial release.
