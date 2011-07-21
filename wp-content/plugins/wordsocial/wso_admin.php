<?php
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
$sessid = session_id();
if(empty($sessid)) session_start();

function wso_log_msg($msg)
{
	global $wpdb;
	
	$data = array(
		'time' => date("Y-m-d H:i:s", time()),
		'message' => $msg
	);
	$format = '%s';
	
	$wpdb->insert($wpdb->prefix . WSO_DB_LOG, $data, $format);
}

function wso_unlink_lockfile()
{
	if($handle = opendir(WSO_ABS_PLUGIN_URL . 'lock/'))
		while (false !== ($file = readdir($handle)))
			if($file != '.' && $file != '..')
				unlink(WSO_ABS_PLUGIN_URL . 'lock/'.$file);
}

add_action( 'admin_init', 'wso_admin_init' );
function wso_admin_init()
{
	wp_register_style( 'wso_admin_styles', plugins_url('medias/admin.css', __FILE__) );
	wp_register_script( 'wso_admin_scripts', plugins_url('medias/admin.js', __FILE__));
	
	wp_register_style( 'wso_admin_posts_styles', plugins_url('medias/admin-posts.css', __FILE__) );
	
	$wsoOpts = get_option(WSO_OPTIONS);
	wso_admin_page_connect($wsoOpts);
}


add_action('admin_menu', 'wso_admin_menu');
function wso_admin_menu()
{
	$allowedGroup = 'manage_options'; //admin		
	$page = add_submenu_page('options-general.php', "WordSocial", "WordSocial", $allowedGroup, 'wordsocial', 'wso_admin_page');
	
	add_action( 'admin_print_styles-' . $page, 'wso_admin_print_styles' );
	add_action( 'admin_print_scripts-' . $page, 'wso_admin_print_scripts' );
	
	add_action( 'admin_print_styles-post.php', 'wso_admin_posts_print_styles' );
	add_action( 'admin_print_styles-post-new.php', 'wso_admin_posts_print_styles' );

}

add_action('admin_head', 'wso_admin_head');
function wso_admin_head()
{
}

add_action('admin_footer', 'wso_admin_footer');
function wso_admin_footer()
{
	if(WSO_DEBUG)
	{
		echo "<pre>" . print_r(WSOManager::getFb(), true) . "</pre>";
		echo "<pre>" . print_r(WSOManager::getTw(), true) . "</pre>";
		echo "<pre>" . print_r(WSOManager::getLi(), true) . "</pre>";
	}
}

function wso_admin_print_styles()
{
	wp_enqueue_style( 'wso_admin_styles' );
}

function wso_admin_print_scripts()
{
	wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-tabs');
	
	wp_enqueue_script( 'wso_admin_scripts' );
}

function wso_admin_posts_print_styles()
{
	wp_enqueue_style( 'wso_admin_posts_styles' );
}

function wso_admin_posts_print_scripts()
{
	wp_enqueue_script( 'wso_admin_scripts' );
}


add_action('admin_notices', 'wso_admin_notices');
function wso_admin_notices()
{
	global $wpdb;
	
	if(isset($_GET['wso_clear']) && $_GET['wso_clear'] == 1)
	{
		$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . WSO_DB_LOG);
		wso_unlink_lockfile();
	}
	
	$results = $wpdb->get_results("SELECT time, message FROM " . $wpdb->prefix . WSO_DB_LOG . " ORDER BY id DESC");

	if(!empty($results)) :
		$wsoInfo = __("WordSocial Information", 'wordsocial');
		$clear = __("<a href='?wso_clear=1'>Clear all messages</a>", 'wordsocial');
		
		$content = "";
		foreach($results as $res)
		{
			$content .= '<li>'. $res->time .' - '. $res->message .'</li>';
		}
	
		echo <<<PAGE
<div style="background: #EAEAAE; border: 1px solid #DBDB70; padding: 5px; margin: 25px auto auto auto; width: 85%;">
<h3>$wsoInfo</h3>
<ul>
	$content
</ul>
$clear
</div>
PAGE;
	endif;
}

add_action('add_meta_boxes', 'wso_add_meta_boxes');
function wso_add_meta_boxes()
{
	$pp = array('post', 'page');
	foreach($pp as $p)
	{
		add_meta_box( 
			'wso_wordsocial',
			__( 'WordSocial', 'wordsocial' ),
			'wso_inner_meta_boxes',
			$p,
			'side',
			'core'
		);
	}
}

function wso_inner_meta_boxes()
{
	$wsoOpts = get_option(WSO_OPTIONS);
	
	$services = array();
	
	echo '<div id="post-wso">';
	
	if(isset($wsoOpts['fbat'])) array_push($services, array('fb', 'Facebook'));
	if(isset($wsoOpts['twat'])) array_push($services, array('tw', 'Twitter'));
	if(isset($wsoOpts['liat'])) array_push($services, array('li', 'LinkedIn'));
	
	if(count($services) == 0)
	{
		echo "<p><a href='./options-general.php?page=wordsocial'>Please configure WordSocial</a>.</p>";
	}
	
	$servicesContent = '';
	$showComment = false;
	foreach($services as $serv)
	{
		$opts = explode('|', $wsoOpts[$serv[0].'opts']);
		$publish = (isset($_GET['post_type']) && $_GET['post_type'] == 'page') ? (int)$opts[WSO_OPT_PAGE] : (int)$opts[WSO_OPT_POST];
		$publish &= ( !isset($_GET['action']) || $_GET['action'] != 'edit' );
		
		$msg = sprintf(__('Publish on %s', 'wordsocial'), $serv[1]);
		
		$inputHiddenValue = ($publish) ? 1 : 0;
		$checkedButton = ($publish) ? '_checked' : '';
		
		$servicesContent .= '<div class="wso_publish_button"><a href="#" class="wso_button_'.$serv[0].' wso_button'.$checkedButton.'" id="wso_id_button_'.$serv[0].'" >&nbsp;</a></div>';
		$servicesContent .= '<input type="hidden" value="'.$inputHiddenValue.'" name="post_wso_'.$serv[0].'" id="wso_id_input_'.$serv[0].'" />';
		
		// comment or not ?
		if(!$showComment && $serv[0] == 'tw') $showComment = (strstr($wsoOpts['twfmt'], '%comment') !== FALSE);
		else if(!$showComment && $serv[0] != 'tw') $showComment = ((int)$opts[WSO_OPT_MESSAGE]) == 1;
	}
	
	if($showComment)
	{
		echo '<p>' . __( 'Comment:', 'wordsocial' ) . '<input type="text" name="wso_comment" style="width: 100%;" /></p>';
	}
	
	echo $servicesContent;
	
	echo <<<WSO_JS
<script type="text/javascript">
jQuery('.wso_publish_button a').click(function()
{
	var checked = jQuery(this).hasClass('wso_button_checked');
	var input = jQuery(this).attr('id').replace('button', 'input');
	
	if(checked)
	{
		jQuery(this).addClass('wso_button');
		jQuery(this).removeClass('wso_button_checked');
		jQuery('#'+input).val(0);
	}
	else
	{
		jQuery(this).removeClass('wso_button');
		jQuery(this).addClass('wso_button_checked');
		jQuery('#'+input).val(1);
	}
});
</script>
WSO_JS;
	echo '</div>';
}

function wso_admin_page_connect(&$wsoOpts)
{	
	if(isset($_GET['fbt']))
	{
		$wsoOpts['fbat'] = $_GET['fbt'];
		$wsoOpts['fbpat'] = $wsoOpts['fbat'];
		$wsoOpts['fbaid'] = "me";
		$wsoOpts['fbopts'] = "1|0|0|1";
		update_option(WSO_OPTIONS, $wsoOpts);
		wp_redirect(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
	}
	if($_GET['connect'] == 'tw')
	{
		$tw = WSOManager::twitter();
		
		// Request
		if(!isset($_GET['resp']))
		{
			$twrt = $tw->getRequestToken(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial&connect=tw&resp=1");
			$loginUrl = $tw->getAuthorizeURL($twrt);
			
			$_SESSION['oauth']['twitter']['oauth_token'] = $twrt['oauth_token'];
			$_SESSION['oauth']['twitter']['oauth_token_secret'] = $twrt['oauth_token_secret'];
			
			wp_redirect($loginUrl);
		}
		// Response
		else
		{
			$twrt = $tw->getAccessToken($_GET['oauth_verifier']);
			$wsoOpts['twat'] = $twrt['oauth_token'];
			$wsoOpts['twats'] = $twrt['oauth_token_secret'];
			$wsoOpts['twopts'] = "1|0|";
			$wsoOpts['twfmt'] = "%title %link %comment";
			$wsoOpts['wsourl'] = "wso";
			$wsoOpts['wsourlp'] = array();
			update_option(WSO_OPTIONS, $wsoOpts);			
			session_unset();
			wp_redirect(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
		}
	}
	if($_GET['connect'] == 'li')
	{
		$li = WSOManager::linkedin();
		$li->setCallbackUrl(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial&connect=li&resp=1");
		
		// Request
		if(!isset($_GET['resp']))
		{
			$response = $li->retrieveTokenRequest();
			if($response['success'] === TRUE)
			{
				$_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];
				wp_redirect(LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token']);
			} 
			else
			{
				wso_log_msg(__('LinkedIn : Request token retrieval failed','wordsocial'));
			}
		}
		// Response
		else
		{
			$response = $li->retrieveTokenAccess($_GET['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
			if($response['success'] === TRUE)
			{
				$wsoOpts['liat'] = $response['linkedin']['oauth_token'];
				$wsoOpts['liats'] = $response['linkedin']['oauth_token_secret'];
				$wsoOpts['liopts'] = "1|0|0|1";
				update_option(WSO_OPTIONS, $wsoOpts);
				session_unset();
				wp_redirect(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
			}
			else
			{
				wso_log_msg(__('LinkedIn : Access token retrieval failed','wordsocial'));
			}
		}
	}
		
	if(isset($_GET['disconnect']))
	{
		if($_GET['disconnect'] == "fb")
		{
			unset($wsoOpts['fbat']);
			unset($wsoOpts['fbpat']);
			unset($wsoOpts['fbaid']);
			unset($wsoOpts['fbpict']);
			$wsoOpts['fbopts'] = "0|0|";
			update_option(WSO_OPTIONS, $wsoOpts);
			
			$redir = "http://wso.li/iconnectFb.php?logout&r=". urlencode(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
			wp_redirect($redir);
		}
		if($_GET['disconnect'] == "tw")
		{
			unset($wsoOpts['twat']);
			unset($wsoOpts['twats']);
			unset($wsoOpts['twfmt']);
			unset($wsoOpts['wsourl']);
			unset($wsoOpts['wsourlp']);
			$wsoOpts['twopts'] = "0|0|";
			update_option(WSO_OPTIONS, $wsoOpts);
			wp_redirect(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
		}
		if($_GET['disconnect'] == "li")
		{
			$li = WSOManager::linkedin();
			$response = $li->revoke();
			if($response['success'] === TRUE)
			{
				unset($wsoOpts['liat']);
				unset($wsoOpts['liats']);
				$wsoOpts['liopts'] = "0|0|";
				update_option(WSO_OPTIONS, $wsoOpts);
				wp_redirect(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
			} 
			else
			{
				wso_log_msg(__("LinkedIn : Error revoking user's token",'wordsocial'));
			}
		}
	}
}

function wso_admin_page_save(&$wsoOpts, $fbAccounts)
{
	//var_dump($_POST);
	if(isset($_POST['submit']))
	{
		$wsoOpts["time"] = $_POST['wsotime'];
		
		//pict
		$wsoOpts['pictret'] = $_POST['wsoretpict'];
		$fbPict = (!empty($_POST['wsopicture'])) ? $_POST['wsopicture'] : WSO_PLUGIN_URL . "medias/wso.jpg";
		$wsoOpts['pict'] = $fbPict;
	
		if(isset($wsoOpts['fbat']) && $fbAccounts != 0)
		{
			// access token
			$fbAccessToken = (int) $_POST['wsofb-access_token'];
			$pat = $wsoOpts['fbat'];
			$aid = "me";
			if($fbAccessToken > -1 && $fbAccessToken < count($fbAccounts['data']))
			{
				$pat = $fbAccounts['data'][$fbAccessToken]['access_token'];
				$aid = $fbAccounts['data'][$fbAccessToken]['id'];
			}
			$wsoOpts['fbpat'] = $pat;
			$wsoOpts['fbaid'] = $aid;
			
			// fb opts
			$fbOpts[WSO_FBOPT_POST] = $_POST['wsofbpublishposts'];
			$fbOpts[WSO_FBOPT_PAGE] = $_POST['wsofbpublishpages'];
			$fbOpts[WSO_FBOPT_MESSAGE] = $_POST['wsofbmessage'];
			$fbOpts[WSO_FBOPT_IMAGE] = $_POST['wsofb_showpicture'];
			$wsoOpts['fbopts'] = implode('|', $fbOpts);
		}
		
		if(isset($wsoOpts['twat']))
		{
			$twopts[WSO_FBOPT_POST] = $_POST['wsotwpublishposts'];
			$twopts[WSO_FBOPT_PAGE] = $_POST['wsotwpublishpages'];
			$wsoOpts['twopts'] = implode('|', $twopts);
			$wsoOpts['twfmt'] = $_POST['wsotwfmt'];
			
			$wsoOpts['wsourl'] = $_POST['wsotwwsourl'];
			if($wsoOpts['wsourl'] == 'yourls')
			{
				$wsoOpts['wsourlp'][0] = $_POST['wso_yourls_url'];;
				$wsoOpts['wsourlp'][1] = $_POST['wso_yourls_sign'];;
			}
			else if($wsoOpts['wsourl'] == 'obitly')
			{
				$wsoOpts['wsourlp'][0] = $_POST['wso_obitly_login'];;
				$wsoOpts['wsourlp'][1] = $_POST['wso_obitly_apikey'];;
			}
			else $wsoOpts['wsourlp'] = array();
		}
		
		if(isset($wsoOpts['liat']))
		{			
			// fb opts
			$liOpts[WSO_LIOPT_POST] = $_POST['wsolipublishposts'];
			$liOpts[WSO_LIOPT_PAGE] = $_POST['wsolipublishpages'];
			$liOpts[WSO_LIOPT_MESSAGE] = $_POST['wsolimessage'];
			$liOpts[WSO_LIOPT_IMAGE] = $_POST['wsoli_showpicture'];
			$wsoOpts['liopts'] = implode('|', $liOpts);
		}
		
		update_option(WSO_OPTIONS, $wsoOpts);
	}
}

function wso_admin_page()
{
	$tw = WSOManager::twitter();
	$fb = WSOManager::facebook();
	
	$wsoOpts = get_option(WSO_OPTIONS);
	//wso_admin_page_connect($wsoOpts);

	$fbAccounts = 0;
	if(isset($wsoOpts['fbat']))
	{
		$fbAccounts = $fb->api('/me/accounts', array('access_token' => $wsoOpts['fbat']));
	}
	
	wso_admin_page_save($wsoOpts, $fbAccounts);
	
	if(WSO_DEBUG)
	{
		echo "<pre>" . print_r($wsoOpts, true) . "</pre>";
	}
?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>WordSocial</h2>
		
	<form action="options-general.php?page=wordsocial" method="post" name="form" id="wso_leftCont">
		<div id="wso-page">
			<ul>
				<li><a href="#wso-page-general">General</a></li>
				<li><a href="#wso-page-facebook">Facebook</a></li>
				<li><a href="#wso-page-twitter">Twitter</a></li>
				<li><a href="#wso-page-linkedin">LinkedIn</a></li>
			</ul>
		
			<div id="wso-page-general" class="wso_page"><?php wso_show_page_general(); ?></div>
			<div id="wso-page-facebook" class="wso_page"><?php wso_show_page_facebook($fbAccounts); ?></div>
			<div id="wso-page-twitter" class="wso_page"><?php wso_show_page_twitter(); ?></div>
			<div id="wso-page-linkedin" class="wso_page"><?php wso_show_page_linkedin(); ?></div>
		</div>
		<p class="submit">
			<input id="submit" class="button-primary" type="submit" value="<?php _e('Save changes', 'wordsocial') ?>" name="submit">
		</p>
	</form>
	<div id="wso_sidebar"><?php wso_show_sidebar(); ?></div>
</div>
<?php
}

function wso_show_sidebar()
{
?>
<div class="postbox-container">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 style="cursor: default"><?php _e("Do you like WordSocial ?", "wordsocial"); ?></h3>
			<div class="inside">
				<p><?php printf(__("This plugin is developed by %s. Any contribution would be greatly apprecieted. Thank you very much!", 'wordsocial'), "<a href='http://www.tommyleunen.com'>Tommy Leunen</a>"); ?></p>
				<ul>
					<li style="padding-left: 38px; background: url('<?php echo plugins_url('medias/rate.png', __FILE__);?>') no-repeat scroll 16px 50% transparent; text-decoration: none;">
						<a href="http://wordpress.org/extend/plugins/wordsocial/" target="_blank"><?php _e("Rate the plugin on WordPress.org", 'wordsocial'); ?></a>
					</li>
					<li style="padding-left: 38px; background: url('<?php echo plugins_url('medias/fb.png', __FILE__);?>') no-repeat scroll 16px 50% transparent; text-decoration: none;">
						<a href="http://www.facebook.com/WordSocial?sk=reviews" target="_blank"><?php _e("Rate the plugin on Facebook App", 'wordsocial'); ?></a>
					</li>
					<li style="padding-left: 38px; background: url('<?php echo plugins_url('medias/paypal.png', __FILE__);?>') no-repeat scroll 16px 50% transparent; text-decoration: none;">
						<a href="http://wso.li/donation" target="_blank"><?php _e("Buy me a coffee (Donate with Paypal)", 'wordsocial'); ?></a>
					</li>
				</ul>
				<?php _e("Don't forget to send me an email with your blog url and your email (paypal) if you send me a donation, so I could add you in the list of donators on the wso.li website and into the plugin.", 'wordsocial'); ?>
				<iframe src="http://www.facebook.com/plugins/likebox.php?href=https%3A%2F%2Fwww.facebook.com%2Fapps%2Fapplication.php%3Fid%3D198517920178057&amp;width=250&amp;colorscheme=light&amp;show_faces=false&amp;stream=false&amp;header=false&amp;height=62" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:250px; height:62px;" allowTransparency="false"></iframe>
			</div>
		</div>
	</div>
</div>
<div class="postbox-container">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 style="cursor: default"><?php _e("From the same author...", "wordsocial"); ?></h3>
			<div class="inside">
				<ul>
					<li>
						<a href="http://wordpress.org/extend/plugins/simple-countdown-timer/" target="_blank">Simple Countdown Timer</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<div class="postbox-container">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 style="cursor: default"><?php _e("Any problems ?", "wordsocial"); ?></h3>
			<div class="inside">
				<p><?php printf(__("If you've some difficulties to use the plugin (error, asking, or anything), feel free to post your inquiry in %s, on %s, with %s or by %s."), 
				'<a href="http://wordpress.org/tags/wordsocial?forum_id=10">the WP forum</a>', '<a href="http://www.facebook.com/apps/application.php?id=198517920178057">Facebook</a>', '<a href="http://twitter.com/tommy">@Tommy</a>', '<a href="mailto:tom@tommyleunen.com?subject=WordSocial">email</a>'); ?></p>
				<p><?php _e('Thanks again for using WordSocial.'); ?></p>
			</div>
		</div>
	</div>
</div>
<?php
// donators
$filec = "";
$filec = @file_get_contents("http://wso.li/donators.txt");
if(!empty($filec)) :
?>
<div class="postbox-container">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 style="cursor: default"><?php _e("Donators", "wordsocial"); ?></h3>
			<div class="inside">
				<p><?php echo nl2br($filec); ?></p>
			</div>
		</div>
	</div>
</div>
<?php
endif;
}

function wso_show_page_general()
{
	$wsoOpts = get_option(WSO_OPTIONS);
	
	$label = __('Publish content after ... minutes', 'wordsocial');
	$imm = __('immediately', 'wordsocial');
	
	$sel0 = ((int)$wsoOpts["time"] == 0) ? ' selected="selected"' : '';
	$sel2 = ((int)$wsoOpts["time"] == 2) ? ' selected="selected"' : '';
	$sel5 = ((int)$wsoOpts["time"] == 5) ? ' selected="selected"' : '';
	$sel10 = ((int)$wsoOpts["time"] == 10) ? ' selected="selected"' : '';
	
	// retrieve image
	$msgRetImage = __("When it's possible to publish the content with an image, how do you like WordSocial get the image ?", 'wordsocial');
	$retImageFeatSelYes = ($wsoOpts['pictret'] == 'feat') ? ' checked="checked"' : '';
	$retImageFeatSelNo = ($wsoOpts['pictret'] != 'feat') ? ' checked="checked"' : '';
	$msgFeatImage = __('Featured Image', 'wordsocial');
	$msgCustomField = __('Custom Field', 'wordsocial');
	$hideField_wsoretpict = ($wsoOpts['pictret'] == 'feat') ? "jQuery('#wsoretpict').hide()" : '';
	
	// default image
	$msgDefaultImage = __('Default image', 'wordsocial');

	echo <<<PAGE
<table class="form-table">
	<tbody>
		<tr>
			<th style="width: 35%">
				<label for="wsotime">$label</label>
			</th>
			<td>
				<select name="wsotime" id="wsotime">
					<option value="0" $sel0>$imm</option>
					<option value="2" $sel2>2</option>
					<option value="5" $sel5>5</option>
					<option value="10" $sel10>10</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>$msgRetImage</th>
			<td>
				<input type="radio" value="feat" name="wsoretpicture" id="wsoretpicture1" $retImageFeatSelYes /> <label for="wsoretpicture1">$msgFeatImage</label>
				<input type="radio" value="custom" name="wsoretpicture" id="wsoretpicture2" $retImageFeatSelNo /> <label for="wsoretpicture2">$msgCustomField</label>
				<input type="text" name="wsoretpict" id="wsoretpict" value="{$wsoOpts['pictret']}" />
				<script type="text/javascript">
				$hideField_wsoretpict
				jQuery('#wsoretpicture1').change(function(){jQuery('#wsoretpict').val("feat");jQuery('#wsoretpict').toggle();});
				jQuery('#wsoretpicture2').change(function(){if(jQuery('#wsoretpict').val() == "feat"){jQuery('#wsoretpict').val("");}jQuery('#wsoretpict').toggle();});
				</script>
		</tr>
		<tr>
			<th>
				<label for="wsopict">$msgDefaultImage</label>
			</th>
			<td>
				<input type="text" style="width: 100%" value="{$wsoOpts['pict']}" name="wsopicture" id="wsopict" />
			</td>
		</tr>
	</tbody>
</table>
PAGE;
}

function wso_show_page_facebook(&$fbAccounts)
{
	$fb = WSOManager::facebook();
	$wsoOpts = get_option(WSO_OPTIONS);
	
	if(!isset($wsoOpts['fbat']))
	{
		$msg = __('If you want to auto-publish your blog posts/pages on your facebook wall (or on the wall of your fanpages), you have to connect to your FB account', 'wordsocial');
		$urlPlugin = urlencode(get_bloginfo('wpurl')."/wp-admin/options-general.php?page=wordsocial");
	
		echo <<<PAGE
<p>$msg</p>
<p><a href="http://wso.li/iconnectFb.php?login&r=$urlPlugin"><img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif"></a></p>
PAGE;
	}
	else
	{
		$msg = __('Hello', 'wordsocial');
		$me = $fb->api('/me', array('access_token' => $wsoOpts['fbat']));
		
		$fbopts = explode("|", $wsoOpts['fbopts']);
		
		// select account
		$msgAccountsSelect =  __('Choose the page on which you would like to publish your posts', 'wordsocial');
		$accountsSelect = "<option value='-1' ". ( ($wsoOpts['fbpat'] == $wsoOpts['fbat']) ? " selected='selected'" : '' ) .">{$me['name']} (My Wall)</option>";
		foreach($fbAccounts['data'] as $k => $v) :
			$sel = ($wsoOpts['fbpat'] == $v['access_token']) ? ' selected="selected"' : '';
			$accountsSelect .= "<option value='$k' $sel>{$v['name']} ({$v['category']})</option>";
		endforeach;
		
		// publish post
		$msgPublishPost = __('Publish posts by default ?', 'wordsocial');
		$publishPostSelYes = ((int)$fbopts[WSO_FBOPT_POST] == 1) ? 'checked="checked"' : '';
		$msgYes = __('Yes', 'wordsocial');
		$publishPostSelNo = ((int)$fbopts[WSO_FBOPT_POST] == 0) ? 'checked="checked"' : '';
		$msgNo = __('No', 'wordsocial');
		
		// publish page
		$msgPublishPage = __('Publish pages by default ?', 'wordsocial');
		$publishPageSelYes = ((int)$fbopts[WSO_FBOPT_PAGE] == 1) ? 'checked="checked"' : '';
		$publishPageSelNo = ((int)$fbopts[WSO_FBOPT_PAGE] == 0) ? 'checked="checked"' : '';
		
		// comment
		$msgMessage = __('Show a comment ?', 'wordsocial');
		$publishMsgSelYes = ((int)$fbopts[WSO_FBOPT_MESSAGE] == 1) ? 'checked="checked"' : '';
		$publishMsgSelNo = ((int)$fbopts[WSO_FBOPT_MESSAGE] == 0) ? 'checked="checked"' : '';
		
		// image
		$msgImage = __('Show an image ?', 'wordsocial');
		$publishImageSelYes = ((int)$fbopts[WSO_FBOPT_IMAGE] == 1) ? 'checked="checked"' : '';
		$publishImageSelNo = ((int)$fbopts[WSO_FBOPT_IMAGE] == 0) ? 'checked="checked"' : '';
		
		// preview
		$msgPreview = __('This is a preview:', 'wordsocial');
		$imgUrlPreview = plugins_url('medias/preview-fb.jpg', __FILE__);
		
		
		echo <<<PAGE
<p>$msg <strong>{$me['name']}</strong>. <a href="options-general.php?page=wordsocial&disconnect=fb"><img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif"></a></p>
<table class="form-table">
	<tbody>
		<tr>
			<th><label for="wsofbat">$msgAccountsSelect</label></th>
			<td>
				<select name="wsofb-access_token" id="wsofbat">
					$accountsSelect
				</select>
			</td>
		</tr>
		<tr>
			<th>$msgPublishPost</th>
			<td>
				<input type="radio" value="1" name="wsofbpublishposts" id="wsofbenable" $publishPostSelYes /> <label for="wsofbenable">$msgYes</label> <input type="radio" value="0" name="wsofbpublishposts" id="wsofbdisable" $publishPostSelNo /> <label for="wsofbdisable">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgPublishPage</th>
			<td>
				<input type="radio" value="1" name="wsofbpublishpages" id="wsofbenablepages" $publishPageSelYes /> <label for="wsofbenablepages">$msgYes</label> <input type="radio" value="0" name="wsofbpublishpages" id="wsofbdisablepages" $publishPageSelNo /> <label for="wsofbdisablepages">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgMessage</th>
			<td>
				<input type="radio" value="1" name="wsofbmessage" id="wsofb_show_message" $publishMsgSelYes /> <label for="wsofb_show_message">$msgYes</label> <input type="radio" value="0" name="wsofbmessage" id="wsofb_hide_message" $publishMsgSelNo /> <label for="wsofb_hide_message">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgImage</th>
			<td>
				<input type="radio" value="1" name="wsofb_showpicture" id="wsofb_show_picture" $publishImageSelYes /> <label for="wsofb_show_picture">$msgYes</label> <input type="radio" value="0" name="wsofb_showpicture" id="wsofb_hide_picture" $publishImageSelNo /> <label for="wsofb_hide_picture">$msgNo</label>
			</td>
		</tr>
	</tbody>
</table>
<p>$msgPreview</p>
<img src="$imgUrlPreview" />
PAGE;
	}
}

function wso_show_page_twitter()
{
	$tw = WSOManager::twitter();
	
	$wsoOpts = get_option(WSO_OPTIONS);

	if(!isset($wsoOpts['twat']))
	{				
		$msg = __('If you want to auto-publish your blog posts/pages on your Twitter account, you have to authorize WordSocial to publish on it', 'wordsocial');
			
		echo <<<PAGE
<p>$msg</p>
<p><a href="options-general.php?page=wordsocial&connect=tw"><img src="http://si0.twimg.com/images/dev/buttons/sign-in-with-twitter-d-sm.png" /></a></p>
PAGE;
	}
	else
	{
		$user = $tw->get('account/verify_credentials');
		$msg = __('Hello', 'wordsocial');
		$msgDisconnect = __('Disconnect', 'wordsocial');
		
		$twopts = explode("|", $wsoOpts['twopts']);
		
		// publish post
		$msgPublishPost = __('Publish posts by default ?', 'wordsocial');
		$publishPostSelYes = ((int)$twopts[WSO_TWOPT_POST] == 1) ? 'checked="checked"' : '';
		$msgYes = __('Yes', 'wordsocial');
		$publishPostSelNo = ((int)$twopts[WSO_TWOPT_POST] == 0) ? 'checked="checked"' : '';
		$msgNo = __('No', 'wordsocial');
		
		// publish page
		$msgPublishPage = __('Publish pages by default ?', 'wordsocial');
		$publishPageSelYes = ((int)$twopts[WSO_TWOPT_PAGE] == 1) ? 'checked="checked"' : '';
		$publishPageSelNo = ((int)$twopts[WSO_TWOPT_PAGE] == 0) ? 'checked="checked"' : '';
		
		// tweet format
		$msgTweetFormat = __('Tweet format :', 'wordsocial');
		$tweetFormat = stripslashes($wsoOpts['twfmt']);
		$msgPostTitle = __('Post title', 'wordsocial');
		$msgPostLink = __('Link to the post', 'wordsocial');
		$msgPostExcerpt = __('Excerpt from the post', 'wordsocial');
		$msgPostComment = __('A comment (you write the comment into the post editor)', 'wordsocial');
		
		// shortening method
		$msgShortMethod = __('Shortening method for the link:', 'wordsocial');
		$bitlySel = ($wsoOpts["wsourl"] == "bitly") ? 'selected="selected"' : '';
		$obitlySel = ($wsoOpts["wsourl"] == "obitly") ? ' selected="selected"' : '';
		$wsoSel = ($wsoOpts["wsourl"] == "wso") ? ' selected="selected"' : '';
		$yourlsSel = ($wsoOpts["wsourl"] == "yourls") ? ' selected="selected"' : '';
		$msgOwnBitly = __('Your own bit.ly configuration', 'wordsocial');
		$msgOwnYourls = __('Your own YOURLS configuration', 'wordsocial');
		$msgYourlsUrl = __('URL Yourls:', 'wordsocial');
		$msgSignature = __('Signature:', 'wordsocial');
		$msgBitlyLogin = __('Login Bit.ly:', 'wordsocial');
		$msgApiKey = __('API Key:', 'wordsocial');
		$jsHide_wso_yourls = ($wsoOpts["wsourl"] != "yourls") ? "jQuery('#wso_yourls').hide();" : '';
		$jsHide_wso_obitly = ($wsoOpts["wsourl"] != "obitly") ? "jQuery('#wso_obitly').hide();" : '';
		
		$msgPreview = __('This is a preview:', 'wordsocial');
		$imgUrlPreview = plugins_url('medias/preview-tw.jpg', __FILE__);
		
		
		echo <<<PAGE
<p>$msg <strong>{$user->screen_name}</strong>. <a href="options-general.php?page=wordsocial&disconnect=tw">[$msgDisconnect]</a></p>
<table class="form-table">
	<tbody>
		<tr>
			<th>$msgPublishPost</th>
			<td>
				<input type="radio" value="1" name="wsotwpublishposts" id="wsotwenable" $publishPostSelYes /> <label for="wsotwenable">$msgYes</label> <input type="radio" value="0" name="wsotwpublishposts" id="wsotwdisable" $publishPostSelNo /> <label for="wsotwdisable">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgPublishPage</th>
			<td>
				<input type="radio" value="1" name="wsotwpublishpages" id="wsotwenablepages" $publishPageSelYes /> <label for="wsotwenablepages">$msgYes</label> <input type="radio" value="0" name="wsotwpublishpages" id="wsotwdisablepages" $publishPageSelNo /> <label for="wsotwdisablepages">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th><label for="wsotwfmt">$msgTweetFormat</label></th>
			<td>
				<input type="text" style="width: 100%" value="$tweetFormat" name="wsotwfmt" id="wsotwfmt" />
				<p>%title : $msgPostTitle<br/>
				%link : $msgPostLink<br/>
				%excerpt : $msgPostExcerpt<br/>
				%comment : $msgPostComment<br/></p>
			</td>
		</tr>
		<tr>
			<th>$msgShortMethod</th>
			<td>
				<select name="wsotwwsourl" id="wsotwwsourl">
					<option value="bitly" $bitlySel>bit.ly</option>
					<option value="obitly" $obitlySel>$msgOwnBitly</option>
					<option value="wso" $wsoSel>wso.li</option>
					<option value="yourls" $yourlsSel>$msgOwnYourls</option>
				</select><br />
				<div id="wso_yourls">
					$msgYourlsUrl <input type="text" name="wso_yourls_url" value="{$wsoOpts['wsourlp'][0]}" /> (http://your-domain.com/yourls-api.php)<br />
					$msgSignature <input type="text" name="wso_yourls_sign" value="{$wsoOpts['wsourlp'][1]}" /><br />
				</div>
				<div id="wso_obitly">
					$msgBitlyLogin <input type="text" name="wso_obitly_login" value="{$wsoOpts['wsourlp'][0]}" /><br />
					$msgApiKey <input type="text" name="wso_obitly_apikey" value="{$wsoOpts['wsourlp'][1]}" />
				</div>
				<script type="text/javascript">
				$jsHide_wso_yourls
				$jsHide_wso_obitly
				jQuery('#wsotwwsourl').change(function(){
					var value = jQuery(this).val();
					if(value == "yourls") { jQuery('#wso_yourls').show(); jQuery('#wso_obitly').hide(); }
					else if(value == "obitly") { jQuery('#wso_obitly').show(); jQuery('#wso_yourls').hide(); }
					else { jQuery('#wso_yourls').hide(); jQuery('#wso_obitly').hide(); }
				});
				</script>
			</td>
		</tr>
	</tbody>
</table>
<p>$msgPreview</p>
<img src="$imgUrlPreview" />
PAGE;
	}
}

function wso_show_page_linkedin()
{
	$li = WSOManager::linkedin();
	$wsoOpts = get_option(WSO_OPTIONS);
	
	if(!isset($wsoOpts['liat']))
	{
		$msg = __('If you want to auto-publish your blog posts/pages on your LinkedIn, you have to connect to your account.', 'wordsocial');
	
		echo <<<PAGE
<p>$msg</p>
<p><a href="options-general.php?page=wordsocial&connect=li"><img height="32" width="142" src="http://developer.linkedin.com/servlet/JiveServlet/downloadImage/102-1225-1-1120/142-32/js-signin.png" alt="js-signin.png" /></a></p>
PAGE;
	}
	else
	{
	
		$msg = __('Hello', 'wordsocial');
		$msgDisconnect = __('Disconnect', 'wordsocial');
		$response = $li->profile('~:(id,first-name,last-name,picture-url)');
		
		if($response['success'] === TRUE)
		{
			$response['linkedin'] = json_decode($response['linkedin'], true);
			$me = $response['linkedin']['firstName'] . " " . $response['linkedin']['lastName'];
		}
		else
		{
			wso_log_msg(__("LinkedIn : Error retrieving profile information",'wordsocial'));
		}
		
		$liopts = explode("|", $wsoOpts['liopts']);
				
		// publish post
		$msgPublishPost = __('Publish posts by default ?', 'wordsocial');
		$publishPostSelYes = ((int)$liopts[WSO_LIOPT_POST] == 1) ? 'checked="checked"' : '';
		$msgYes = __('Yes', 'wordsocial');
		$publishPostSelNo = ((int)$liopts[WSO_LIOPT_POST] == 0) ? 'checked="checked"' : '';
		$msgNo = __('No', 'wordsocial');
		
		// publish page
		$msgPublishPage = __('Publish pages by default ?', 'wordsocial');
		$publishPageSelYes = ((int)$liopts[WSO_LIOPT_PAGE] == 1) ? 'checked="checked"' : '';
		$publishPageSelNo = ((int)$liopts[WSO_LIOPT_PAGE] == 0) ? 'checked="checked"' : '';
		
		// comment
		$msgMessage = __('Show a comment ?', 'wordsocial');
		$publishMsgSelYes = ((int)$liopts[WSO_LIOPT_MESSAGE] == 1) ? 'checked="checked"' : '';
		$publishMsgSelNo = ((int)$liopts[WSO_LIOPT_MESSAGE] == 0) ? 'checked="checked"' : '';
		
		// image
		$msgImage = __('Show an image ?', 'wordsocial');
		$publishImageSelYes = ((int)$liopts[WSO_LIOPT_IMAGE] == 1) ? 'checked="checked"' : '';
		$publishImageSelNo = ((int)$liopts[WSO_LIOPT_IMAGE] == 0) ? 'checked="checked"' : '';
		
		$msgPreview = __('This is a preview:', 'wordsocial');
		$imgUrlPreview = plugins_url('medias/preview-li.jpg', __FILE__);
		
		echo <<<PAGE
<p>$msg <strong>{$me}</strong>. <a href="options-general.php?page=wordsocial&disconnect=li">[$msgDisconnect]</a></p>
<table class="form-table">
	<tbody>
		<tr>
			<th>$msgPublishPost</th>
			<td>
				<input type="radio" value="1" name="wsolipublishposts" id="wsolienable" $publishPostSelYes /> <label for="wsolienable">$msgYes</label> <input type="radio" value="0" name="wsolipublishposts" id="wsolidisable" $publishPostSelNo /> <label for="wsolidisable">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgPublishPage</th>
			<td>
				<input type="radio" value="1" name="wsolipublishpages" id="wsolienablepages" $publishPageSelYes /> <label for="wsolienablepages">$msgYes</label> <input type="radio" value="0" name="wsolipublishpages" id="wsolidisablepages" $publishPageSelNo /> <label for="wsolidisablepages">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgMessage</th>
			<td>
				<input type="radio" value="1" name="wsolimessage" id="wsoli_show_message" $publishMsgSelYes /> <label for="wsoli_show_message">$msgYes</label> <input type="radio" value="0" name="wsolimessage" id="wsoli_hide_message" $publishMsgSelNo /> <label for="wsoli_hide_message">$msgNo</label>
			</td>
		</tr>
		<tr>
			<th>$msgImage</th>
			<td>
				<input type="radio" value="1" name="wsoli_showpicture" id="wsoli_show_picture" $publishImageSelYes /> <label for="wsoli_show_picture">$msgYes</label> <input type="radio" value="0" name="wsoli_showpicture" id="wsoli_hide_picture" $publishImageSelNo /> <label for="wsoli_hide_picture">$msgNo</label>
			</td>
		</tr>
	</tbody>
</table>
<p>$msgPreview</p>
<img src="$imgUrlPreview" />
PAGE;
	}
}