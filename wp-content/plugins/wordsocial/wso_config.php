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

define('WSO_PLUGIN_URL', WP_PLUGIN_URL . '/wordsocial/');
define('WSO_ABS_PLUGIN_URL', ABSPATH . 'wp-content/plugins/wordsocial/');
define('WSO_OPTIONS', 'wso_opt');
define('WSO_VERSION', '0.4.4');
define('WSO_DEBUG', false);
define('WSO_DB_LOG', 'wso_logs');

require_once('inc/facebook/facebook.php');
require_once('inc/twitteroauth.php');
require_once('inc/linkedin.php');
require_once('inc/wsourl.php');

class WSOManager
{
	private static $m_facebook;
	private static $m_twitter;
	private static $m_linkedin;
	
	public static function facebook()
	{
		if(!isset(self::$m_facebook))
		{
			self::$m_facebook = new Facebook(array(
				'appId'  => '198517920178057',
				'secret' => '52e29c0fd4f0e233db6120e4b0189a37'
			));
		}
		return self::$m_facebook;
	}
	
	public static function twitter()
	{
		if(!isset(self::$m_twitter))
		{	
			$wsoOpts = get_option(WSO_OPTIONS);
			
			$at = NULL;
			$ats = NULL;
			if(isset($_SESSION['oauth']['twitter']['oauth_token'])) $at = $_SESSION['oauth']['twitter']['oauth_token'];
			if(isset($_SESSION['oauth']['twitter']['oauth_token'])) $ats = $_SESSION['oauth']['twitter']['oauth_token_secret'];
			if(isset($wsoOpts['twat'])) $at = $wsoOpts['twat'];
			if(isset($wsoOpts['twats'])) $ats = $wsoOpts['twats'];
			
			self::$m_twitter = new TwitterOAuth("er4yQn8kiqGsvtDv5FgOA", "AHwFQFi4twWMYSagHAAUMaIPbsEXKq52KSyPNymBQo", $at, $ats);
		}
		return self::$m_twitter;
	}
	
	public static function linkedin()
	{
		if(!isset(self::$m_linkedin))
		{
			$wsoOpts = get_option(WSO_OPTIONS);
			self::$m_linkedin = new LinkedIn(array(
				'appKey'      => '-Nc0w1VO2MA-J-EBxTrTdhMUNyxD6wPV1G72BDRazoetRIBwCKjhZBrLQR90bWmX',
				'appSecret'   => 'I6CrJdaldRzFnhkwf4_IzDbN8T0yDZvuTC4izFLotoM6_EfqxDl-fh6hRJA1sXQt',
				'callbackUrl' => NULL 
			));
			if(isset($wsoOpts['liat']) && isset($wsoOpts['liats']))
			{
				self::$m_linkedin->setTokenAccess(array('oauth_token' => $wsoOpts['liat'], 'oauth_token_secret' => $wsoOpts['liats']));
			}
		}
		return self::$m_linkedin;
	}
	
	public static function getFb() { return self::$m_facebook; }
	public static function getTw() { return self::$m_twitter; }
	public static function getLi() { return self::$m_linkedin; }
}

define('WSO_OPT_POST', 0);
define('WSO_OPT_PAGE', 1);
define('WSO_OPT_MESSAGE', 2);

define('WSO_FBOPT_POST', WSO_OPT_POST);
define('WSO_FBOPT_PAGE', WSO_OPT_PAGE);
define('WSO_FBOPT_MESSAGE', WSO_OPT_MESSAGE);
define('WSO_FBOPT_IMAGE', 3);

define('WSO_TWOPT_POST', WSO_OPT_POST);
define('WSO_TWOPT_PAGE', WSO_OPT_PAGE);

define('WSO_LIOPT_POST', WSO_OPT_POST);
define('WSO_LIOPT_PAGE', WSO_OPT_PAGE);
define('WSO_LIOPT_MESSAGE', WSO_OPT_MESSAGE);
define('WSO_LIOPT_IMAGE', 3);