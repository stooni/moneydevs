Note: Read this only if you are upgrading Featured Articles Lite and you use custom themes with this plugin.

Version 2.3 comes with new stuff, most important being the ability to create multiple sliders. In order to do that, themes needed to be slightly modified to acomodate the changes. Follow the steps below to upgrade your custom slider theme.

1. In your custom theme, open display.php and make the following changes:
	1.1  Change overall container CSS class from FA_overall_container to FA_overall_container_your_theme_folder_name (replace your_theme_folder_name with the actual folder name you gave to your theme). For example if you placed your custom theme into a folder called my_theme, the new class name will be FA_overall_container_my_theme. After you do this, add a second class on the same element called FA_slider. This will be the selector the script uses so it knows that this is a slider. Now, add an id to this element. Simply paste this: 

[code]
id="<?php echo $FA_slider_id;?>"
[/code]

The whole modified thing should look something like this:

[code]
<div class="FA_overall_container_my_theme FA_slider" style="<?php echo $styles['x'];?>" id="<?php echo $FA_slider_id;?>">
[/code]

Where again, my_theme is the folder name of your theme.

Into this same file, locate function FA_article_image(). This function detects the post image but now, since there are multiple sliders, it needs a second argument. Change the function arguments by inserting a new one:

[code]
FA_article_image($post, $slider_id) )
[/code]

In previous versions, this function only had one argument, the $post. Now it also needs the slider id so it can ask Wordpress for the image size you set in administration area for the slider you're displaying.

2. With multiple sliders you also have the possibility to display multiple sliders on the same page. Displaying multiple sliders causes CSS conflicts between different sliders themes displayed into the page. To solve this, for your custom theme, open stylesheet.css and change all .FA_overall_container to .FA_overall_container_my_theme (where my_theme is the folder name of your theme and it's the same class as the one you set in display.php). Once that is done, make all CSS declarations for all elements within the container descend from .FA_overall_container_my_theme. Basically, every CSS declaration must have .FA_overall_container_my_theme before it. For example, the navigation links are contained into a list (ul.FA_navigation). In previous themes, the CSS for this was ul.FA_navigation{css rules here}. To make this work with the new version, you need to change it to:
 
[code]
.FA_overall_container_my_theme ul.FA_navigation{css rules here}
[/code]

This should be all you need to do to upgrade your theme and make it work with version 2.3. If you need help upgrading or don't understand something, you're welcome to ask questions on http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/
Remember to also leave a link to the place where you implemented your theme.