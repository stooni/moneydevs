<?php
/*
	Plugin Name: WordSocial
	Plugin URI: http://wso.li/
	Description: Allows you to publish your posts and pages on Social Networks.
	Version: 0.4.4
	Author: Tommy Leunen
	Author URI: http://www.tommyleunen.com
	License: GPLv2
*/

/*  Copyright 2011  Tommy Leunen (t@tommyleunen.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('wso_config.php');
require_once('wso_admin.php');

add_action('plugins_loaded', 'wso_plugins_loaded');
function wso_plugins_loaded()
{
	//load_plugin_textdomain('wordsocial', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
	
	$wsoOpts = get_option(WSO_OPTIONS);
	if ($wsoOpts['version'] < '0.4')
	{	
		// force reinit because the structure changed
		$wsoOpts = array(
			'version' => WSO_VERSION,
			'fbopts' => '0|0|',
			'twopts' => '0|0|',
			'liopts' => '0|0|',
			'pict' => WSO_PLUGIN_URL . "medias/wso.jpg",
			'pictret' => "",
			'time' => '2'
		);
		
		update_option(WSO_OPTIONS, $wsoOpts);

		wso_log_msg(sprintf(__('Thanks for using WordSocial. The structure of the plugin changed with the version 0.4, so you need to reconfigure it in <a href="%s">your settings</a>.','wordsocial'), get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial"));
	}
	
	if ($wsoOpts['version'] < WSO_VERSION)
	{
		chmod(WSO_ABS_PLUGIN_URL, 0755);
		chmod(WSO_ABS_PLUGIN_URL . 'lock/', 0777);
		wso_unlink_lockfile();
		
		wso_create_db();
		
		$wsoOpts['version'] = WSO_VERSION;
		update_option(WSO_OPTIONS, $wsoOpts);
	}
}


register_activation_hook(__FILE__, 'wso_activation');
function wso_activation()
{
	$opts = array(
		'version' => WSO_VERSION,
		'fbopts' => '0|0|',
		'twopts' => '0|0|',
		'liopts' => '0|0|',
		'pict' => WSO_PLUGIN_URL . "medias/wso.jpg",
		'pictret' => "",
		'time' => '2'
	);
	// add the configuration options
	add_option(WSO_OPTIONS, $opts);
	
	wso_create_db();
}

register_deactivation_hook(__FILE__, 'wso_deactivation');
function wso_deactivation()
{
	global $wpdb;
	
	$wpdb->query('DROP TABLE '. $wpdb->prefix . WSO_DB_LOG);
	
	delete_option(WSO_OPTIONS);
}

add_action( 'wpmu_new_blog', 'wso_wpmu_new_blog' );
function wso_wpmu_new_blog( $blog_id )
{
	switch_to_blog( $blog_id );
	
	foreach ( array_keys( get_site_option( 'active_sitewide_plugins' ) ) as $plugin )
	{
		do_action( 'activate_' . $plugin, false );
		do_action( 'activate_plugin', $plugin, false );
	}
	
	restore_current_blog();
}

add_action( 'wpmu_delete_blog', 'wso_wpmu_delete_blog' );
function wso_wpmu_delete_blog( $blog_id )
{
	switch_to_blog( $blog_id );
	
	foreach ( array_keys( get_site_option( 'active_sitewide_plugins' ) ) as $plugin )
	{
		do_action( 'deactivate_' . $plugin, false );
		do_action( 'deactivate_plugin', $plugin, false );
	}
	
	restore_current_blog();
}

function wso_create_db()
{
	global $wpdb;
	
	$tableExist = false;
		
	$tables = $wpdb->get_results("show tables");
	foreach($tables as $table)
	{
		foreach($table as $value)
		{
			if($value == $wpdb->prefix . WSO_DB_LOG)
			{
				$tableExist = true;
				break;
			}
		}
	}
	
	if(!$tableExist)
	{
		$sql = "CREATE TABLE " . $wpdb->prefix . WSO_DB_LOG . " (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`time` DATETIME NOT NULL ,
			`message` VARCHAR( 255 ) NOT NULL
			) ENGINE = MYISAM ;";
		$wpdb->query($sql);
	}
}

add_action('save_post', 'wso_save_post', 1);
function wso_save_post($postid)
{
	// ONLY FOR Press This !!
	if ( isset($_POST['press-this']) && wp_verify_nonce($_POST['press-this'], 'press-this') )
	{
		wso_publish_post($postid);
	}
}

/*
// This cause double posting on Facebook (Twitter has an antispam system to avoid it)
add_action('publish_future_post', 'wso_publish_future_post');
function wso_publish_future_post($postid)
{
	$_wso_opts = get_post_meta($postid, '_wso_opts', true);
	$_wso_opts = unserialize($_wso_opts);
	
	if(!empty($_wso_opts))
	{
		//wso_log_msg('wso_publish_future_post');
	
		wso_publish(array(
			'postid' => $postid, 
			'fbenable' => $_wso_opts['publishToFb'],
			'twenable' => $_wso_opts['publishToTw'],
			'lienable' => $_wso_opts['publishToLi']
		));
	}
}
*/

$wso_social_services = array('fb', 'tw', 'li');

add_action('publish_post', 'wso_publish_post', 99);
add_action('publish_page', 'wso_publish_post', 99);
add_action('wso_cron_publish', 'wso_cron_publish', 10, 3);
function wso_publish_post($postid)
{
	global $wso_social_services;
	
	$wsoOpts = get_option(WSO_OPTIONS);
	
	// Must Publish ?
	$mustPublish = array();
	foreach($wso_social_services as $serv)
	{
		$wantPublish = isset($_POST['post_wso_'.$serv]) ? (int)$_POST['post_wso_'.$serv] : 1;
		$pub = isset($wsoOpts[$serv.'at']) && $wantPublish;
		$mustPublish[$serv] = (int)$pub;
	}
	
	$countMustPublish = array_count_values($mustPublish);	
	if(isset($countMustPublish[1]) && $countMustPublish[1] > 0)
	{
		$_wso_opts = get_post_meta($postid, '_wso_opts', true);
		$_wso_opts = unserialize($_wso_opts);
		if(empty($_wso_opts))
		{
			$_wso_opts = array();
			$_wso_opts['lastPublished'] = 0;
			add_post_meta($postid, '_wso_opts', serialize($_wso_opts), true);
		}
		
		foreach($wso_social_services as $serv)
			$_wso_opts['publishTo'.ucfirst($serv)] = $mustPublish[$serv];
					
		update_post_meta($postid, '_wso_opts', serialize($_wso_opts));
		
		$publishargs['postid'] = $postid;
		$publishargs['comment'] = isset($_POST['wso_comment']) ? $_POST['wso_comment'] : '';
		$publishargs['servEnable'] = array();
		foreach($wso_social_services as $serv)
			$publishargs['servEnable'][$serv.'enable'] = $_wso_opts['publishTo'.ucfirst($serv)];
		
		if((int)$wsoOpts['time'] != 0)
		{
			$t = time() + (60* (int)$wsoOpts['time']);
			
			//wso_log_msg('wso_publish_post cron post');
			
			wp_clear_scheduled_hook('wso_cron_publish', $publishargs);
            wp_schedule_single_event($t, 'wso_cron_publish', $publishargs);
		}
		else
		{
			//wso_log_msg('wso_publish_post publish post');
			wso_publish($publishargs);
		}
	}
}

function wso_cron_publish($postid, $comment, $servEnable)
{
	//wso_log_msg('wso_cron_publish');
	wso_publish(array(
		'postid' => $postid, 
		'comment' => $comment,
		'servEnable' => $servEnable
	));
}

function wso_publish($arr)
{
	global $wpdb, $wso_social_services;
	extract($arr);
		
	// manual lock
	if(file_exists(WSO_ABS_PLUGIN_URL . 'lock/' . "_wso-lockfile-".$wpdb->prefix.$postid.".txt"))
		return;
		
	$f = @fopen(WSO_ABS_PLUGIN_URL . 'lock/' . "_wso-lockfile-".$wpdb->prefix.$postid.".txt", 'x');
	if(!$f) 
	{
		wso_log_msg(sprintf(__("WordSocial was unable to publish the <a href='%s'>post with ID %d, because it can't create the file %s in your WSO plugin folder. Please give it the write access",'wordsocial'), 
			get_bloginfo('wpurl')."/wp-admin/post.php?post=".$postid."&action=edit", $postid, "_wso-lockfile-".$wpdb->prefix.$postid.".txt"));
		return;
	}
	
    fwrite($f, "Locked at ".time()."\n"); 
    fclose($f); 
	//-manual lock

	//var_dump($arr);	
	$_wso_opts = get_post_meta($postid, '_wso_opts', true);
	$_wso_opts = unserialize($_wso_opts);
	
	foreach($wso_social_services as $serv)
		unset($_wso_opts['publishTo'.ucfirst($serv)]);
		
	if(time() - $_wso_opts['lastPublished'] > 60)
	{
		$_wso_opts['lastPublished'] = time();
			
		if(isset($servEnable['fbenable']) && $servEnable['fbenable']) wso_publish_facebook($postid, $comment);		
		if(isset($servEnable['twenable']) && $servEnable['twenable']) wso_publish_twitter($postid, $comment);
		if(isset($servEnable['lienable']) && $servEnable['lienable']) wso_publish_linkedIn($postid, $comment);
	}
	update_post_meta($postid, '_wso_opts', serialize($_wso_opts));
	
	unlink(WSO_ABS_PLUGIN_URL . 'lock/' . "_wso-lockfile-".$wpdb->prefix.$postid.".txt"); // unlock   
}

function wso_publish_facebook($postid, $comment)
{
	$post = get_post($postid);
	$wsoOpts = get_option(WSO_OPTIONS);
	
	//wso_log_msg('wso_publish facebook');
	$lnk = get_permalink($postid) . "?utm_source=WordSocial&utm_medium=social&utm_campaign=WordSocial";
							
	$fbopts = explode("|", $wsoOpts['fbopts']);
	$args['access_token'] = $wsoOpts['fbpat'];
	$args['message'] = stripslashes($comment);
	$args['name'] = wso_qTrans($post->post_title);
	$args['caption'] = str_replace('http://', '', get_option('siteurl'));
	$args['link'] = $lnk;
	$args['description'] = substr(wso_strip_tags(wso_qTrans(do_shortcode($post->post_content))), 0, 400)."...";
	$args['actions'] = array('name' => __('Share', 'wordsocial'), 
							 'link' => 'http://www.facebook.com/share.php?u='.urlencode($lnk)
							 );				 
	if((int)$fbopts[WSO_FBOPT_IMAGE] == 1) $args['picture'] = wso_getImage($postid, $wsoOpts);
			
	try
	{
		$fbpost = WSOManager::facebook()->api('/'.$wsoOpts['fbaid'].'/feed/', 'post', $args);
		
		if(empty($_wso_opts['fbpids'])) $_wso_opts['fbpids'] = array();
		$_wso_opts['fbpids'][] = $fbpost['id'];
	} 
	catch(Exception $e)
	{
		wso_log_msg(sprintf(__('Facebook: WordSocial was unable to publish <a href="%s">this post</a> on Facebook (Reason : %s)','wordsocial'),
			get_bloginfo('wpurl')."/wp-admin/post.php?post=".$postid."&action=edit", $e->getMessage()));
	}
}

function wso_publish_twitter($postid, $comment)
{
	$post = get_post($postid);
	$wsoOpts = get_option(WSO_OPTIONS);
	
	$shortenLink = wso_getShortenLnk($postid);
	$maxLn = 140;
	
	$tweet = stripslashes($wsoOpts['twfmt']);
	
	$tweetTitleLn = (strstr($tweet, '%title') === false) ? 0 : strlen("%title");
	$tweetLinkLn = (strstr($tweet, '%link') === false) ? 0 : strlen("%link");
	$tweetExcerptLn = (strstr($tweet, '%excerpt') === false) ? 0 : strlen("%excerpt");
	$tweetCommentLn = (strstr($tweet, '%comment') === false) ? 0 : strlen("%comment");
	
	$tweetLn = strlen($tweet) - $tweetTitleLn - $tweetLinkLn - $tweetExcerptLn - $tweetCommentLn;
	
	//echo $tweet . "<br/>";
	//echo $tweetLn . "<br/>";
	
	// no enough space for the link -> Force the format
	if(strlen($shortenLink) > $maxLn-$tweetLn)
	{
		$tweet = "%title %link";
		$tweetTitleLn = strlen("%title");
		$tweetLinkLn = strlen("%link");
		$tweetExcerptLn = 0;
	}
	$tweet = str_replace("%link", $shortenLink, $tweet);
	$tweetLn = strlen($tweet) - $tweetTitleLn - $tweetExcerptLn - $tweetCommentLn;
	
	//echo $tweet . "<br/>";
	//echo $tweetLn . "<br/>";
	
	$postTitle = wso_qTrans($post->post_title);
	$postTitle = (strlen($postTitle) > $maxLn-$tweetLn) ? substr($postTitle, 0, $maxLn-$tweetLn) : $postTitle;
	$tweet = str_replace("%title", $postTitle, $tweet);
	$tweetLn = strlen($tweet) - $tweetExcerptLn - $tweetCommentLn;
	
	//echo $tweet . "<br/>";
	//echo $tweetLn . "<br/>";
	
	$postExcerpt = wso_qTrans($post->post_excerpt);
	$postExcerpt = (strlen($postExcerpt) > $maxLn-$tweetLn) ? substr($postExcerpt, 0, $maxLn-$tweetLn) : $postExcerpt;
	$tweet = str_replace("%excerpt", $postExcerpt, $tweet);
	$tweetLn = strlen($tweet) - $tweetCommentLn;
	
	//echo $tweet . "<br/>";
	//echo $tweetLn . "<br/>";
	
	$comment = stripslashes($comment);
	$postComment = (strlen($comment) > $maxLn-$tweetLn) ? substr($comment, 0, $maxLn-$tweetLn) : $comment;
	$tweet = str_replace("%comment", $postComment, $tweet);
	
	//echo $tweet . "<br/>";
	//echo $tweetLn . "<br/>";
	
	$response = WSOManager::twitter()->post('statuses/update', array('status' => $tweet));
}

function wso_publish_linkedIn($postid, $comment)
{
	$post = get_post($postid);
	$wsoOpts = get_option(WSO_OPTIONS);
	$liopts = explode("|", $wsoOpts['liopts']);
	
	$lnk = get_permalink($postid) . "?utm_source=WordSocial&utm_medium=social&utm_campaign=WordSocial";
	
	$content = array();
	$content['title'] = wso_qTrans($post->post_title);
	if((int)$liopts[WSO_LIOPT_MESSAGE] == 1) $content['comment'] = stripslashes($comment);
    $content['submitted-url'] = $lnk;
	$content['description'] = substr(wso_strip_tags(wso_qTrans($post->post_content)), 0, 400)."...";
	if((int)$liopts[WSO_LIOPT_IMAGE] == 1)
	{
		$content['submitted-image-url'] = wso_getImage($postid, $wsoOpts);
	}
	
	// Publish
	$response = WSOManager::linkedin()->share('new', $content, TRUE);
	if($response['success'] !== TRUE)
	{
		wso_log_msg(sprintf(__('LinkedIn: WordSocial was unable to publish <a href="%s">this post</a> on LinkedIn','wordsocial'),
			get_bloginfo('wpurl')."/wp-admin/post.php?post=".$postid."&action=edit"));
	}
}

function wso_getImage($postid, &$wsoOpts)
{
	$img = '';
	
	// featured image
	if($wsoOpts['pictret'] == 'feat' && current_theme_supports('post-thumbnails'))
	{
		$img = wp_get_attachment_image_src(get_post_thumbnail_id($postid), 'full');
		if($img !== false) $img = $img[0];
	}
	// custom field image
	else if(!empty($wsoOpts['pictret']))
	{
		$img = get_post_meta($postid, $wsoOpts['pictret'], true);	
	}
	
	//image inside the post
	if(empty($img))
	{
		$attachments = get_children(array(
					'post_parent' => $postid,
					'numberposts' => 1,
					'post_type' => 'attachment',
					'post_mime_type' => 'image',
					'order' => 'ASC',
					'orderby' => 'menu_order date'));
							
		if(is_array($attachments) && !empty($attachments))
		{
			foreach($attachments as $att_id => $attachment)
			{
				$img = wp_get_attachment_image_src($att_id, 'full');
				if($img !== false) { $img = $img[0]; break; }
			}
		}
	}
	
	if(empty($img)) $img = $wsoOpts['pict'];
	
	return $img;
}

function wso_getShortenLnk($postid)
{
	$wsoOpts = get_option(WSO_OPTIONS);
	
	switch($wsoOpts['wsourl'])
	{
		case 'bitly' : return WSO_SURL::ShortenBitLy(get_permalink($postid));
		case 'obitly' : return WSO_SURL::ShortenBitLy(get_permalink($postid), $wsoOpts['wsourlp'][0], $wsoOpts['wsourlp'][1]);
		case 'yourls' : return WSO_SURL::Shorten(get_permalink($postid), $wsoOpts['wsourlp'][0], $wsoOpts['wsourlp'][1]);
	}
	return WSO_SURL::Shorten(get_permalink($postid));
}

function wso_qTrans($output)
{
	if(function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) 
	{
		$output = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($output);
	}
	return $output;
}

function wso_strip_tags($output)
{
	$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
               '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
               '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
	);
	return preg_replace($search, '', $output); 
}