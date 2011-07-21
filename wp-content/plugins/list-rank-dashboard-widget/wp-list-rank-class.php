<?php

/*
	Support class List Rank Dashboard Widget
	Copyright (c) 2010, 2011 by Marcel Bokhorst
*/

// Define constants
define('c_wplr_text_domain', 'wp-list-rank');

define('c_wplr_option_version', 'wplr_version');
define('c_wplr_option_sites', 'wplr_sites');
define('c_wplr_option_metric', 'wplr_metric_');
define('c_wplr_option_gapi', 'wplr_gapi');
define('c_wplr_option_yapi', 'wplr_yapi');
define('c_wplr_option_widget', 'wplr_widget');
define('c_wplr_option_cap', 'wplr_cap');
define('c_wplr_option_clean', 'wplr_clean');
define('c_wplr_option_donated', 'wplr_donated');
define('c_wplr_option_nospsn', 'wplr_nospsn');
define('c_wplr_option_debug', 'wplr_debug');
define('c_wplr_option_last_cron', 'wplr_last_cron');

define('c_wplr_cache_age', 'wplr_cache_age');
define('c_wplr_cache_entry', 'wplr_cache_entry');

define('c_wplr_action_arg', 'wplr_action');
define('c_wplr_action_list', 'list');
define('c_wplr_action_refresh', 'refresh');
define('c_wplr_action_cron', 'cron');
define('c_wplr_param_nonce', 'nonce');
define('c_wplr_nonce_ajax', 'wplr-nonce-ajax');

require_once(ABSPATH . '/wp-admin/includes/plugin.php');
require_once(ABSPATH . WPINC . '/pluggable.php');

// Define class
if (!class_exists('WPListRank')) {
	class WPListRank {
		// Class variables
		var $main_file = null;
		var $plugin_url = null;
		var $sites = null;
		var $metrics = null;

		// Constructor
		function __construct() {
			// Get file/URL
			$this->main_file = str_replace('-class', '', __FILE__);
			$this->plugin_url = WP_PLUGIN_URL . '/' . basename(dirname($this->main_file));

			// Register (de)activation hook
			register_activation_hook($this->main_file, array(&$this, 'Activate'));
			register_deactivation_hook($this->main_file, array(&$this, 'Deactivate'));

			// Register actions/filters
			add_action('init', array(&$this, 'Init'));
			if (is_admin()) {
				add_action('admin_menu', array(&$this, 'Admin_menu'));
				add_action('wp_dashboard_setup', array(&$this, 'Dashboard_setup'));
				add_action('wp_ajax_wplr_ajax', array(&$this, 'Ajax_handler'));
			}

			// Short code handling
			add_shortcode('list_rank', array(&$this, 'Shortcode_handler'));
			if (get_option(c_wplr_option_widget))
				add_filter('widget_text', 'do_shortcode');
		}

		// Handle plugin activation
		function Activate() {
			$version = get_option(c_wplr_option_version);
			if (!$version) {
				// Set version
				update_option(c_wplr_option_version, 1);

				// Default own site
				if (!get_option(c_wplr_option_sites)) {
					$blog = get_bloginfo('wpurl');
					if (strpos($blog, 'http://') === 0)
						$blog = substr($blog, 7);
					update_option(c_wplr_option_sites, $blog);
				}

				// Copy Google API Key from XML Google Maps
				if (!get_option(c_wplr_option_gapi))
					update_option(c_wplr_option_gapi, get_option('xmlgm_uid'));

				// Default metrics to show
				update_option(c_wplr_option_metric . 'googlepr', true);
				if (get_option(c_wplr_option_gapi))
					update_option(c_wplr_option_metric . 'googlebl', true);
				update_option(c_wplr_option_metric . 'alexarank', true);
				update_option(c_wplr_option_metric . 'delicious', true);
			}

			// Upgrade to version 2
			if ($version <= 1) {
				update_option(c_wplr_option_version, 2);
				update_option(c_wplr_option_cap, 'read');
			}
		}

		// Handle plugin deactivation
		function Deactivate() {
			// Remove pseudo cron schedule
			wp_clear_scheduled_hook('wplr_cron');

			// Cleanup if requested
			if (get_option(c_wplr_option_clean)) {
				// Delete cache & metrics
				global $wpdb;
				$query = "SELECT option_name FROM " . $wpdb->options;
				$query .= " WHERE option_name LIKE '" . c_wplr_cache_entry . "%'";
				$query .= " OR option_name LIKE '" . c_wplr_cache_age . "%'";
				$query .= " OR option_name LIKE '" . c_wplr_option_metric . "%'";
				$rows = $wpdb->get_results($query);
				foreach ($rows as $row)
					delete_option($row->option_name);

				// Delete options
				delete_option(c_wplr_option_version);
				delete_option(c_wplr_option_sites);
				delete_option(c_wplr_option_gapi);
				delete_option(c_wplr_option_yapi);
				delete_option(c_wplr_option_widget);
				delete_option(c_wplr_option_cap);
				delete_option(c_wplr_option_clean);
				delete_option(c_wplr_option_donated);
				delete_option(c_wplr_option_nospsn);
				delete_option(c_wplr_option_debug);
				delete_option(c_wplr_option_last_cron);
			}
		}

		// Handle initialize
		function Init() {
			if (is_admin()) {
				// I18n
				load_plugin_textdomain(c_wplr_text_domain, false, basename(dirname($this->main_file)));

				// Load style sheet
				$css_name = $this->Change_extension(basename($this->main_file), '.css');
				if (file_exists(WP_CONTENT_DIR . '/uploads/' . $css_name))
					$css_url = WP_CONTENT_URL . '/uploads/' . $css_name;
				else if (file_exists(TEMPLATEPATH . '/' . $css_name))
					$css_url = get_bloginfo('template_directory') . '/' . $css_name;
				else
					$css_url = $this->plugin_url . '/' . $css_name;
				wp_register_style('wplr_style', $css_url);
				wp_enqueue_style('wplr_style');

				// Enqueue jQuery
				wp_enqueue_script('jquery');

				// Get sites & metrics
				$this->Get_sites();
				$this->Define_metrics();

				// Register settings
				register_setting('wp-list-rank', c_wplr_option_sites);
				register_setting('wp-list-rank', c_wplr_option_gapi);
				register_setting('wp-list-rank', c_wplr_option_yapi);
				register_setting('wp-list-rank', c_wplr_option_widget);
				register_setting('wp-list-rank', c_wplr_option_cap);
				register_setting('wp-list-rank', c_wplr_option_clean);
				register_setting('wp-list-rank', c_wplr_option_donated);
				register_setting('wp-list-rank', c_wplr_option_nospsn);
				register_setting('wp-list-rank', c_wplr_option_debug);
				foreach ($this->metrics as $metric)
					register_setting('wp-list-rank', c_wplr_option_metric . strtolower($metric['name']));
			}
		}

		// Add options page
		function Admin_menu() {
			add_options_page('List Rank', 'List Rank', 'manage_options', 'list-rank', array(&$this, 'Options_page'));
		}

		function Render_pluginsponsor() {
			if (!get_option(c_wplr_option_nospsn)) {
?>
				<script type="text/javascript">
				var psHost = (("https:" == document.location.protocol) ? "https://" : "http://");
				document.write(unescape("%3Cscript src='" + psHost + "pluginsponsors.com/direct/spsn/display.php?client=list-rank-dashboard-widget&spot=' type='text/javascript'%3E%3C/script%3E"));
				</script>
				<a id="list-rank-sponsorship" href="http://pluginsponsors.com/privacy.html" target="_blank">
				<?php _e('Privacy in the Sustainable Plugins Sponsorship Network', c_wplr_text_domain); ?></a>
<?php
			}
		}

		function Render_info_panel() {
?>
			<div id="list-rank-resources">
			<h3><?php _e('Resources', c_wplr_text_domain); ?></h3>
			<ul>
			<li><a href="http://wordpress.org/extend/plugins/list-rank-dashboard-widget/faq/" target="_blank"><?php _e('Frequently asked questions', c_wplr_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/4014/computers-en-internet/wordpress-plugin-list-rank-dashboard-widget/" target="_blank"><?php _e('Support page', c_wplr_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/about/" target="_blank"><?php _e('About the author', c_wplr_text_domain); ?></a></li>
			<li><a href="http://en.wikipedia.org/wiki/PageRank" target="_blank"><?php _e('Google PageRank', c_wplr_text_domain); ?></a></li>
			</ul>
<?php		if (!get_option(c_wplr_option_donated)) { ?>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>
<?php		} ?>
			</div>
<?php
		}

		// Render options page
		function Options_page() {
			if (current_user_can('manage_options')) {
				$this->Render_pluginsponsor();
				echo '<div class="wrap">';
				$this->Render_info_panel();
?>
				<div id="list-rank-options">
				<h2><?php _e('List Rank', c_wplr_text_domain); ?></h2>

				<form method="post" action="options.php">
				<?php wp_nonce_field('update-options'); ?>
				<?php settings_fields('wp-list-rank'); ?>

				<table class="form-table">

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_sites"><?php _e('Sites:', c_wplr_text_domain); ?></label>
				</th><td>
					<textarea id="wplr_opt_sites" name="<?php echo c_wplr_option_sites; ?>"><?php echo get_option(c_wplr_option_sites); ?></textarea>
				</td></tr>
<?php
				foreach ($this->metrics as $metric) {
					$name = strtolower($metric['name']);
					$option = c_wplr_option_metric . $name;
?>
					<tr valign="top"><th scope="row">
						<label for="wplr_opt_<?php echo $name; ?>"><?php echo __('Display', c_wplr_text_domain) . ' ' . $metric['description'] . ':'; ?></label>
					</th><td>
						<input id="wplr_opt_<?php echo $name; ?>" name="<?php echo $option; ?>" type="checkbox"<?php if (get_option($option)) echo ' checked="checked"'; ?> />
						<span class="list-rank-remark"><?php echo $metric['remark']; ?></span>
					</td></tr>
<?php
				}
?>
				<tr valign="top"><th scope="row">
					<label for="wplr_opt_gapi"><?php _e('Google API Key:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_gapi" name="<?php echo c_wplr_option_gapi; ?>" type="text" value="<?php echo get_option(c_wplr_option_gapi); ?>" />
					<span><a href="http://code.google.com/apis/ajaxsearch/key.html" target="_blank"><?php _e('Get one free', c_wplr_text_domain); ?></a></span>
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_yapi"><?php _e('Yahoo! Application ID:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_yapi" name="<?php echo c_wplr_option_yapi; ?>" type="text" value="<?php echo get_option(c_wplr_option_yapi); ?>" />
					<span><a href="https://developer.apps.yahoo.com/wsregapp/" target="_blank"><?php _e('Get one free', c_wplr_text_domain); ?></a></span>
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_widget"><?php _e('Execute shortcodes in (sidebar) widgets:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_widget" name="<?php echo c_wplr_option_widget; ?>" type="checkbox"<?php if (get_option(c_wplr_option_widget)) echo ' checked="checked"'; ?> />
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_cap"><?php _e('Required capability:', c_wplr_text_domain); ?></label>
				</th><td>
					<select id="wplr_opt_cap" name="<?php echo c_wplr_option_cap; ?>">
<?php
						// Get list of capabilities
						global $wp_roles;
						$capabilities = array();
						foreach ($wp_roles->role_objects as $key => $role)
							if (is_array($role->capabilities))
								foreach ($role->capabilities as $cap => $grant)
									$capabilities[$cap] = $cap;
						sort($capabilities);

						// List capabilities and select current
						$current_cap = get_option(c_wplr_option_cap);
						foreach ($capabilities as $cap) {
							echo '<option value="' . $cap . '"';
							if ($cap == $current_cap)
								echo ' selected';
							echo '>' . $cap . '</option>';
						}
?>
					</select>
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_clean"><?php _e('Clean on deactivate:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_clean" name="<?php echo c_wplr_option_clean; ?>" type="checkbox"<?php if (get_option(c_wplr_option_clean)) echo ' checked="checked"'; ?> />
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_donated"><?php _e('I have donated to this plugin:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_donated" name="<?php echo c_wplr_option_donated; ?>" type="checkbox"<?php if (get_option(c_wplr_option_donated)) echo ' checked="checked"'; ?> />
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_nospsn"><?php _e('I don\'t want to support this plugin with the Sustainable Plugins Sponsorship Network:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_nospsn" name="<?php echo c_wplr_option_nospsn; ?>" type="checkbox"<?php if (get_option(c_wplr_option_nospsn)) echo ' checked="checked"'; ?> />
				</td></tr>

				<tr valign="top"><th scope="row">
					<label for="wplr_opt_debug"><?php _e('Debug:', c_wplr_text_domain); ?></label>
				</th><td>
					<input id="wplr_opt_debug" name="<?php echo c_wplr_option_debug; ?>" type="checkbox"<?php if (get_option(c_wplr_option_debug)) echo ' checked="checked"'; ?> />
				</td></tr>

				</table>
<?php
				$options[] = c_wplr_option_sites;
				$options[] = c_wplr_option_gapi;
				$options[] = c_wplr_option_yapi;
				$options[] = c_wplr_option_widget;
				$options[] = c_wplr_option_cap;
				$options[] = c_wplr_option_clean;
				$options[] = c_wplr_option_donated;
				$options[] = c_wplr_option_nospsn;
				$options[] = c_wplr_option_debug;
				foreach ($this->metrics as $metric)
					$options[] = c_wplr_option_metric . strtolower($metric['name']);
?>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="<?php echo implode(',', $options); ?>" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes', c_wplr_text_domain) ?>" />
				</p>

				</form>
				</div>
				</div>
<?php
			}
		}

		// Add dashboard widget
		function Dashboard_setup() {
			if (current_user_can(get_option(c_wplr_option_cap)))
				wp_add_dashboard_widget('list-rank-container', __('List Rank', c_wplr_text_domain), array(&$this, 'Dashboard'));
		}

		// Render dashboard widget
		function Dashboard() {
			// Security
			$nonce = wp_create_nonce(c_wplr_nonce_ajax);
?>
			<div id="list-rank-dashboard"></div>
			<div id="list-rank-action">
			<a id="list-rank-refresh" href="#"><?php _e('Refresh', c_wplr_text_domain); ?></a>
<?php		if (get_option(c_wplr_option_debug)) { ?>
			<a id="list-rank-cron" href="#"><?php _e('Cron', c_wplr_text_domain); ?></a>
<?php		} ?>
<?php		if (!get_option(c_wplr_option_donated)) { ?>
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=AJSBB7DGNA3MJ&lc=US&item_name=List%20Rank%20Dashboard%20Widget%20WordPress%20Plugin&item_number=Marcel%20Bokhorst&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted" target="_blank"><?php _e('Donate', c_wplr_text_domain); ?></a>
<?php		} ?>
			</div>

			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready(function($) {
				$('#list-rank-container').bind('list-rank-container-toggle', function(e) {
					if (!$('#list-rank-container').hasClass('closed')) {
						var load = $('<img src="<?php echo $this->plugin_url  . '/img/ajax-loader.gif'; ?>" alt="wait" />');
						$('#list-rank-dashboard').html(load);
						$.ajax({
							url: ajaxurl,
							type: 'GET',
							data:
							{
								action: 'wplr_ajax',
								<?php echo c_wplr_param_nonce; ?>: '<?php echo $nonce; ?>',
								<?php echo c_wplr_action_arg; ?>: '<?php echo c_wplr_action_list ?>'
							},
							dataType: 'text',
							cache: false,
							success: function(result) {
								$('#list-rank-dashboard').html(result);
							},
							error: function(x, stat, e) {
								$('#list-rank-dashboard').html('<span class="list-rank-notice">Error ' + x.status + '<\/span>');
							}
						});
					}
				});

				$('#list-rank-dashboard').ready(function() {
					$('#list-rank-container').trigger('list-rank-container-toggle');
				});

				$('#list-rank-container > .handlediv').click(function() {
					/* Executed before real class change */
					$('#list-rank-container').toggleClass('closed');
					$('#list-rank-container').trigger('list-rank-container-toggle');
					$('#list-rank-container').toggleClass('closed');
				});

				$('#list-rank-refresh').click(function() {
					var load = $('<img src="<?php echo $this->plugin_url  . '/img/ajax-loader.gif'; ?>" alt="wait" />');
					$('#list-rank-dashboard').html(load);

					/* Async fetch */
					$.ajax({
						url: ajaxurl,
						type: 'GET',
						data: {
							action: 'wplr_ajax',
							<?php echo c_wplr_param_nonce; ?>: '<?php echo $nonce; ?>',
							<?php echo c_wplr_action_arg; ?>: '<?php echo c_wplr_action_refresh ?>'
						},
						dataType: 'text',
						cache: false,
						success: function(result) {
							$('#list-rank-dashboard').html(result);
						},
						error: function(x, stat, e) {
							$('#list-rank-dashboard').html('<span class="list-rank-notice">Error ' + x.status + '<\/span>');
						}
					});
					return false;
				});

				$('#list-rank-cron').click(function() {
					var load = $('<img src="<?php echo $this->plugin_url  . '/img/ajax-loader.gif'; ?>" alt="wait" />');
					$('#list-rank-dashboard').html(load);

					/* Async post */
					$.ajax({
						url: ajaxurl,
						type: 'GET',
						data: {
							action: 'wplr_ajax',
							<?php echo c_wplr_param_nonce; ?>: '<?php echo $nonce; ?>',
							<?php echo c_wplr_action_arg; ?>: '<?php echo c_wplr_action_cron ?>'
						},
						dataType: 'text',
						cache: false,
						success: function(result) {
							$('#list-rank-dashboard').html(result);
						},
						error: function(x, stat, e) {
							$('#list-rank-dashboard').html('<span class="list-rank-notice">Error ' + x.status + '<\/span>');
						}
					});
					return false;
				});

			});
			/* ]]> */
			</script>
<?php
		}

		// Handle ajax calls
		function Ajax_handler() {
			if (isset($_REQUEST[c_wplr_action_arg])) {
				// Security check
				$nonce = $_REQUEST[c_wplr_param_nonce];
				if (!wp_verify_nonce($nonce, c_wplr_nonce_ajax))
					die('Unauthorized');

				// Load text domain
				load_plugin_textdomain(c_wplr_text_domain, false, basename(dirname($this->main_file)));

				header('Content-Type: text/html; charset=' . get_option('blog_charset'));

				if ($_REQUEST[c_wplr_action_arg] == c_wplr_action_list)
					$this->Ajax_list(true);
				else if ($_REQUEST[c_wplr_action_arg] == c_wplr_action_refresh)
					$this->Ajax_list(false);
				else if ($_REQUEST[c_wplr_action_arg] == c_wplr_action_cron) {
					$this->Cron();
					$this->Ajax_list(true);
				}
			}
			exit();
		}

		// Ajax get rank list
		function Ajax_list($usecache = true) {
			if (get_option(c_wplr_option_debug)) {
				echo 'Last cron: ' . date('r', get_option(c_wplr_option_last_cron)) . '<br />';
				echo 'Next cron: ' . date('r', wp_next_scheduled('wplr_cron')) . '<br />';
				echo 'Current time: ' . date('r') . '<br /><br />';
			}
			echo '<table><tr><th>' . __('Site', c_wplr_text_domain) . '</th>';
			foreach ($this->metrics as $metric)
				if ($metric['enabled'])
					echo '<th>' . str_replace(' ', '<br />', $metric['description']) . '</th>';
			echo '</tr>';
			foreach ($this->sites as $site) {
				if (!strpos($site['url'], 'https://'))
					$transport = 'http://';
				echo '<tr>';
				echo '<td><a href="' . $transport . $site['url'] . '" target="_blank">' . $site['url'] . '</a></td>';
				foreach ($this->metrics as $metric)
					if ($metric['enabled']) {
						$value = $this->getMetric($metric['name'], $site['url'], $usecache);
						if (is_numeric($value))
							$value = number_format($value);
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';
			}
			echo '</table>';
		}

		// Handle pseudo cron
		function Cron() {
			update_option(c_wplr_option_last_cron, time());
			$this->Get_sites();
			$this->Define_metrics();
			foreach ($this->sites as $site)
				foreach ($this->metrics as $metric)
					if ($metric['enabled'])
						$this->getMetric($metric['name'], $site['url'], false);
		}

		// Get site list
		function Get_sites() {
			$this->sites = array();
			foreach (explode("\n", get_option(c_wplr_option_sites)) as $site) {
				$url = trim($site);
				if (strpos($url, 'http://') === 0)
					$url = substr($url, 7);
				if ($url)
					$this->sites[] = array('url' => $url, 'enabled' => true);
			}
		}

		function Define_metrics() {
			$this->metrics = array();
			$this->metrics[] = array('name' => 'GooglePR', 'description' => __('Google PageRank', c_wplr_text_domain), 'remark' => '');
			$this->metrics[] = array('name' => 'GoogleBL', 'description' => __('Google Backlinks', c_wplr_text_domain), 'remark' => __('Requires a Google API Key', c_wplr_text_domain));
			$this->metrics[] = array('name' => 'AlexaRank', 'description' => __('Alexa Rank', c_wplr_text_domain), 'remark' => '');
			$this->metrics[] = array('name' => 'YahooBL', 'description' => __('Yahoo! Backlinks', c_wplr_text_domain), 'remark' => __('Requires a Yahoo! Application ID', c_wplr_text_domain));
			$this->metrics[] = array('name' => 'Delicious', 'description' => __('Delicious Posts', c_wplr_text_domain), 'remark' => '');

			// Reset options without needed key
			if (!get_option(c_wplr_option_gapi))
				update_option(c_wplr_option_metric . 'googlebl', false);
			if (!get_option(c_wplr_option_yapi))
				update_option(c_wplr_option_metric . 'yahoobl', false);

			// Enable/disable metrics
			for ($i = 0; $i < count($this->metrics); $i++)
				$this->metrics[$i]['enabled'] = get_option(c_wplr_option_metric . strtolower($this->metrics[$i]['name']));
		}

		// Handle [list_rank] short code
		function Shortcode_handler($atts) {
			$page = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$query = strpos($page, '?');
			if ($query)
				$page = substr($page, 0, strpos($page, $query));

			extract(shortcode_atts(array('name' => 'GooglePR', 'url' => $page), $atts));

			if (strpos($url, 'http://') === 0)
				$url = substr($url, 7);

			return $this->getMetric($name, $url);
		}

		// Helper get metric
		function getMetric($name, $url, $usecache = true) {
			$option = '_' . strtolower($name) . '_' . str_replace('.', '_', $url);
			$centry = c_wplr_cache_entry . $option;
			$cage = c_wplr_cache_age . $option;

			$entry = null;
			$age = get_option($cage);
			if ($usecache) {
				if ($age + 24*60*60 > time())
					$entry = get_option($centry);
			}

			if (!$entry) {
				$func = array(&$this, 'get' . $name);
				if (is_callable($func)) {
					if (!strpos($url, 'https://'))
						$transport = 'http://';
					$entry = call_user_func($func, $transport . $url);
					if ($entry == 0)
						$entry = '-';
					else if ($entry < 0)
						$entry = '?';
					update_option($centry, $entry);
					$new_age = time();
					update_option($cage, $new_age);
				}
				else
					$entry = $name . '?';
			}

			if (get_option(c_wplr_option_debug))
				return $entry . '<br />' . date('d/m H:i', $age) . ($new_age ? '<br />' . date('d/m H:i', $new_age) : '');
			else
				return $entry;
		}

		// Google page rank
		function GooglePRStrToNum($Str, $Check, $Magic)	{
			$Int32Unit = 4294967296;  // 2^32
			$length = strlen($Str);
			for ($i = 0; $i < $length; $i++) {
				$Check *= $Magic;
				if ($Check >= $Int32Unit) {
					$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
					$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
				}
				$Check += ord($Str{$i});
			}
			return $Check;
		}

		function GooglePRHashURL($String) {
			$Check1 = $this->GooglePRStrToNum($String, 0x1505, 0x21);
			$Check2 = $this->GooglePRStrToNum($String, 0, 0x1003F);
			$Check1 >>= 2;
			$Check1 = (($Check1 >> 4) & 0x3FFFFC0) | ($Check1 & 0x3F);
			$Check1 = (($Check1 >> 4) & 0x3FFC00) | ($Check1 & 0x3FF);
			$Check1 = (($Check1 >> 4) & 0x3C000) | ($Check1 & 0x3FFF);
			$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2) | ($Check2 & 0xF0F);
			$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000);
			return ($T1 | $T2);
		}

		function GooglePRCheckHash($Hashnum) {
			$CheckByte = 0;
			$Flag = 0;
			$HashStr = sprintf('%u', $Hashnum) ;
			$length = strlen($HashStr);
			for ($i = $length - 1;  $i >= 0;  $i --) {
				$Re = $HashStr{$i};
				if (1 === ($Flag % 2)) {
					$Re += $Re;
					$Re = (int)($Re / 10) + ($Re % 10);
				}
				$CheckByte += $Re;
				$Flag ++;
			}

			$CheckByte %= 10;
			if (0 !== $CheckByte) {
				$CheckByte = 10 - $CheckByte;
				if (1 === ($Flag % 2)) {
					if (1 === ($CheckByte % 2))
						$CheckByte += 9;
					$CheckByte >>= 1;
				}
			}

			return '7' . $CheckByte . $HashStr;
		}

		function getGooglePR($url) {
			$remote_url = "http://toolbarqueries.google.com/search?client=navclient-auto&ch=";
			$remote_url .= $this->GooglePRCheckHash($this->GooglePRHashURL($url));
			$remote_url .= "&features=Rank&q=info:" . $url . "&num=100&filter=0";
			$data = wp_remote_retrieve_body(wp_remote_get($remote_url));
			$pos = strpos($data, "Rank_");
			if ($pos === false)
				return -1;
			else
				return intval(substr($data, $pos + 9));
		}

		// Google back links
		function getGoogleBL($url) {
			$apikey = get_option(c_wplr_option_gapi);
			if ($apikey) {
				$remote_url = 'http://ajax.googleapis.com/ajax/services/';
				$remote_url .= 'search/web?v=1.0&filter=0&key=' . $apikey . '&q=link:' . urlencode($url);
				$content = wp_remote_retrieve_body(wp_remote_get($remote_url));
				$data = json_decode($content);
				if ($data &&
					isset($data->responseData) &&
					isset($data->responseData->cursor) &&
					isset($data->responseData->cursor->estimatedResultCount))
					return intval($data->responseData->cursor->estimatedResultCount);
				else
					return -1;
			}
			else
				return -1;
		}

		// Alexa rank
		function getAlexaRank($url) {
			$remote_url = 'http://data.alexa.com/data?cli=10&dat=snbamz&url=' . $url;
			$content = wp_remote_retrieve_body(wp_remote_get($remote_url));
			$xml = simplexml_load_string($content);
			if (isset($xml->SD[1]->POPULARITY['TEXT']))
				return intval($xml->SD[1]->POPULARITY['TEXT']);
			else
				return -1;
		}

		// Yahoo! back links
		function getYahooBL($url) {
			$appid = get_option(c_wplr_option_yapi);
			if ($appid) {
				$remote_url = 'http://search.yahooapis.com/WebSearchService/V1/webSearch?appid=' . $appid . '&query=site:' . $url . '&results=1';
				$content = wp_remote_retrieve_body(wp_remote_get($remote_url));
				$xml = simplexml_load_string($content);
				return intval($xml->attributes()->totalResultsAvailable);
			}
			else
				return -1;
		}

		// Delicious posts
		function GetDelicious($url) {
			$remote_url = 'http://feeds.delicious.com/v2/json/urlinfo/data?url=' . $url;
			$content = wp_remote_retrieve_body(wp_remote_get($remote_url));
			$data = json_decode($content);
			if ($data)
				return intval($data[0]->total_posts);
			else
				return -1;
		}

		// Helper check environment
		function Check_prerequisites() {
			// Check WordPress version
			global $wp_version;
			if (version_compare($wp_version, '3.0') < 0)
				die('List Rank requires at least WordPress 3.0, installed version is ' . $wp_version);

			// Check basic prerequisities
			WPListRank::Check_function('register_activation_hook');
			WPListRank::Check_function('register_deactivation_hook');
			WPListRank::Check_function('add_action');
			WPListRank::Check_function('add_filter');
			WPListRank::Check_function('wp_register_style');
			WPListRank::Check_function('wp_enqueue_style');
			WPListRank::Check_function('wp_register_script');
			WPListRank::Check_function('wp_enqueue_script');
			WPListRank::Check_function('json_decode');
			WPListRank::Check_function('simplexml_load_string');
		}

		function Check_function($name) {
			if (!function_exists($name))
				die('Required function "' . $name . '" does not exist');
		}

		// Helper change file name extension
		function Change_extension($filename, $new_extension) {
			return preg_replace('/\..+$/', $new_extension, $filename);
		}
	}
}

?>
