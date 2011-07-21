<?php
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
/** Load WordPress Administration Bootstrap */
$dir = dirname( __FILE__ );
$root_dir = substr($dir, 0, strpos($dir, 'wp-content'));
// bootstrap wordpress
require($root_dir.'wp-load.php');
require($root_dir.'wp-admin/admin.php');

if( isset($_POST['post']) && isset($_POST['value']) )
{
	$post_id = (int)$_POST['post'];	
	update_post_meta( $post_id, '_fa_image', $_POST['value'] );
	$th = wp_get_attachment_image_src( (int)$_POST['value'], 'thumbnail' );
	echo $th[0];
	exit();	
}

wp_enqueue_script( 'jquery' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php _e('Add new video', 'wp_mytube');?></title>
<?php
wp_admin_css( 'css/global');
wp_admin_css();
wp_admin_css( 'css/colors');
wp_admin_css( 'css/ie');
?>
<?php
do_action('admin_print_styles');
do_action('admin_print_scripts');
do_action('admin_head');
?>
<style type="text/css">
	#images{
		display:block;
		position:relative;
		width:550px;
		margin:0px auto 0px;		
	}
		#images .image{
			display:block;
			position:relative;
			float:left;
			margin:0px 10px 10px 0px;
			width:170px;
			text-align:center;
			border:1px #CCC solid;
			padding-bottom:5px;
		}
			#images .image strong{
				display:block;
				position:relative;
				margin-bottom:5px;
				text-align:left;
				padding:5px;
				width:160px;				
				overflow:hidden;
				font-weight:normal;
				font-size:10px;
				height:30px;
			}
		span.alert{
			display:block;
			position:relative;
			padding:20px 0px 0px;
			text-align:center;
			fint-size:16px;
			color:#FF0000;
		}	
</style>

<script language="javascript" type="text/javascript">
	jQuery(document).ready(function(){
		var images = jQuery('#images .image span');
		images.click(function(event){
			
			event.preventDefault();
			if( !confirm('Are you sure you want Featured Articles to display this thumbnail?') )
				return;

			var val = jQuery(this).attr('id');			
			var data = {
				'post': <?php echo (int)$_GET['post'];?>,
				'value': val
			};
		
			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post('add_meta.php', data, function(response) {
				var win = window.dialogArguments || opener || parent || top;
				win.tb_remove();
				var img = jQuery('#FA-current-image', window.parent.document);
				if( img.length > 0 ){
					jQuery('#FA-current-image', window.parent.document).attr('src', response);
				}else{
					var wrapper = jQuery('#FA-curr-img-wrap', window.parent.document);
					var h = '<p>Current image is: <img src="' + response + '" alt="Current Featured Articles image set for this post." style="padding:2px; border:1px #000 solid" id="FA-current-image" /></p><p><label><input type="checkbox" value="1" name="fa_remove_meta_image" /> Remove this image</label></p>';
					wrapper.html(h);			
				}
			});
		})			
	});
</script>
</head>

<body>
<div class="wrap">
	<h2><?php _e('Featured Articles - Define new image custom field');?></h2>
	<div id="images">		
	<?php
		/* get all images */
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null, // any parent
			); 
		$attachments = get_posts($args);
		if ($attachments) :
			foreach ($attachments as $post) :
				setup_postdata($post);
				$medium_size = wp_get_attachment_image_src( $post->ID );
				$th = wp_get_attachment_image_src( $post->ID);	
	?>
		<div class="image">
			<strong><?php the_title(); ?></strong>
			<div style="width:150px; margin:0px auto 0px;">
				<span class="fa_thumbnail" id="<?php echo $post->ID;?>"><img src="<?php echo $th[0];?>" alt="" /></span>
			</div>	
		</div>
	<?php
			endforeach;
		else:
	?>
		<span class="alert"><?php _e('Sorry, no images in your Media Library. Try uploading some images first.');?></span>
	<?php		
		endif;
	?>	
	</div>
</div>
</body>
</html>