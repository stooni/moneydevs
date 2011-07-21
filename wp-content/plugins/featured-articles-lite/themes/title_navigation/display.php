<?php if ($options['section_display']==1): ?><h3 class="FA_title_section"><?php echo $options['section_title']?></h3><?php endif;?>
<div class="FA_overall_container_title_nav FA_slider" style="<?php echo implode($styles, ';');?>" id="<?php echo $FA_slider_id;?>">	
	<div class="FA_featured_articles" style="<?php echo $styles['y'];?>">
	<?php foreach ($postslist as $post):?>
		<div class="FA_article">	
			<div class="FA_wrap">
				<?php if( $image = FA_article_image($post, $slider_id) ):?>			
                    <div class="image_container"><img src="<?php echo $image;?>" alt="<?php the_title('','');?>" class="FA_image" /></div>
                <?php endif;?>
                <h2><?php if( $options['title_click'] ):?><a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php endif;?><?php the_title();?><?php if( $options['title_click'] ):?></a><?php endif;?></h2>
                <span class="FA_date"><?php the_time(get_option('date_format')); ?></span>
                <p><?php echo FA_truncate_text($post->FA_post_content, $image ? $options['desc_truncate'] : $options['desc_truncate_noimg']);?></p>
                <a class="FA_read_more" href="<?php the_permalink();?>" title="<?php the_title();?>"><?php echo $options['read_more'];?></a>
			</div>                			
		</div>	
	<?php endforeach;?>			
	</div>
	<ul class="FA_navigation" style="<?php echo $styles['y'];?>">
	<?php foreach ($postslist as $post):?>
		<li><a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php the_title();?></a></li>
	<?php endforeach;?>
	</ul>
</div>