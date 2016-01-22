=== Web Push ===
Contributors: marco-c-1
Tags: web push, push, notifications, web push notifications, push notifications, desktop notifications, mobile notifications
Requires at least: 3.5
Tested up to: 4.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Web Push notifications for your website.

== Installation ==
1. Install the plugin from the WordPress.org plugin directory
2. Activate the plugin

To set up GCM (Google Chrome) support, you need to follow the steps outlined [here](https://developer.mozilla.org/en-US/docs/Web/API/Push_API/Using_the_Push_API#Extra_steps_for_Chrome_support):

1. Navigate to the [Google Developers Console](https://console.developers.google.com/) and set up a new project.
2. Go to your project\'s homepage (ours is at https://console.developers.google.com/project/push-project-978, for example), then
  1. Select the Enable Google APIs for use in your apps option.
  2. In the next screen, click Cloud Messaging for Android under the Mobile APIs section.
  3. Click the Enable API button.
3. Now you need to make a note of your project number and API key because you\'ll need them later. To find them:
  1. Project number: click Home on the left; the project number is clearly marked at the top of your project\'s home page.
  2. API key: click Credentials on the left hand menu; the API key can be found on that screen.
