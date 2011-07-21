<?php if(!$slider_id):?>
<p>Make sure that you first save the slider. Once that is done, the code needed to place the slider into your theme files will be displayed here.</p>
<?php else:?>
<p>If you want to display this Slider into your Wordpress theme template, simply copy and paste the code below in the theme file where you want it displayed.</p>
<p style="padding:10px; border:1px #ccc solid; background:#FFFFCC;">
	&lt;?php<br />
    if( function_exists('FA_display_slider') ){<br />
    &nbsp;&nbsp;&nbsp;&nbsp;FA_display_slider(<?php echo $slider_id;?>);<br />
    }<br />
    ?&gt;
</p>
<?php endif;?>