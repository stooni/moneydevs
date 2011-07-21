<?php
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
 
/* set the current page url */
$current_page = menu_page_url('featured-articles-lite/edit.php', false);
$slider_id = isset( $_GET['id'] ) ? (int)$_GET['id'] : false;
$options = FA_slider_options( $slider_id );
$themes = FA_themes();
/* if editing and the slider id isn't valid, display error message */
if( $slider_id ){
	$slider = get_post($slider_id);
	if( !$slider ){
		$error_message = "Sorry, there's no slider with that ID in here."; 
	}else{
		$current_page.="&id=".$slider_id;
	}
}	
/* Save the data */
if( isset($_POST['FA-save_wpnonce']) ){
	if( !wp_verify_nonce( $_POST['FA-save_wpnonce'], 'FA_saveOptions' ) ) {
		die('Sorry, it looks like your request is not valid. Please try again.');
	}
	// if it's a new slider, save it first
	if( !$slider_id ){
		global $user_ID;
		$post_data = array(
			'post_type'=>'fa_slider',
			'post_title'=>$_POST['section_title'],
			'post_author'=>$user_ID,
			'post_status'=>'publish',
			'comment_status'=>'closed',
			'ping_status'=>'closed'
		);
		$slider_id = wp_insert_post($post_data);
		$current_page.='&id='.$slider_id; 
	}else{
		$post_data = array(
			'post_title'=>$_POST['section_title'],
			'ID'=>$slider_id
		);
		wp_update_post($post_data);
	}
	// get the default options	
	$defaults = FA_slider_options();
	// save new options
	foreach( $defaults as $meta_key=>$values ){
		if( !is_array($values) || empty($values) ){
			$key = str_replace('_fa_lite_', '', $meta_key);
			if( isset($_POST[$key]) ){
				if(is_bool( $values )){
					$value = true;
				}else{
					$value = $_POST[$key];
				}
			}else{
				$value = false;
			}
			update_post_meta($slider_id, $meta_key, $value);
			continue;
		}		
		$fields = $values;
		foreach( $values as $key=>$value ){
			if( isset( $_POST[$key] ) ){
				if( is_numeric( $value ) ){
					if( is_numeric( $_POST[$key] ) )
						$fields[$key] = $_POST[$key];
				}else if (is_bool( $value )) {
					$fields[$key] = true;
				}else{
					$fields[$key] = $_POST[$key];
				}
			}else{
				$fields[$key] = false;
			}
		}
		update_post_meta($slider_id, $meta_key, $fields);
	}
	// make some verifications to set the slider as homepage slider or not
	$on_homepage = get_option('fa_lite_home', array());
	if( isset($_POST['home_display']) && !in_array($slider_id, $on_homepage) ){
		$on_homepage[$slider_id] = $slider_id;
	}else if(in_array($slider_id, $on_homepage) && !isset($_POST['home_display'])){
		unset($on_homepage[$slider_id]);
	}
	update_option('fa_lite_home', $on_homepage);
	// update categories where slider will display
	require_once 'includes/common.php';
	$new_categs = isset($_POST['categ_display']) && !empty($_POST['categ_display'][0]) ? $_POST['categ_display'] : false;
	FA_update_display('fa_lite_categories', $slider_id, $new_categs);
	// update pages where slider will display
	$new_pages = isset($_POST['page_display']) && !empty($_POST['page_display'][0]) ? $_POST['page_display'] : false;
	FA_update_display('fa_lite_pages', $slider_id, $new_pages);
	
	// redirect to edit page
	wp_redirect( $current_page );	
	exit();	
}

add_meta_box('submitdiv', 'Save Slider', 'fa_lite_save_panel', 'fa_slider', 'side');
add_meta_box('fa-lite-js', 'JavaScript Settings', 'fa_lite_js_panel', 'fa_slider', 'side');
add_meta_box('fa-lite-implement', 'Manual placement', 'fa_lite_implement_panel', 'fa_slider', 'side');

function fa_lite_save_panel(){
	global $slider_id, $options, $themes;
	$current_page = menu_page_url('featured-articles-lite/featured_articles_lite.php', false);
	include('displays/panel_save.php');
}
function fa_lite_js_panel(){
	global $slider_id, $options, $themes;
	include('displays/panel_js.php');
}
function fa_lite_implement_panel(){
	global $slider_id;
	include('displays/panel_implement.php');
}

?>
<?php 
if( version_compare('3.1', get_bloginfo("version"), '>') ){
	echo '<div class="updated"><p>This plugin is compatible with Wordpress 3.1 or above. Please update your Wordpress installation.</p></div>';
}
?>
<div class="wrap">
    <div id="icon-edit" class="icon32 icon32-posts-post"><br></div>
    <h2><?php if(!$slider_id):?>Add new<?php else:?>Edit<?php endif;?> Slider</h2>
	<?php if( isset($error_message) ):?>
        <?php echo $error_message;?>
    <?php  
        exit();
        endif;
    ?>
    <form method="post" action="<?php echo $current_page.'&amp;noheader=true';?>" id="FeaturedArticles_settings">
    	<?php wp_nonce_field('FA_saveOptions', 'FA-save_wpnonce'); ?>
        <div id="poststuff" class="metabox-holder has-right-sidebar">
        	<div id="side-info-column" class="inner-sidebar">
                <?php do_meta_boxes( 'fa_slider', 'side', null);?>
                <?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );?>
				<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );?>
            </div>
        	<div class="post-body" id="post-body">            
                <h3 class="title"><?php _e('Output Settings');?></h3>
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row"><label for="loop_display" title="<?php _e('If you have multiple articles columns displaying in your page, change this value to the column number you want the slider to display on top of.');?>"><?php _e('Display on loop:');?></label></th>
                            <td><input type="text" id="loop_display" name="loop_display" value="<?php echo $options['_fa_lite_display']['loop_display']; ?>" class="small-text FA_number" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="desc_truncate" title="<?php _e('Posts or pages with image will have maximum this many characters');?>"><?php _e('Truncate descriptions to:');?></label></th>
                            <td><input type="text" name="desc_truncate" id="desc_truncate" value="<?php echo $options['_fa_lite_aspect']['desc_truncate']; ?>" class="small-text FA_number" /> <?php _e('characters (numeric)');?></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="desc_truncate_noimg" title="<?php _e('Posts or pages without image will have maximum this many characters');?>"><?php _e('Truncate descriptions without image to:');?></label></th>
                            <td><input type="text" name="desc_truncate_noimg" id="desc_truncate_noimg" value="<?php echo $options['_fa_lite_aspect']['desc_truncate_noimg']; ?>" class="small-text FA_number" /> <?php _e('characters (numeric)');?></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="allowed_tags" title="<?php _e('The tags you specify here will not be stripped from the description.');?>"><?php _e('Allow these HTML tags:');?></label></th>
                            <td><input type="text" name="allowed_tags" id="allowed_tags" value="<?php echo $options['_fa_lite_aspect']['allowed_tags']; ?>" class="regular-text" /><span class="note">Example to allow links and paragraphs: &lt;a&gt;&lt;p&gt;</span></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="num_articles" title="<?php _e('Maximum number of posts or pages to display into the slider');?>"><?php _e('Number of articles:');?></label></th>
                            <td><input type="text" name="num_articles" id="num_articles" value="<?php echo $options['_fa_lite_content']['num_articles']; ?>" class="small-text FA_number" /> <?php _e('(numeric)');?></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="" title="<?php _e('For featured posts, see meta panel on post edit');?>"><?php _e('Display order:');?></label></th>
                            <td>
                                <fieldset><legend class="screen-reader-text"><span>Display order</span></legend>
                                    <input type="radio" name="display_order" value="1"<?php if($options['_fa_lite_content']['display_order']=='1'): ?> checked="checked"<?php endif;?> id="FA_order_date" /> <label for="FA_order_date"><?php _e('Newest posts');?></label><br />
                                    <input type="radio" name="display_order" value="2"<?php if($options['_fa_lite_content']['display_order']=='2'): ?> checked="checked"<?php endif;?> id="FA_meta_posts" /> <label for="FA_meta_posts" title="<?php _e('To display posts using this feature, set a custom field on any post having as name FA_featured and as value 1 (ie. FA_featured=1)');?>"><?php _e('Featured posts');?></label><br />
                                    <input type="radio" name="display_order" value="3"<?php if($options['_fa_lite_content']['display_order']=='3'): ?> checked="checked"<?php endif;?> id="FA_comments_posts" /> <label for="FA_comments_posts"><?php _e('Most commented');?></label><br />
                                    <input type="radio" name="display_order" value="4"<?php if($options['_fa_lite_content']['display_order']=='4'): ?> checked="checked"<?php endif;?> id="FA_comments_posts" /> <label for="FA_comments_posts"><?php _e('Random order');?></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="" title="<?php _e('Set thumbnails maximum size if displayed');?>"><?php _e('Thumbnail max size:');?></label></th>
                            <td>
                                <label for="FA_th_width"><?php _e('width:');?> </label>
                                <input type="text" name="th_width" id="FA_th_width" value="<?php echo $options['_fa_lite_aspect']['th_width']; ?>" class="small-text FA_number" />px; 
                                <label for="FA_th_height"><?php _e('height:');?> </label>
                                <input type="text" name="th_height" id="FA_th_height" value="<?php echo $options['_fa_lite_aspect']['th_height']; ?>" class="small-text FA_number" />px
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="" title="<?php _e('Set slider size');?>"><?php _e('Slider size:');?></label></th>
                            <td>
                                <label for="slider_width"><?php _e('width:');?> </label>
                                <input type="text" name="slider_width" id="slider_width" value="<?php echo $options['_fa_lite_aspect']['slider_width']; ?>" class="small-text" />px; 
                                <label for="slider_height"><?php _e('height:');?> </label>
                                <input type="text" name="slider_height" id="slider_height" value="<?php echo $options['_fa_lite_aspect']['slider_height']; ?>" class="small-text" />px<br />
                                <span class="description">Use numbers for exact dimensions. For percents, simply add % ( ie: 800 - for 800px or 50% ). To disable size control, enter 0 for size.</span>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="thumbnail_display" class="FA_inline" title="<?php _e('Choose to display thumbnails or not')?>"><?php _e('Display thumbnail:');?></label></th>
                            <td><input type="checkbox" id="thumbnail_display" name="thumbnail_display"<?php if($options['_fa_lite_aspect']['thumbnail_display']) echo ' checked="checked"';?> value="1" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="show_author" class="FA_inline" title="<?php _e('Display author link')?>"><?php _e('Show author link');?></label></th>
                            <td><input type="checkbox" id="show_author" name="show_author" value="1"<?php if( $options['_fa_lite_display']['show_author'] ): ?> checked="checked"<?php endif;?> /></td>
                        </tr>
                    </tbody>
                </table>
                <h3 class="title"><?php _e('Content Settings');?></h3>
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row"><label for="displayed_content" title="<?php _e('Choose between posts or pages. The next field will automatically display according to your choice.');?>"><?php _e('Slider will display:');?></label></th>
                            <td>
                                <input type="radio" name="displayed_content" value="1" id="display_posts"<?php if( $options['_fa_lite_content']['displayed_content']!==2 ):?> checked="checked"<?php endif;?>> <label for="display_posts"><?php _e('Posts');?></label>
                                <input type="radio" name="displayed_content" value="2" id="display_pages"<?php if( $options['_fa_lite_content']['displayed_content']==2 ):?> checked="checked"<?php endif;?>> <label for="display_pages"><?php _e('Pages');?></label></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"></th>
                            <td>
                            	<?php $opt = isset($options['_fa_lite_content']['display_from_category'][0]) ? $options['_fa_lite_content']['display_from_category'][0] : '';?>
                                <div id="d_display_posts"<?php if( $options['_fa_lite_content']['displayed_content']==2 ):?> style="display:none;"<?php endif;?>>
                                    <label for="display_categs" title="<?php _e('Choose specific categories to display posts from. Hold down CTRL for multiple selections');?>"><?php _e('Display posts from categories:');?></label>
                                    <div class="FA_input">
                                        <select name="display_from_category[]" id="display_categs" multiple="multiple" size="7" style="height:auto;">
                                            <option value="" <?php if(empty($opt)):?>selected="selected"<?php endif;?>>All</option>
                                            <?php 
                                                $cats = get_categories('child_of=0'); 
                                                foreach ($cats as $category):
                                                    $selected = in_array($category->term_id,$options['_fa_lite_content']['display_from_category']) ? 'selected="selected"' : '';
                                            ?>
                                            <option value="<?php echo $category->term_id;?>" <?php echo $selected;?>><?php echo $category->name;?></option>
                                            <?php endforeach;?>
                                        </select>	
                                    </div>
                               </div>
                               <div id="d_display_pages"<?php if( $options['_fa_lite_content']['displayed_content']!=2 ):?> style="display:none;"<?php endif;?>> 
                                   <label for="display_pages" title="<?php _e('Choose pages to be displayed in slider. Hold down CTRL key to select multiple pages');?>"><?php _e('Display pages');?>:</label>
                                   <div class="FA_input">
                                        <?php $opt = isset($options['_fa_lite_content']['display_pages'][0]) ? $options['_fa_lite_content']['display_pages'][0] : '';?>
                                        <select name="display_pages[]" id="display_pages" multiple="multiple" size="7" style="height:auto;">			        
                                            <option value="" <?php if(empty($opt)):?>selected="selected"<?php endif;?>>All</option>
                                            <?php 
                                                $pages = get_pages();
                                                foreach ($pages as $page):
                                                    $selected = in_array($page->ID,$options['_fa_lite_content']['display_pages']) ? 'selected="selected"' : '';
                                            ?>
                                            <option value="<?php echo $page->ID;?>" <?php echo $selected;?>><?php echo $page->post_title;?></option>
                                            <?php endforeach;?>			   
                                        </select>
                                   </div> 
                                </div>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="read_more" class="FA_inline" title="<?php _e('Read more link on article will display this text.');?>"><?php _e('Read more link text:');?></label></th>
                            <td><input type="text" id="read_more" name="read_more" value="<?php echo $options['_fa_lite_aspect']['read_more']; ?>" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="title_click" class="FA_inline" title="<?php _e('Article title becomes a link pointing to full article');?>"><?php _e('Article title is clickable');?>:</label></th>
                            <td><input type="checkbox" name="title_click" id="title_click" value="1"<?php if( $options['_fa_lite_aspect']['title_click'] ):?> checked="checked"<?php endif;?> /></td>
                        </tr>
                    </tbody>
                </table>
                <h3 class="title"><?php _e('Display Settings');?></h3>
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row"><label for="" title="<?php _e('Choose where to display the slider')?>"><?php _e('Display slider on');?>:</label></th>
                            <td>
                                <input type="checkbox" name="home_display"<?php echo $options['_fa_lite_home_display']?' checked="checked"':'';?> value="1" id="FA_home"><label for="FA_home"><?php _e('display on home page');?></label><br />
                                <select name="categ_display[]" multiple="multiple" size="7" style="height:auto;">
                                    <optgroup label="Categories">
                                        <option value="">None</option>
                                    <?php 
                                        foreach ($cats as $category):
                                            $selected = in_array($category->term_id,$options['_fa_lite_categ_display']) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo $category->term_id;?>" <?php echo $selected;?>><?php echo $category->name;?></option>
                                    <?php endforeach;?>
                                    </optgroup>
                                </select>
                                <select name="page_display[]" multiple="multiple" size="7" style="height:auto;">
                                    <optgroup label="Pages">
                                        <option value="">None</option>
                                    <?php 
                                        $pages = get_pages();
                                        foreach ($pages as $page):
                                            $selected = in_array($page->ID,$options['_fa_lite_page_display']) ? 'selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo $page->ID;?>" <?php echo $selected;?>><?php echo $page->post_title;?></option>
                                    <?php endforeach;?>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="bottom_nav" class="FA_inline"><?php _e('Display bottom navigation');?>:</label></th>
                            <td><input type="checkbox" name="bottom_nav" id="bottom_nav" value="1"<?php if( $options['_fa_lite_aspect']['bottom_nav'] ):?> checked="checked"<?php endif;?> /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="sideways_nav" class="FA_inline"><?php _e('Display sideways navigation:');?></label></th>
                            <td><input type="checkbox" name="sideways_nav" id="sideways_nav" value="1"<?php if( $options['_fa_lite_aspect']['sideways_nav'] ):?> checked="checked"<?php endif;?> /></td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />	</p>    
        	</div>
        </div>
    </form>
</div>