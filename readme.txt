=== BestWebSoft Google Analytics ===
Contributors: bestwebsoft
Donate link: https://www.2checkout.com/checkout/purchase?sid=1430388&quantity=1&product_id=94
Tags: add tracking code, analytics, analitics, display statistics report, googel, googgle, gogle, gogole, google, google analytics, google analytics loggins, google analytics plugin, google analytics stats, group stats, group statistics, metrics, page views, retrieve metrics, statistics, statistics report, stats, tracking, visit duration, visits statistics, visitors, visits, web properties, webproperties.
Requires at least: 3.3
Tested up to: 4.1
Stable tag: 1.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to retrieve basic stats from Google Analytics account and add the necessary tracking code to your blog.

== Description ==

This plugin establishes a connection with your Google Analytics account and allows you to retrieve basic metrics from web properties you have on this account. Statistical report appears as a table or as a line chart on the plugin settings page in the WordPress Admin area. User is able to select which information to display in the report. Presently a full report comprises the following metrics: total number of visitors for the requested time period, the percentage of visits by people who have never visited your property before, total number of sessions, the percentage of single-page visits, the average visit duration, the total number of pageviews, the average number of pages viewed during a visit. Moreover, this plugin makes it easy to place the necessary tracking code to enable Google Analytics logging on your WordPress blog.

http://www.youtube.com/watch?v=u6GCmG2SYIg

<a href="http://wordpress.org/plugins/bws-google-analytics/faq/" target="_blank">FAQ</a>

<a href="http://support.bestwebsoft.com" target="_blank">Support</a>

= Features = 

* Actions: Connect to your Google Analytics account and get Google Analytics stats.
* Display: Group stats by day, month and year.
* Actions: See data for different time periods in your reports.
* Actions: Add tracking code to enable Google Analytics logging on your blog.

= Recommended Plugins =

The author of the BestWebSoft Google Analytics also recommends the following plugins:

* <a href="http://wordpress.org/plugins/updater/">Updater</a> - This plugin updates WordPress core and the plugins to the recent versions. You can also use the auto mode or manual mode for updating and set email notifications.
There is also a premium version of the plugin <a href="http://bestwebsoft.com/products/updater/?k=b0536eca91f29f7603d42d53f5fd3990">Updater Pro</a> with more useful features available. It can make backup of all your files and database before updating. Also it can forbid some plugins or WordPress Core update.

= Translation =

* Russian (ru_RU)
* Ukrainian (uk)

If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> for <a href="http://support.bestwebsoft.com" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for work with PO and MO files  <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in another languages won't be accepted.

If you notice any bugs in the plugin's work, you can notify us about it and we'll investigate and fix the issue then. Your request should contain URL of the website, issues description and WordPress admin panel credentials.
Moreover we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then.
We can fix some things for free for the users who provide translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation == 

1. Upload the `bws-google-analytics` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin using the 'Plugins' menu in your WordPress admin panel.
3. Plugin settings are located in the plugin settings page.

== Frequently Asked Questions ==

= Can I retrieve statistics using this plugin without having any Google Analytics account? =

No, you can't. This plugin allows you to retrieve statistics from Google Analytics account. It doesn't generate its own statistics.

= Can I create a new Google Analytics account or a new webproperty from within the admin area using this plugin? = 

No, you can't. Google Analytics provides developer access to the configuration data through the Management API, which is a read-only API for account and configuration data.

= How can I add tracking code? =

After you have created a new webroperty you will get a tracking code. If you want to add tracking code to your blog you will need to copy Tracking ID that looks like UA-xxxxx-y, paste it to the "Tracking Code" field, check "Add tracking Code To Your Blog" checkbox (if not checked) and click "Save Changes" button.

= I want to collect statistic from my blog, what should I do? =

If you want to enable tracking and collect statistics from your blog, you need to past tracking code to your blog. To do this you should follow next steps:
1. Sign in to your Google Analytics account. Click Admin in the menu bar at the top of any page.
2. In the Account column, select the account from the dropdown that you want to add the property to.
3. In the dropdown in the Property column, click Create new property.
4. Select Website
5. Select the tracking method. Click either Universal Analytics or Classic Analytics. We strongly recommend Universal Analytics.
6. Enter the name of your Wordpress blog.
7. Enter the Website URL of your blog
8. Select the Industry Category.
9. Select the Reporting Time Zone.
10. Click Get Tracking ID.
11. Copy Tracking ID that looks like UA-xxxxx-y and past it to the "Tracking Code" field in the "Tracking Code & Reset" tab of this plugin.
12. Check "Add tracking Code To Your Blog" checkbox (if not checked) and click "Save Changes" button.

= I have added tracking code to my blog using this plugin. Now if I deactivate this plugin, will Google Analytics continue logging my blog? =

No, if you deactivate plugin tracking code will be deleted from your blog.

= What date range does the line chart cover? =

Line chart displays stats for the last year, 6 months, 3 months, 1 month, 5 days and 1 day.

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed yet on our forum (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If no, please provide the following data along with your problem's description:
1. The link to the page where the problem occurs
2. The name of the plugin and its version. If you are using a pro version - your order number.
3. The version of your WordPress installation
4. Copy and paste into the message your system status report. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit" target="_blank">Instuction on System Status</a>

== Screenshots ==

1. Google Analytics Authentication.
2. Line Chart Tab in the plugin settings page.
3. Table Chart Tab in the plugin settings page.
4. Tracking Code and Reset Tab in the plugin settings page.

== Changelog == 

= V1.6.1 - 06.01.2014 =
* Update: BWS plugins section was updated.

= V1.6 - 26.09.2014 =
* New : We added an option to save tracking code without adding it to blog.
* Update : We updated all functionality for WordPress 4.0.
* Bugfix : We fixed the script that adds tracking code and moved it to the bottom of a page to speed the page loading process.
* Bugfix : Security Exploit was fixed.

= V1.5 - 13.05.2014 =
* New : We added Ukrainian language.
* Update : We updated all functionality for wordpress 3.9.1.

= V1.4 - 12.03.2014 =
* Update: Screenshots were updated.
* Update: Readme file was updated.
* Bugfix: Plugin optimization was done.
* Update: BWS plugins section was updated.

= V1.3 - 06.03.2014 = 
* Bugfix: Fixed fatal error that occured during the plugin activation.

= V1.2 - 28.02.2014 = 
* Update: Updated UI.
* Bugfix: Fixed ajax functions issues.
* Update: Updated instructions.

= V1.1 - 20.02.2014 = 
* Update: Updated UI.
* Bugfix: Fixed form validation issues.
* Update: Updated instructions.

= V1.0 - 13.02.2014 = 
* NEW: Ability to retrieve basic statistical information from Google Analytics account was added.

== Upgrade Notice ==

= V1.6.1 =
BWS plugins section was updated.

= V1.6 =
We added an option to save tracking code without adding it to blog. We updated all functionality for WordPress 4.0. We fixed the script that adds tracking code and moved it to the bottom of a page to speed the page loading process. Security Exploit was fixed.

= V1.5 =
We added Ukrainian language. We updated all functionality for wordpress 3.9.1.

= V1.4 =
Plugin optimization was done. BWS plugins section was updated. Readme file was updated. Screenshots were updated.

= V1.3 =
Fixed fatal error that occured during the plugin activation.

= V1.2 = 
Updated UI and instructions. Fixed ajax functions issues.

= V1.1 = 
Updated UI and instructions. Fixed form validation issues.

= V1.0 =
Ability to retrieve basic statistical information from Google Analytics account was added.
