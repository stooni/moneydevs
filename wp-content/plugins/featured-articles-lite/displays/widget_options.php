<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
?>
<select name="fa-lite-widget-slider">
	<option value=""><?php _e('Choose slider to display');?></option>
    <?php 
		if ( $loop->have_posts() ) : 
			while ( $loop->have_posts() ) : 
				$loop->the_post();
	?>
    <option value="<?php the_ID();?>"<?php if($active == get_the_ID()):?> selected="selected"<?php endif;?>><?php the_title();?></option>
    <?php
			endwhile;
		endif;	
		wp_reset_query();
	?>	
</select>