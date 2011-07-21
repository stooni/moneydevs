<?php
/*
Plugin Name: Custom Post Templates
Plugin URI: http://wordpress.org/extend/plugins/custom-post-template/
Description: Provides a drop-down to select different templates for posts from the post edit screen. The templates are defined similarly to page templates, and will replace single.php for the specified post.
Author: Simon Wheatley
Version: 1.3
Author URI: http://simonwheatley.co.uk/wordpress/
*/

/*  Copyright 2008 Simon Wheatley

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

require_once( dirname (__FILE__) . '/plugin.php' );
require_once( dirname (__FILE__) . '/template-tags.php' );

/**
 *
 * @package default
 * @author Simon Wheatley
 **/
class CustomPostTemplates extends CustomPostTemplates_Plugin
{
	private $tpl_meta_key;
	private $post_ID;
	
	function __construct()
	{
		// Init properties
		$this->tpl_meta_key = 'custom_post_template';
		// Init hooks and all that
		$this->register_plugin ( 'custom-post-templates', __FILE__ );
		$this->add_meta_box( 'select_post_template', __( 'Post Template', 'custom-post-templates' ), 'select_post_template', 'post', 'side', 'default' );
		$this->add_action( 'save_post' );
		$this->add_filter( 'single_template', 'filter_single_template' );
		$this->add_filter( 'body_class' );
	}
	
	/*
	 *  FILTERS & ACTIONS
	 * *******************
	 */
	
	/**
	 * Hooks the WP body_class function to add a class to single posts using a post template.
	 *
	 * @param array $classes An array of strings 
	 * @return array An array of strings
	 * @author Simon Wheatley
	 **/
	public function body_class( $classes ) {
		if ( ! is_post_template() )
			return $classes;
		global $wp_query;
		// We distrust the global $post object, as it can be substituted in any
		// number of different ways.
		$post = $wp_query->get_queried_object();
		$post_template = get_post_meta( $post->ID, 'custom_post_template', true );
		$classes[] = 'post-template';
		$classes[] = 'post-template-' . str_replace( '.php', '-php', $post_template );
		return $classes;
	}

	public function select_post_template( $post )
	{
		$this->post_ID = $post->ID;

		$template_vars = array();
		$template_vars[ 'templates' ] = $this->get_post_templates();
		$template_vars[ 'custom_template' ] = $this->get_custom_post_template();

		// Render the template
		$this->render_admin ( 'select_post_template', $template_vars );
	}

	public function save_post( $post_ID )
	{
		$action_needed = (bool) @ $_POST[ 'custom_post_template_present' ];
		if ( ! $action_needed ) return;

		$this->post_ID = $post_ID;

		$template = (string) @ $_POST[ 'custom_post_template' ];
		$this->set_custom_post_template( $template );
	}

	public function filter_single_template( $template ) 
	{
		global $wp_query;

		$this->post_ID = $wp_query->post->ID;

		// No template? Nothing we can do.
		$template_file = $this->get_custom_post_template();

		if ( ! $template_file )
			return $template;

		// If there's a tpl in a (child theme or theme with no child)
		if ( file_exists( trailingslashit( STYLESHEETPATH ) . $template_file ) )
			return STYLESHEETPATH . DIRECTORY_SEPARATOR . $template_file;
		// If there's a tpl in the parent of the current child theme
		else if ( file_exists( TEMPLATEPATH . DIRECTORY_SEPARATOR . $template_file ) )
			return TEMPLATEPATH . DIRECTORY_SEPARATOR . $template_file;

		return $template;
	}

	/*
	 *  UTILITY METHODS
	 * *****************
	 */
	
	protected function set_custom_post_template( $template )
	{
		delete_post_meta( $this->post_ID, $this->tpl_meta_key );
		if ( ! $template || $template == 'default' ) return;

		add_post_meta( $this->post_ID, $this->tpl_meta_key, $template );
	}
	
	protected function get_custom_post_template()
	{
		$custom_template = get_post_meta( $this->post_ID, $this->tpl_meta_key, true );
		return $custom_template;
	}

	protected function get_post_templates() 
	{
		$themes = get_themes();
		$theme = get_current_theme();
		$templates = $themes[ $theme ][ 'Template Files' ];

		$post_templates = array();

		if ( is_array( $templates ) ) {
			$base = array( trailingslashit(get_template_directory()), trailingslashit(get_stylesheet_directory()) );

			foreach ( $templates as $template ) {
				$basename = str_replace($base, '', $template);

				// don't allow template files in subdirectories
				if ( false !== strpos($basename, '/') )
					continue;

				// Get the file data and collapse it into a single string
				$template_data = implode( '', file( $template ));

				$name = '';
				if ( preg_match( '|Template Name Posts:(.*)$|mi', $template_data, $name ) )
					$name = _cleanup_header_comment( $name[1] );

				if ( !empty( $name ) )
					$post_templates[trim( $name )] = $basename;
			}
		}

		return $post_templates;
	}
}

/**
 * Instantiate the plugin
 *
 * @global
 **/

$CustomPostTemplates = new CustomPostTemplates();

?>