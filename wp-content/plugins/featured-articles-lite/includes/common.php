<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */

/**
 * Returns the complete path of a given file from within the plugin
 *
 * @param string $file
 * @return string
 */
function FA_path( $file ){
	if( !defined('FA_PLUGIN_URL') ){
		define('FA_PLUGIN_URL', WP_PLUGIN_URL.'/featured-articles-lite/');
	}
	return FA_PLUGIN_URL.$file;	
}
/**
 * Returns complete path of a file within the plugin
 *
 * @param string $file
 * @return string
 */
function FA_dir( $file ){
	if( !defined('FA_PLUGIN_DIR') ){
		define('FA_PLUGIN_DIR', WP_PLUGIN_DIR.'/featured-articles-lite/');
	}
	return FA_PLUGIN_DIR.$file;	
}
/**
 * Truncates a text on a given number of characters. Based on the Smarty plugin
 *
 * @param string $string
 * @param int $length
 * @param string $etc
 * @param bool $break_words
 * @param bool $middle
 * @return string
 */
function FA_truncate_text($string, $length = 80, $etc = '...', $break_words = false, $middle = false){
    if ($length == 0)
        return '';
	
    if (strlen($string) > $length) {
        $length -= strlen($etc);
        if (!$break_words && !$middle) {
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
        }
        if(!$middle) {
            return substr($string, 0, $length).$etc;
        } else {
            return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
        }
    } else {
        return $string;
    }
}
/**
 * Image detection inside post
 *
 * @param object $post
 * @return string - image path
 */
function FA_article_image ($post, $slider_id){
	// if thumbnails are stopped from admin, return false
	$options = FA_slider_options($slider_id, '_fa_lite_aspect');
	if( !$options['thumbnail_display'] ) 
		return false;
	
	// check for custom field image	
	$meta_image_id = get_post_meta($post->ID, '_fa_image', true);
	$meta_image = wp_get_attachment_image_src( $meta_image_id, array($options['th_width'], $options['th_height']) );
	
	if( $meta_image )
		return $meta_image[0];
	// check for images in text
	preg_match_all("#\<img(.*)src\=(\"|\')(.*)(\"|\')(/?[^\>]+)\>#Ui", $post->post_content, $matches);
	
	if( !isset($matches[0][0]) ){ 
		return false;
	}
	
	// get image attributes in order to determine the attachment guid
	preg_match_all("#([a-z]+)=\"(.*)\"#Ui", $matches[0][0], $attrs);
	$inversed = array_flip($attrs[1]);
	
	// if image doesn't have width/height attributes set on it, there's no point in going further
	if( !array_key_exists('width', $inversed) || !array_key_exists('height', $inversed) ){
		return $matches[3][0];
	}
	
	// image attributes hold the image URL. Replace those to get the real image guid
	$img_size_url = '-'.$attrs[2][$inversed['width']].'x'.$attrs[2][$inversed['height']];
	$real_image_guid = str_replace( $img_size_url, '', $matches[3][0] );
	
	global $wpdb;
	$the_image = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE guid = '$real_image_guid' AND post_type='attachment'" ) );
	// if unsuccessful, return the image url from content
	if( !$the_image ){
		return $matches[3][0];
	}
	// get the image according to size settings from FA Lite settings
	// this is very useful if for example in post there's the ful image but in slider there's a 150x150 pixels image
	$meta_image = wp_get_attachment_image_src( $the_image->ID, array($options['th_width'], $options['th_height']) );
	if( $meta_image ){
		// if meta image was found, set the id as custom field for the post so that all the query work won't be needed again
		update_post_meta($post->ID, '_fa_image', $the_image->ID);
		return $meta_image[0];
	}else{
		return $matches[3][0];
	}		
}
/**
 * Returns an array of available themes
 */
function FA_themes(){
	$themes = array();
	if ($handle = opendir( FA_dir('themes') )) {
	    while (false !== ($file = readdir($handle))) {
	        if( $file == '.' || $file == '..' || substr($file, 0, 1) == '.' ) continue;
	        $themes[] = $file;
	    }	
	    closedir($handle);
	}
	return $themes;
}
/**
 * Returns the content list according to the settings
 */
function FA_get_content( $slider_id ){
	$options = FA_slider_options( $slider_id, '_fa_lite_content' );
	
	$args = array();
	$args['numberposts'] = $options['num_articles'];
	$args['order'] = 'DESC';
	
	if( $options['displayed_content'] == 2 ){		
		if( isset($options['display_pages'][0]) )
			$args['include'] = implode(',', $options['display_pages']);	
	}else{
		if( isset($options['display_from_category'][0]) )
			$args['category'] = implode(',',$options['display_from_category']);
	}
	
	/* get posts depending on what order was chosen ( by date, by comment count or by meta key ) */
	switch ( $options['display_order'] ){
		case 1: default:
			$args['orderby'] = 'post_date';
		break;	
		case 2:
			$args['meta_query'] = array(array(
				'key'=>'_fa_featured',
				'value'=>'"'.$slider_id.'"',
				'compare'=>'LIKE'
			));
			$args['orderby'] = 'post_date';
			//$args['meta_key'] = '_fa_featured';
			//$args['meta_value'] = 1;
		break;
		case 3:
			$args['orderby'] = 'comment_count post_date';
		break;
		case 4:
			$args['orderby'] = 'rand';
		break;
	}	
	
	if( $options['displayed_content'] == 1 ){
		$postslist = get_posts($args);
	}else{
		$args['sort_order'] = $options['display_order'] == 4 ? 'RAND()' : 'DESC';
		if( $options['display_order'] == 3 ){
			$args['sort_column'] = 'comment_count DESC, post_date';
		}else if( $options['display_order'] == 4 ){
			$args['sort_column'] = '';
		}else{
			$args['sort_column'] = $args['orderby'];	
		}		
		$postslist = get_pages($args);
	}	
	// remove captions and unwanted HTML tags
	$aspect_opt = FA_slider_options( $slider_id, '_fa_lite_aspect' );
	foreach($postslist as $k=>$v){
		$content = $v->post_content;
		// remove shortcodes from content
	    $string = strip_shortcodes( $content );    
	    // remove all HTML tags except links
	    $string = strip_tags($string,$aspect_opt['allowed_tags']);
	    //store the slider stripped text into a different variable
		$postslist[$k]->FA_post_content = $string;
	}
	
	return $postslist;
}
/**
 * Creates a JSON string from an array
 * @param array $array
 */
function FA_lite_json($array){
	if( function_exists('json_encode') ){
		return json_encode($array);
	}else{
		if( file_exists(ABSPATH.'/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php') ){
			require_once(ABSPATH.'/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
			$json_obj = new Moxiecode_JSON();
			return $json_obj->encode($array);
		}	
	}
}
/**
 * Updates options for pages and categories display
 * @param string $option
 * @param int $slider_id
 * @param array/bool $new_values
 */
function FA_update_display($option = false, $slider_id, $new_values){
	$options = array('fa_lite_categories', 'fa_lite_pages');
	if(!$option) return;
	if( !in_array($option, $options) ) return;
	
	// save categories where the slider is displayed
	$wp_opt = get_option($option, array());
	if( $new_values ){
		foreach ( $wp_opt as $c=>$s){
			if( !in_array($c, $new_values) ){
				unset($wp_opt[$c][$slider_id]);
			}
			if( empty($wp_opt[$c]) ){
				unset($wp_opt[$c]);
			}
		}		
		foreach ($new_values as $new_cat){
			$wp_opt[$new_cat][$slider_id] = $slider_id;
		}
	}else{
		foreach( $wp_opt as $cat=>$sliders ){
			if(in_array($slider_id, $sliders)){
				unset($wp_opt[$cat][$slider_id]);				
				if( empty($wp_opt[$cat]) ){
					unset($wp_opt[$cat]);
				}
			}			
		}
	}
	update_option($option, $wp_opt);
	
}
/**
 * Checks the current location to see if any slider needs to be displayed
 */
function FA_display(){
	$sliders = array();
	
	if( is_home() ){
		$option = get_option('fa_lite_home', false);
		if( $option )
			$sliders = $option;		
	}else if( is_category() ){
		$option = get_option('fa_lite_categories', false);
		$cat_ID = get_query_var('cat');
		if( $option && array_key_exists($cat_ID, $option) ){
			$sliders = $option[$cat_ID];
		}				
	}else if( is_page() ){
		$option = get_option('fa_lite_pages', false);
		if( $option ){
			global $post;
			if( array_key_exists($post->ID, $option) ){
				$sliders = $option[$post->ID];
			}
		}		
	}
		
	return $sliders;
}

/**
 * Returns the slider options from database or the default values
 * @param int $id - slider id
 * @param string $meta_key - the meta key id from database
 */
function FA_slider_options( $id = false, $meta_key = false ){
	// the default values
	$fields = array(
		'_fa_lite_content'=>array(
			'num_articles'=>5,
			'display_order'=>1,
			'display_pages'=>array(),	
			'display_from_category'=>array(),
			'displayed_content'=>1
		),
		'_fa_lite_aspect'=>array(
			'section_display'=>1,
			'section_title'=>'Featured articles',
			'slider_width'=>'100%',
			'slider_height'=>300,
			'thumbnail_display'=>true,
			'th_width'=>250,
			'th_height'=>250,	
			'title_click'=>false,
			'desc_truncate'=>500,
			'desc_truncate_noimg'=>800,
			'read_more'=>'Read more',
			'allowed_tags'=>'<a>',	
			'bottom_nav'=>true,
			'sideways_nav'=>true
		),
		'_fa_lite_display'=>array(
			'loop_display'=>0,
			'drop_moo'=>false,
			'show_author'=>true
		),
		'_fa_lite_js'=>array(
			'slideDuration'=>5,
			'effectDuration'=>.6,
			'fadeDist'=>0,
			'fadePosition'=>'left',
			'stopSlideOnClick'=>false,
			'autoSlide'=>false,
			'mouseWheelNav'=>true
		),
		'_fa_lite_theme'=>array(
			'active_theme'=>'dark'
		),
		'_fa_lite_home_display'=>true,
		'_fa_lite_categ_display'=>array(),
		'_fa_lite_page_display'=>array()		
	);
	// if no post ID is set, return default values
	if( !$id ){
		if( !$meta_key )
			return $fields;
		else 	
			return $fields[$meta_key];	
	}
	// if a certain meta key is searched, return only the values associated with it
	if( $meta_key ){
		$defaults = $fields[$meta_key];
		$post_meta = get_post_meta($id, $meta_key, true);
		if( !empty($post_meta) || ( is_bool($defaults) || empty($defaults) ) ){
			$defaults = $post_meta;
		}
		return $defaults;
	}
	// return all values
	foreach( $fields as $key=>$values ){
		$post_meta = get_post_meta($id, $key, true);
		if( !empty($post_meta) || ( is_bool($values) || empty($values) ) ){
			$fields[$key] = $post_meta;
		}
	}
	return $fields;	
}

?>