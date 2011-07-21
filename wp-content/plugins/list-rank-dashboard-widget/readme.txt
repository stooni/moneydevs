=== List Rank Dashboard Widget ===
Contributors: Marcel Bokhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=AJSBB7DGNA3MJ&lc=US&item_name=List%20Rank%20Dashboard%20Widget%20WordPress%20Plugin&item_number=Marcel%20Bokhorst&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: admin, dashboard, widget, google, stats, statistics, PageRank, page rank, alexa, backlinks, seo, ajax
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 1.7

Displays the rankings of a configurable list of sites in a dashboard widget; includes a shortcode to display individual rankings

== Description ==

Displays the rankings of a configurable list of sites in a dashboard widget. Includes a shortcode to display individual rankings in post, pages and sidebar widgets (see FAQ for usage).

Currently the following rankings can be displayed for any number of sites:

* [Google PageRank](http://en.wikipedia.org/wiki/PageRank "Google PageRank")
* Google Backlinks (Requires a Google API Key)
* [Alexa Rank](http://en.wikipedia.org/wiki/Alexa_Internet "Alexa Rank")
* Yahoo! Backlinks (Requires a Yahoo! Application ID)
* Delicious Posts

Without configuration the Google PageRank, Alexa Rank and the number of Delicious Posts are displayed for the current site.

**This plugin requires at least PHP 5.**

Please report any issue you have on the [support page](http://blog.bokhorst.biz/4014/computers-en-internet/wordpress-plugin-list-rank-dashboard-widget/ "Marcel's weblog"), so I can at least try to fix it. If you rate this plugin low, please [let me know why](http://blog.bokhorst.biz/4014/computers-en-internet/wordpress-plugin-list-rank-dashboard-widget/#respond "Marcel's weblog").

See my [other plugins](http://wordpress.org/extend/plugins/profile/m66b "Marcel Bokhorst").

== Installation ==

*Using the WordPress dashboard*

1. Login to your weblog
1. Go to Plugins
1. Select Add New
1. Search for List Rank Dashboard Widget
1. Select Install
1. Select Install Now
1. Select Activate Plugin

*Manual*

1. Download and unzip the plugin
1. Upload the entire *list-rank-dashboard-widget/* directory to the */wp-content/plugins/* directory
1. Activate the plugin through the Plugins menu in WordPress

== Frequently Asked Questions ==

= How often are the rankings updated? =

Once daily or when visiting the settings.

= How can I use the shortcode to display rankings? =

The general format is:

*[list_rank name="ranking name" url="site address"]*

Curently available ranking names:

* GooglePR
* GoogleBL
* AlexaRank
* YahooBL
* Delicious

The default ranking name is *GooglePR* and the default url the current page url. Note that ranking names are case sensitive.

If you want to use the shortcode in a sidebar widget, you have to enabled shortcode processing in sidebar widgets using the settings.

= Who can use the dashboard widget? =

By default users with the capability *read* (subscribers), but this can be changed with a setting.

= Who can configure the dashboard widget? =

Users with the capability *manage_options* (administrators).

= How can I change the styling? =

1. Copy *wp-list-rank.css* to your upload directory to prevent it from being overwritten by an update
2. Change the style sheet to your wishes

= Where can I ask questions, report bugs and request features? =

You can write comments on the [support page](http://blog.bokhorst.biz/4014/computers-en-internet/wordpress-plugin-list-rank-dashboard-widget/ "Marcel's weblog").

== Screenshots ==

1. The Link Rank Dashboard Widget

== Changelog ==

= 1.7 =
* Replaced *cURL* and *file_get_contents* by *wp_remote_get*
* Added *Sustainable Plugins Sponsorship Network* again
* Updated Dutch and Flemisch translations (nl\_NL/nl\_BE)

= 1.6 =
* Bugfix for undefined function register_setting

= 1.5 =
* Tested with WordPress 3.2
* Fixed some notices
* Removed *Sustainable Plugins Sponsorship Network*

= 1.4 =
* Added option for debugging
* Fixed automatic update (pseudo cron)
* Tested with WordPress version 3.1 RC 3

= 1.3 =
* Fixed automatic update (pseudo cron)
* Tested with WordPress version 3.1 beta 1

= 1.2 =
* Fixed minimum required capability

= 1.1 =
* Fetching rankings only when widget open

= 1.0 =
* Small bugfixes

= 0.6 =
* 'I have donated' removes donate link/button

= 0.5 =
* Added an option to set minimum required capability to use dashboard widget

= 0.4 =
* Removed cache clear when visiting settings

= 0.3 =
* Corrected handling of https sites

= 0.2 =
* Added Dutch and Flemisch translations (nl\_NL/nl\_BE)

= 0.1 =
* Initial version

= 0.0 =
* Development version

== Upgrade Notice ==

= 1.7 =
Compatibility

= 1.6 =
Bugfix

= 1.5 =
Compatibility

= 1.3 =
Bug fix

= 1.2 =
Bug fix

= 1.1 =
New feature: only fe tch when widget open

= 1.0 =
Small bug fixes

= 0.6 =
New feature: remove donate link/button

= 0.5 =
New feature: minimum capability

= 0.4 =
Usability

= 0.3 =
Bugfix

= 0.2 =
Translations
