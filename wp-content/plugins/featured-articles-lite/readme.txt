=== Featured Articles Lite ===
Contributors: constantin.boiangiu
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3999592
Tags: slider, featured, articles, posts, pages
Requires at least: 3.1
Tested up to: 3.1.2
Stable tag: trunk

Put featured posts or pages into a fancy JavaScript slider that can be set to display on any category page, page or homepage.

== Description ==

Featured Article Lite allows easy placement into your blog pages of an animated slider displaying featured posts or pages according to your settings. Sliders displaying posts can be placed in various ways: automatically on your blog home page or category page, as a widget, using shortcodes to display the featured posts directly inside the content of any post or page or by manually placing a small PHP code into your theme files.

Almost every aspect can be customized, from the way the JavaScript works (duration of animation effect, mouse wheel navigation, automatic sliding with configurable time delay and so on ) to content displayed into the slider (featured posts, ordered by date, order by number of comments, random order). Also, it comes with 4 default themes and gives the possibility to create new themes for it.  

Features

- Multiple sliders management
- Can display as widget
- Can display manually set featured post
- SEO friendly
- Autoslide
- Automatic or manual placement of featured posts
- Set the slider to animate at any interval when your visitors come to your website.
- Animation direction
- Make it yours! Choose between slides coming from top or from left to mimic a vertical or horizontal slider.
- Themes - 4 default themes available for your featured posts; easy implementation for new themes (a detailed guide on creating themes can be found [here](http://www.php-help.ro/how-to/featured-articles-lite-how-to-create-custom-themes/ "Featured Articles Lite – how to create custom themes"))
- Options - various options available, from featured post thumbnail display to categories to gather articles from and JavaScript settings, all available from Wordpress admin
- Navigation - Make a choice between forward-backwards navigation and individual navigation of your featured posts or use both with only 2 clicks. Also, mouse wheel navigation available.

More information can be found on [php-help.ro](http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/ "Wordpress Featured Articles plugin").

== Installation ==

If you are upgrading to the current version, make sure you backup all the files of the previous installation. After you backup, go to wp-admin and disable the plugin. With a FTP client, delete the plugin completely.

1. Disable the previous installation of Featured Articles Lite
2. FTP to plugins directory of your blog and delete the plugin. 
1. Download and extract folder featured-articles-lite
2. Upload the whole *featured-articles-lite* folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to WP Admin->FA Lite->Edit/Add and configure your first slider
5. To display the slider, you can either choose sections from admin ( homepage, pages or categories ), display the newly created slider as a widget or place the following code in your template: 

`<?php FA_display_slider(YOUR SLIDER ID HERE); ?>`

For your convenience, once you save a slider, under Manual Placement you will see the code needed to be placed into your theme files to display it.

6. To display only certain posts into the slider, set under Display order: Featured posts. After you select the option, open the posts or pages you want to set as featured for editing and from the panel from the right sidebar (in post edit mode) follow the instructions.

For any clarifications, please read or leave a comment on [WP featured articles Lite homepage](http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/ "WP Featured Articles plugin")

== Screenshots ==

1. Theme Light
2. Theme Dark
3. Sideway titles navigation
4. Theme Smoke (for this theme, in settings set slider and thumbnail size to have same values)

== Changelog ==

= 2.3.7 =
* Solved bug that disabled custom image placement on post/page when Google Analyticator plugin was installed
* Solved bug that caused automatic image detection within post content not to be performed because of plugin stripping HTML from post content. Please note that you need to do a small change if you display the slider with a custom theme made by you or others. In display.php inside your theme folder change

`<?php echo FA_truncate_text($post->post_content, $image ? $options['desc_truncate'] : $options['desc_truncate_noimg']);?>` 

into

`<?php echo FA_truncate_text($post->FA_post_content, $image ? $options['desc_truncate'] : $options['desc_truncate_noimg']);?>`

= 2.3.6 =
* Solved shortcode bug that caused the slider not to be displayed into the exact place the shortcode was placed in post content (changes made in file: featured_articles.php)
* Solved stylesheet issue not being loaded for developer link at the bottom of the slider (changes made in file: featured_articles.php)

= 2.3.5 =
* Solved IE8 bug that caused autoslide not to start (error message: Message: 'currentKey' is null or not an object)
* Solved issue with autoslide that wasn't correctly reset when navigation was clicked
* Made links in article description have the same color as the text

= 2.3.4 =
* Created shortcode support to display sliders inside post/page content.

= 2.3.3 =

* Solved Allowed tags option bug that wasn't taken into account by the plugin when displaying posts in slider.

= 2.3.2 =
* Solved z-index issue in themes causing side navigation not to be able to be clicked 

= 2.3.1 =
* Solved JavaScript bug that made only the first post clickable.

= 2.3 =
* Slider script developed with jQuery (this solves the conflict between different JavaScript frameworks)
* Creation/management and placement of multiple sliders into the same page or all over your blog
* Widget support provided (go to Appearance->Widgets and look for FA Lite Slider)
* Easy manual placement with code to be implemented provided for each slider created
* Slightly modified themes to make it work with multiple sliders

= 2.2 =
* User specified HTML tags allowed into featured post description displayed into the slider
* Meta box to ease the way custom images and featured posts are inserted into the slider
* Possibility to display posts or pages in random order into the slider
* Featured articles slider resizable from administration area ( default values get specified into stylesheet )
* Author link ( if you want to support the plugin ) that can be disabled from administration
* Slider settings access is restricted to administrators only with the possibility to give access to any other group of users available in wordpress
* Menu no longer available under Settings->Featured articles but directly in Wordpress admin sidebar ( look for FA Lite )
* Themes modified to support featured posts slider resizing ( both CSS files and display files have changed a little ). If you update the plugin and you made custom themes, back-up first your themes folder.
* Custom post/pages images improved usage and interface
* Easy setting for featured posts and pages to be displayed into the slider
* Image detection improved even more. Currently there are 2 ways to set an image for a certain post: by setting the image as a custom field and second by detecting the image from post content. For images detected in post content, the plugin tries to identify the exact attachment from the database and if found, it automatically sets the image into the custom field. The only thing it needs is for the image to have the width and height attributes set in HTML.
* New theme available (Smoke). See screenshots for details and [WP featured articles Lite homepage](http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/#additional-themes "WP Featured Articles plugin - theme Smoke settings") for instructions on how to set up this theme.

= 2.1 =
* Date format in featured post short description displays according to blog date format option setting
* Editable text for read more link
* New option to set featured post title as link
* Image detection no more made inside theme but done by function ( less code in slider theme )
* New option to display the slider manually by adding a function to theme files ( function is FA_display_slider - see installation for instructions ) 
* Slider mouse wheel navigation can be enabled/disabled from wp admin
* Links in featured posts text allowed 
* For automatic placement, option to choose loop to display on top of 

= 2.0 =
Initial release for the new redesigned Wordpress Featured Articles

== Troubleshooting ==

ONLY FOR VERSIONS PRIOR TO 2.3

The slider script is developed using MooTools 1.2. Since the framework isn't bundled in Wordpress (as jQuery is), the plugin adds the MooTools framework along with the other scripts it needs to run into the blog header. If other plugins running on MooTools are installed the page will issue Javascript errors. To solve this problem, in slider administration page there's an option to drop the MooTools script so that conflicts no longer occur.

Another known problem is if any of the plugin installed use Prototype framework. MooTools and Prototype are conflicting and the only solution would be to remove one of the plugins (either the MooTools based or the Prototype based plugins).

Usually, after you install Featured Articles Lite into your blog and you go see it in front-end and the slider doesn't work it's a clear sign that there's a framework conflict. First thing to to is to go to wp-admin and open the FA Lite settings panel. Look for option Unload MooTools framework and uncheck it. Go back to front-end and see if the slider works. If it does, this means that another plugin uses MooTools and there was a conflict because MooTools was included twice in header.
If the slider still doesn't work, look into page source and do a search for "prototype.js". If you can see it in your page source it's time to make a decision: use FA Lite and deactivate the plugin using Prototype or drop FA Lite and continue using the other plugin.

If you need help troubleshooting leave a comment on [WP featured articles Lite homepage](http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/#additional-themes "WP Featured Articles plugin homepage").
