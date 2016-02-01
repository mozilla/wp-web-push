=== Web Push ===
Contributors: marco-c-1
Tags: web push, push, notifications, web push notifications, push notifications, desktop notifications, mobile notifications
Requires at least: 3.5
Tested up to: 4.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send push notifications to your visitors! Uses the W3C Push API.

== Description ==
This plugin uses the [W3C Push API](https://www.w3.org/TR/push-api/) to allow you to send push notifications to anyone who visits your site and signs up for notifications.

Once you\'ve installed this plugin, anyone visiting your site/app in [browsers that support the W3C Push API](http://caniuse.com/#feat=push-api) will be asked whether he/she wants to receive notifications from your site. You will immediately be able to send push notifications to anyone who consents.

== Installation ==
1. Download and install the plugin from the WordPress.org plugin directory
1. Activate the plugin through the \"Plugins\" menu in WordPress

Follow these additional [steps for setting up GCM (Google Chrome) support](https://developer.mozilla.org/en-US/docs/Web/API/Push_API/Using_the_Push_API#Extra_steps_for_Chrome_support)

== Frequently Asked Questions ==
= What browsers support the W3C Push API? =
[browser support for the W3C Push API](http://caniuse.com/#feat=push-api) currently exists in Firefox, Chrome, and Chrome for Android, with others likely to follow

= What will push notifications look like? =
That depends on the browser! Each browser will display your notifications somewhat differently, but in general the notifications will look appropriate for the device/OS/browser on which they are displayed

= When / how often will visitors be asked about accepting push notifications? =
Each browser has its own heuristics for deciding when to ask its user about push notifications from your site. Most of them won\'t ask the first time someone visits your site, and most also implement a \"never ask me again\" response.

== Changelog ==
= 0.1 =
* Initial release.
