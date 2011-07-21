<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
?>
<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2>Featured Articles Sliders <a class="button add-new-h2" href="admin.php?page=featured-articles-lite/edit.php"><?php _e('Add New');?></a> </h2>

    <table cellspacing="0" class="wp-list-table widefat posts">
        <thead>
        <tr>
            <th class="manage-column" id="title" scope="col"><?php _e('Title');?></th>
            <th style="" class="manage-column column-author" id="author" scope="col"><?php _e('Author');?></th>
            <th style="" class="manage-column column-date" id="date" scope="col"><?php _e('Date');?></th>	
        </tr>
        </thead>    
        <tfoot>
        <tr>
            <th style="" class="manage-column column-title" scope="col"><?php _e('Title');?></th>
            <th style="" class="manage-column column-author" scope="col"><?php _e('Author');?></th>
            <th style="" class="manage-column column-date" scope="col"><?php _e('Date');?></th>	
        </tr>
        </tfoot>
    
        <tbody id="the-list">
        	<?php if ( $loop->have_posts() ) : 
					$i = 0;
					while ( $loop->have_posts() ) : 
						$loop->the_post();
			?>
            <tr valign="top" class="<?php if($i%2 == 0):?>alternate <?php endif;?>author-self status-publish format-default iedit" id="post-<?php the_ID();?>">
            	<td class="post-title page-title column-title">
                	<strong><a title="<?php echo the_title();?>" href="admin.php?page=featured-articles-lite/edit.php&amp;id=<?php echo get_the_ID();?>" class="row-title"><?php the_title();?></a></strong>
    				<div class="row-actions">
                    	<span class="edit"><a title="<?php _e('Edit this item');?>" href="admin.php?page=featured-articles-lite/edit.php&amp;id=<?php echo get_the_ID();?>"><?php _e('Edit');?></a> | </span>
                        <span class="trash"><a href="<?php echo wp_nonce_url( $current_page.'&amp;noheader=true&amp;delete='.get_the_ID() );?>" title="<?php _e('Delete this item');?>" class="submitdelete"><?php _e('Delete');?></a></span>
                    </div>
    		</td>			
            <td class="author column-author"><?php the_author();?></td>
            <td class="date column-date"><abbr title="<?php the_modified_date();?>"><?php the_modified_date();?></abbr><br><?php echo ucfirst($post->post_status);?></td>		
            </tr>
            <?php
					$i++; 
				endwhile;
				wp_reset_query();
			?>
            <?php else:?>
            <tr>
            	<td colspan="3"><p>Nothing in here yet. Looks like you need to add some sliders.</p></td>
            </tr>
            <?php endif;?>
    	</tbody>
    </table>        
	<br class="clear">
</div>