<?php
/*
Plugin Name: List Rank Dashboard Widget
Plugin URI: http://blog.bokhorst.biz/4014/computers-en-internet/wordpress-plugin-list-rank-dashboard-widget/
Description: Displays the rankings of a configurable list of sites in a dashboard widget
Version: 1.7
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/about/
*/

/*
	Copyright 2010, 2011 Marcel Bokhorst

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

#error_reporting(E_ALL);

// Check PHP version
if (version_compare(PHP_VERSION, '5', '<'))
	die('List Rank Dashboard Widget requires at least PHP 5, installed version is ' . PHP_VERSION);

// Include list rank class
if (!class_exists('WPListRank'))
	require_once('wp-list-rank-class.php');

// Check pre-requisites
WPListRank::Check_prerequisites();

// Start plugin
global $wp_list_rank;
$wp_list_rank = new WPListRank();

// Schedule cron if needed
if (!wp_next_scheduled('wplr_cron')) {
	$day = intval(time() / (24*3600)) + 1;
	wp_schedule_event($day * 24*3600, 'daily', 'wplr_cron');
}

add_action('wplr_cron', 'wplr_cron');

if (!function_exists('wplr_cron')) {
	function wplr_cron() {
		global $wp_list_rank;
		$wp_list_rank->Cron();
	}
}

// That's it!

?>
