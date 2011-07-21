<?php
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
/*
Plugin Name: Featured articles Lite
Plugin URI: http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/
Description: Put a fancy JavaScript slider on any blog page, category page or home page to highlight your featured content. Compatible with Wordpress 3.1+
Author: Constantin Boiangiu
Version: 2.3.7
Author URI: http://www.php-help.ro
*/
// Slider administration capability name
define('FA_CAPABILITY', 'edit_FA_slider');
include_once('includes/common.php');
/**
 * Do not change this. It enables the script to display the plugin only for the first loop
 */
$FA_current_loop = array();
/**
 * Displays the featured articles box on index page
 *
 */
function featured_articles_lite(){
	
	$sliders = FA_display();
	if(!$sliders) return;
	
	global $FA_current_loop;
	foreach( $sliders as $slider_id ){
		if( !array_key_exists($slider_id, $FA_current_loop) ){
			$FA_current_loop[$slider_id] = 0;
		}
		$o = FA_slider_options( $slider_id , '_fa_lite_display' );		
		if( $FA_current_loop[$slider_id] != $o['loop_display']  ){
			$FA_current_loop[$slider_id]+=1;
			continue;
		}
		$FA_current_loop[$slider_id]+=1;
		$postslist = FA_get_content($slider_id);
			
		global $post, $id;
		// save the original post
		$original_post = $post;
		// this is used for comments. The comments function uses a global $id variable to count comments. the current id is for the first item in loop
		$original_id = $id;
		$theme_option = FA_slider_options($slider_id, '_fa_lite_theme');
		/* theme display */
		$theme = 'themes/'.$theme_option['active_theme'].'/display.php';
		// get slider size
		$styles = FA_style_size($slider_id);
		if( !is_file( FA_dir($theme) ) )
			$theme = 'themes/dark/display.php';
		$options = FA_slider_options( $slider_id , '_fa_lite_aspect' );
		$FA_slider_id = 'FA_slider_'.$slider_id;		
		include( $theme );
		FA_dev_by($slider_id);	
		// give $post and $id his original value 
		$post = $original_post;
		$id = $original_id;	
	}
}
/*
 * Returns a simple array with width and height styling for easy access in theme.
 * These values are used for resizing the slider according to admin user settings
 */
function FA_style_size( $slider_id ){
	$options = FA_slider_options($slider_id, '_fa_lite_aspect');
	$size = array('x'=>'', 'y'=>'');
	if( !empty( $options['slider_width'] ) && $options['slider_width'] != 0 ){
		$size['x'] = 'width:'.$options['slider_width'].( is_numeric( $options['slider_width'] ) ? 'px':'' );
	}	
	if( !empty( $options['slider_height'] ) && $options['slider_height'] !=0 ){
		$size['y'] = 'height:'.$options['slider_height'].( is_numeric( $options['slider_height'] ) ? 'px':'' );
	}	
	return $size;
}

/**
 * Manual loading function. Place this in theme files like this:
 * 
 * <?php	
 *		if( function_exists('FA_display_slider') ){	
 *			FA_display_slider();
 *		}	
 *	?>
 */
$FA_SLIDERS_PARAMS = array();
function FA_display_slider($slider_id, $echo = true){
	global $FA_SLIDERS_PARAMS;
	
	$slider = get_post($slider_id);
	if(!$slider) return;
	
	$options = FA_slider_options( $slider_id , '_fa_lite_aspect' );
	$postslist = FA_get_content($slider_id);
	
	global $post, $id;
	// save the original post
	$original_post = $post;
	// this is used for comments. The comments function uses a global $id variable to count comments. the current id is for the first item in loop
	$original_id = $id;
	
	$styles = FA_style_size($slider_id);
	/* theme display */
	$theme_option = FA_slider_options($slider_id, '_fa_lite_theme');
	$theme = 'themes/'.$theme_option['active_theme'].'/display.php';
	if( !is_file( FA_dir($theme) ) )
		$theme = 'themes/dark/display.php';
		
	wp_enqueue_style('FA_Lite_'.$theme, FA_path('themes/'.$theme_option['active_theme'].'/stylesheet.css'));
	
	$js_options = FA_slider_options($slider_id, '_fa_lite_js');	
	$FA_SLIDERS_PARAMS['FA_slider_'.$slider_id] = FA_lite_json($js_options);
	if( !$echo ){
		ob_start();
	}
	$FA_slider_id = 'FA_slider_'.$slider_id;
	include( $theme );
		
	if(!$echo){
		$slider_content = ob_get_contents();
		ob_end_clean();
	}
	
	$fa_dev = FA_dev_by($slider_id, $echo);	
	// give $post and $id his original value 
	$post = $original_post;
	$id = $original_id;
	
	if(!$echo){
		return $slider_content.$fa_dev;
	}
}
/**
 * Developer link at the bottom of the slider. Don't delete this, you can disable it from administration panel under Featured articles Settings->Show author link
 * I would appreciate it if you could display the link to help spreading the word about this plugin. 
 * Thank you in advance.
 */
function FA_dev_by($slider_id, $echo  = true){
	$options = FA_slider_options($slider_id, '_fa_lite_display');
	if( !$options['show_author'] ) return;
	
	$size = FA_style_size($slider_id);
	$output = '<div class="wpf-dev" style="'.$size['x'].'">';
	$output.= '<a href="http://www.php-help.ro/mootools-12-javascript-examples/wordpress-featured-content-plugin/" title="Wordpress Featured Articles plugin" target="_blank">developed by php-help.ro</a>';
	$output.= '</div>';
	wp_enqueue_style('FA_dev', FA_path( 'styles/fa_dev.css' ));
	if($echo)
		echo $output;
	else
		return $output;	
}

/**
 * Function to load stylesheets and scripts into the footer.
 * This is needed for manually defined sliders to load scripts and stylesheets 
 * only when needed
 */
function FA_load_footer(){
	global $FA_SLIDERS_PARAMS;
	if(!$FA_SLIDERS_PARAMS) return;
	wp_print_styles();
	wp_register_script('jquery-mousewheel', FA_path('scripts/jquery.mousewheel.min.js'), 'jquery', '3.0.2');
	wp_enqueue_script('FeaturedArticles-jQuery', FA_path('scripts/FeaturedArticles.jquery.js'), array('jquery', 'jquery-mousewheel'), '1.0');
	wp_enqueue_script('FA_footer', FA_path('scripts/fa_footer.js'), array('FeaturedArticles-jQuery'));
	
	wp_localize_script('FA_footer', 'FA_Lite_footer_params', $FA_SLIDERS_PARAMS);	
	wp_print_scripts();		
}
/**
 * Add JavaScript
 *
 */
function FA_add_scripts(){	
	$sliders = FA_display();	
	if(!$sliders) return;
	
	$js_options = array();	
	foreach( $sliders as $slider_id ){
		$options = FA_slider_options($slider_id, '_fa_lite_js');		
		$js_options['FA_slider_'.$slider_id] = FA_lite_json($options);	
	}
	
	wp_register_script('jquery-mousewheel', FA_path('scripts/jquery.mousewheel.min.js'), 'jquery', '3.0.2');
	wp_enqueue_script('FeaturedArticles-jQuery', FA_path('scripts/FeaturedArticles.jquery.js'), array('jquery', 'jquery-mousewheel'), '1.0');
	wp_localize_script('FeaturedArticles-jQuery', 'FA_Lite_params', $js_options);
}
/**
 * Add stylesheets
 *
 */
function FA_add_styles(){	
	$sliders = FA_display();	
	if(!$sliders) return;
	
	foreach( $sliders as $slider_id ){
		$theme = FA_slider_options($slider_id, '_fa_lite_theme');
		$theme_path = 'themes/'.$theme['active_theme'].'/stylesheet.css';
		if( !is_file( FA_dir($theme_path) ) )
			$theme_path = 'themes/dark/stylesheet.css';
		
		wp_register_style('FA_style_'.$theme['active_theme'], FA_path($theme_path));
		wp_enqueue_style('FA_style_'.$theme['active_theme']);
		wp_register_style('FA_dev', FA_path( 'styles/fa_dev.css' ));
		wp_enqueue_style( 'FA_dev');	
	}			
}

/**
 * Adds the admin menu for the settings 
 *
 */
function FA_plugin_menu(){
	add_menu_page( 'FA Lite', 'FA Lite', FA_CAPABILITY, __FILE__, 'FA_sliders_management', FA_path('styles/ico.png') ); 
	add_submenu_page( __FILE__, 'FA Lite Sliders', 'Sliders', FA_CAPABILITY, __FILE__, 'FA_sliders_management');
	add_submenu_page( __FILE__, 'FA Lite Slider', 'Edit/Add', FA_CAPABILITY, FA_dir('edit.php'));	
	// only administrator can manage slider settings user capabilities
	add_submenu_page( __FILE__, 'Permissions', 'Permissions', 'manage_options', FA_dir('permissions.php'));
}
/**
 * Display a list of available FA Lite sliders in administration area
 */
function FA_sliders_management(){
	global $post;
	if( version_compare('3.1', get_bloginfo("version"), '>') ){
		echo '<div class="updated"><p>This plugin is compatible with Wordpress 3.1 or above. Please update your Wordpress installation.</p></div>';
	}
	$current_page = menu_page_url('featured-articles-lite/featured_articles_lite.php', false);
	/* perform deletes */
	if( isset($_GET['delete']) ){		
		$nonce = $_GET['_wpnonce'];
		$id = (int)$_GET['delete'];
		if( wp_verify_nonce($nonce) ){
			wp_delete_post($id, true);
			
			$home_sliders = get_option('fa_lite_home', array());
			if( in_array($id, $home_sliders) ){
				unset($home_sliders[$id]);
				update_option('fa_lite_home', $home_sliders);
			}
			// remove the slider id from pages and categories display
			FA_update_display('fa_lite_categories', $id, false);
			FA_update_display('fa_lite_pages', $id, false);
			// redirect 
			wp_redirect( $current_page );
			exit();
		}else{
			die('Sorry, your action is invalid.');
		}
	}
	/* get the posts */
	$args = array(
        'post_type' => 'fa_slider',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
	$loop = new WP_Query( $args );
	/* display the page */	
	include('displays/show_sliders.php');
}

/**
 * Load admin styles and scripts
 *
 */
function FA_admin_init(){	
	if( !is_admin() ) return ;
	wp_enqueue_script('FA_script_settings', FA_path('scripts/FA_admin.js'), array( 'jquery' ), '1.0' );
	// give permission to administrator to change slider settings
	if( current_user_can('manage_options') ){
		if( !current_user_can( FA_CAPABILITY ) ){
			global $wp_roles;
			$wp_roles->add_cap('administrator', FA_CAPABILITY);
		}
	}	
}
/**
 * Register sliders post type into wordpress
 */
function FA_init(){
	
	$labels = array(
		'name'=>__('Featured Articles Sliders'),
		'singular_name'=>__('Featured Articles Slider'),
		'add_new'=>__('Add New', 'slider'),
		'add_new_item'=>__('Add New Slider'),
		'edit_item'=>__('Edit Slider'),
		'new_item'=>__('New Slider'),
		'view_item'=>__('View Slider')
	);
	$args = array(
		'labels'=>$labels,
		'public'=>false,		
		'query_var'=>false,
		'has_archive'=>false,
		'hierarchical'=>false
	);	
	register_post_type( 'fa_slider', $args);	
}

/**
 * Add box into sidebar for posts and pages
 */
function FA_post_actions() {
	if( !current_user_can(FA_CAPABILITY) ) return;
    add_meta_box( 'FA-actions', 'Featured Articles Lite', 'FA_meta_box', 'post', 'side', 'high' );
    add_meta_box( 'FA-actions', 'Featured Articles Lite', 'FA_meta_box', 'page', 'side', 'high' );
}
/**
 * Display the meta box for posts and pages
 */
function FA_meta_box(){
	global $post;
	// get current image attached by the user for FA Artiles
	$current_image_id = get_post_meta($post->ID, '_fa_image', true);
	if( $current_image_id ){
		$image = wp_get_attachment_image_src( $current_image_id, 'thumbnail' );
		$current_image = $image[0];
	}
		
	// check if post is already featured or not
	$meta = get_post_meta($post->ID, '_fa_featured', true);
	$featured = !empty($meta) ? $meta : array();
	global $post, $id;
	// save the original post
	$original_post = $post;
	// this is used for comments. The comments function uses a global $id variable to count comments. the current id is for the first item in loop
	$original_id = $id;
	/* get the posts */
	$args = array(
        'post_type' => 'fa_slider',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
	$loop = new WP_Query( $args );
	include('displays/meta_box.php');
	$post = $original_post;
	$id = $original_id;
}
/**
 * Saves the data for featured posts
 */
function FA_save_meta(){
	if( !current_user_can(FA_CAPABILITY) ) return;
	if( isset($_POST['fa_nonce']) && wp_verify_nonce($_POST['fa_nonce'],'fa_article_featured') ){
		$id = (int)$_POST['post_ID'];		
		// feature post
		
		if( isset( $_POST['fa_lite_featured'] ) ){
			update_post_meta( $_POST['post_ID'], '_fa_featured', $_POST['fa_lite_featured']);
		}else{
			delete_post_meta( $_POST['post_ID'], '_fa_featured' );	
		}
		// delete custom image
		if( isset( $_POST['fa_remove_meta_image'] ) ){
			delete_post_meta( $_POST['post_ID'], '_fa_image' );	
		}	
	}
}
/**
 * Front-end widget display
 */
function fa_lite_widget(){
	$id = get_option('fa-lite-widget-slider', false);
	if( !$id ){
		return;
	}
	FA_display_slider($id);
}
/**
 * Back-end widget options
 */
function fa_lite_widget_control(){
	$active = get_option('fa-lite-widget-slider', false);
	/* get the posts */
	$args = array(
        'post_type' => 'fa_slider',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
	$loop = new WP_Query( $args );
	include( 'displays/widget_options.php' );
	if( isset($_POST['fa-lite-widget-slider']) ){
		update_option('fa-lite-widget-slider', ((int)$_POST['fa-lite-widget-slider']));
	}
}
/**
 * Widget registration
 */
wp_register_sidebar_widget(
    'fa_lite_widget',
    'FA Lite Slider',
    'fa_lite_widget',
    array(
        'description' => 'Place a slider into your widgets area'
    )
);
wp_register_widget_control(
	'fa_lite_widget',
	'FA Lite Slider',
	'fa_lite_widget_control',
	array(                  // options
        'description' => 'Place a slider into your widgets area'
    )
);

/**
 * Shortcode slider display
 */
add_shortcode('FA_Lite', 'FA_lite_shortcode');
function FA_lite_shortcode($atts){
	extract(shortcode_atts(array(
	      'id' => false
    ), $atts));
    return FA_display_slider($id, false); 
}

/**
 * Hooks
 */
add_action('admin_init', 'FA_admin_init');
add_action('init', 'FA_init');
add_action('admin_menu', 'FA_plugin_menu');
add_action('admin_menu', 'FA_post_actions');
add_action( 'save_post', 'FA_save_meta' );
// script loading in header
add_action('wp_print_scripts', 'FA_add_scripts');
add_action('wp_print_styles','FA_add_styles');
// script loading in footer for manually implemented sliders
add_action('wp_footer', 'FA_load_footer');
add_action('loop_start', 'featured_articles_lite',1);
?>