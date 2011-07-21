<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
?>
<div class="misc-pub-section">
<p>To set up a custom image for this post when you display it in Featured Articles Lite Slider, click the link below.</p>
<a href="../wp-content/plugins/featured-articles-lite/add_meta.php?height=300&width=800&post=<?php echo $post->ID;?>&TB_iframe=true" class="thickbox button" title="<?php _e('Add new image for Featured Articles','wp_featured_articles')?>">Set custom image for this post</a>
<div id="FA-curr-img-wrap">
<?php if($current_image):?>
<p>
Current image is:
<img src="<?php echo $current_image;?>" alt="Current Featured Articles image set for this post." style="padding:2px; border:1px #000 solid" id="FA-current-image" />
</p>
<p><label><input type="checkbox" value="1" name="fa_remove_meta_image" /> Remove this image</label></p>
<?php endif;?>
</div>
</div>
<div class="misc-pub-section">
<?php wp_nonce_field('fa_article_featured', 'fa_nonce');?>
<label>Set this post as featured for <em>Featured Articles Lite</em> sliders</label><br />
<select name="fa_lite_featured[]" multiple="multiple" size="3" style="height:auto; width:100%;">
	<option value=""<?php if(!$featured):?> selected="selected"<?php endif;?>><?php _e('None');?></option>
    <?php 
		if ( $loop->have_posts() ) : 
			while ( $loop->have_posts() ) : 
				$loop->the_post();
	?>
    <option value="<?php the_ID();?>"<?php if(in_array(get_the_ID(), $featured)):?> selected="selected"<?php endif;?>><?php the_title();?> [<?php the_ID();?>]</option>
    <?php
			endwhile;
		endif;	
		wp_reset_query();
	?>	
</select>
</div>
<div class="misc-pub-section misc-pub-section_last">
<label>Insert a slider into this post</label><br />
<select name="fa_lite_shortcode" id="fa_lite_shortcode">
	<option value=""><?php _e('Choose slider');?></option>
    <?php 
		if ( $loop->have_posts() ) : 
			while ( $loop->have_posts() ) :
				$loop->the_post();
	?>
    <option value="<?php the_ID();?>"><?php the_title();?> [<?php the_ID();?>]</option>
    <?php
			endwhile;
		endif;	
		wp_reset_query();
	?>	
</select>
<input type="button" id="add_fa_slider" value="insert" class="button-primary" />
</div>
<script language="javascript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#add_fa_slider').click(function(){
			var v = jQuery('#fa_lite_shortcode').val();
			if(''==v){
				alert('Please select a slider first.');
				return;
			}
			send_to_editor(' [FA_Lite id="'+v+'"] ');
		})
	})
</script>
