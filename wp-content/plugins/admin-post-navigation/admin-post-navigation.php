<?php
/**
 * @package Admin_Post_Navigation
 * @author Scott Reilly
 * @version 1.6.1
 */
/*
Plugin Name: Admin Post Navigation
Version: 1.6.1
Plugin URI: http://coffee2code.com/wp-plugins/admin-post-navigation/
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Adds links to the next and previous posts when editing a post in the WordPress admin.

Compatible with WordPress 2.8+, 2.9+, 3.0+, 3.1+, 3.2+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/admin-post-navigation/

TODO:
	* Update screenshots for WP3.2
	* L10n
*/

/*
Copyright (c) 2008-2011 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( is_admin() && ! class_exists( 'c2c_AdminPostNavigation' ) ) :

class c2c_AdminPostNavigation {

	private static $prev_text = '';
	private static $next_text = '';
	private static $post_statuses     = array( 'draft', 'future', 'pending', 'private', 'publish' ); // Filterable later
	private static $post_statuses_sql = '';

	/**
	 * Class constructor: initializes class variables and adds actions and filters.
	 */
	public static function init() {
		global $pagenow;
		if ( 'post.php' == $pagenow ) {
			self::$prev_text = __( '&laquo; Previous' );
			self::$next_text = __( 'Next &raquo;' );

			add_action( 'admin_init',                 array( __CLASS__, 'admin_init' ) );
			add_action( 'admin_head',                 array( __CLASS__, 'add_css' ) );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'add_js' ) );
		}
	}

	/**
	 * Initialize variables and meta_box
	 */
	public static function admin_init() {
		add_action( 'do_meta_boxes', array( __CLASS__, 'do_meta_box' ), 10, 3 ); /* For WP 3.0+ only support, change this to hook 'add_meta_boxes' */
	}

	/**
	 * Register meta box
	 *
	 * By default, the navigation is present for all post types.  Filter
	 * 'c2c_admin_post_navigation_post_types' to limit its use.
	 *
	 * @param string $post_type The post type
	 * @param string $type The mode for the meta box (normal, advanced, or side)
	 * @param WP_Post $post The post
	 * @return void
	 */
	public static function do_meta_box( $post_type, $type, $post ) {
		$post_statuses = apply_filters( 'c2c_admin_post_navigation_post_statuses', self::$post_statuses, $post_type, $post );

		$post_types = apply_filters( 'c2c_admin_post_navigation_post_types', get_post_types() );
		if ( !in_array( $post_type, $post_types ) )
			return;

		self::$post_statuses_sql = "'" . implode( "', '", array_map( 'esc_sql', $post_statuses ) ) . "'";
		if ( in_array( $post->post_status, $post_statuses ) )
			add_meta_box( 'adminpostnav', sprintf( '%s Navigation', ucfirst( $post_type ) ), array( __CLASS__, 'add_meta_box' ), $post_type, 'side', 'core' );
	}

	/**
	 * Adds the content for the post navigation meta_box.
	 *
	 * @param object $object
	 * @param array $box
	 * @return void (Text is echoed.)
	 */
	public static function add_meta_box( $object, $box ) {
		global $post_ID;
		$display = '';
		$context = $object->post_type;
		$prev = self::previous_post();
		if ( $prev ) {
			$post_title = esc_attr( strip_tags( get_the_title( $prev->ID ) ) ); /* If only the_title_attribute() accepted post ID as arg */
			$display .= '<a href="' . get_edit_post_link( $prev->ID ) .
				"\" id='admin-post-nav-prev' title='Previous $context: $post_title' class='admin-post-nav-prev'>" . self::$prev_text . '</a>';
		}
		$next = self::next_post();
		if ( $next ) {
			if ( ! empty( $display ) )
				$display .= ' | ';
			$post_title = esc_attr( strip_tags( get_the_title( $next->ID ) ) );  /* If only the_title_attribute() accepted post ID as arg */
			$display .= '<a href="' . get_edit_post_link( $next->ID ) .
				"\" id='admin-post-nav-next' title='Next $context: $post_title' class='admin-post-nav-next'>" . self::$next_text . '</a>';
		}
		$display = '<span id="admin-post-nav">' . $display . '</span>';
		$display = apply_filters( 'admin_post_nav', $display ); /* Deprecated as of v1.5 */
		echo apply_filters( 'c2c_admin_post_navigation_display', $display );
	}

	/**
	 * Outputs CSS within style tags
	 */
	public static function add_css() {
		echo <<<CSS
		<style type="text/css">
		#admin-post-nav {margin-left:20px;}
		h2 #admin-post-nav {font-size:0.6em;}
		</style>

CSS;
	}

	/**
	 * Outputs the JavaScript used by the plugin.
	 *
	 * For those with JS enabled, the navigation links are moved next to the
	 * "Edit Post" header and the plugin's meta_box is hidden.  The fallback
	 * for non-JS people is that the plugin's meta_box is shown and the
	 * navigation links can be found there.
	 */
	public static function add_js() {
		echo <<<JS
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#admin-post-nav').appendTo($('h2'));
			$('#adminpostnav').hide();
		});
		</script>

JS;
	}

	/**
	 * Returns the previous or next post relative to the current post.
	 *
	 * Currently, a previous/next post is determined by the next lower/higher
	 * valid post based on relative sequential post ID and which the user can
	 * edit.  Other post criteria such as post type (draft, pending, etc),
	 * publish date, post author, category, etc, are not taken into
	 * consideration when determining the previous or next post.
	 *
	 * @param string $type (optional) Either '<' or '>', indicating previous or next post, respectively. Default is '<'.
	 * @param int $offset (optional) Offset. Default is 0.
	 * @param int $limit (optional) Limit. Default is 15.
	 * @return string
	 */
	public static function query( $type = '<', $offset = 0, $limit = 15 ) {
		global $post_ID, $wpdb;

		if ( $type != '<' )
			$type = '>';
		$offset = (int) $offset;
		$limit  = (int) $limit;

		$post_type = esc_sql( get_post_type( $post_ID ) );
		$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = '$post_type' AND post_status IN (" . self::$post_statuses_sql . ') ';

		// Determine order
		if ( function_exists( 'is_post_type_hierarchical' ) && is_post_type_hierarchical( $post_type ) )
			$orderby = 'post_title';
		else
			$orderby = 'ID';
		$orderby = esc_sql( apply_filters( 'c2c_admin_post_navigation_orderby', $orderby, $post_type ) );
		$post = get_post( $post_ID );
		$sql .= "AND $orderby $type '{$post->$orderby}' ";

		$sort = $type == '<' ? 'DESC' : 'ASC';
		$sql .= "ORDER BY $orderby $sort LIMIT $offset, $limit";

		// Find the first one the user can actually edit
		$posts = $wpdb->get_results( $sql );
		$result = false;
		if ( $posts ) {
			foreach ( $posts as $post ) {
				if ( current_user_can( 'edit_post', $post->ID ) ) {
					$result = $post;
					break;
				}
			}
			if ( ! $result ) { // The fetch did not yield a post editable by user, so query again.
				$offset += $limit;
				// Double the limit each time (if haven't found a post yet, chances are we may not, so try to get through posts quicker)
				$limit += $limit;
				return self::query( $type, $offset, $limit );
			}
		}
		return $result;
	}

	/**
	 * Returns the next post relative to the current post.
	 *
	 * A convenience function that calls query().
	 *
	 * @return object The next post object.
	 */
	public static function next_post() {
		return self::query( '>' );
	}

	/**
	 * Returns the previous post relative to the current post.
	 *
	 * A convenience function that calls query().
	 *
	 * @return object The previous post object.
	 */
	public static function previous_post() {
		return self::query( '<' );
	}

} // end c2c_AdminPostNavigation

c2c_AdminPostNavigation::init();

endif; // end if !class_exists()

?>