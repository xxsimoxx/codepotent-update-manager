<?php

/**
 * -----------------------------------------------------------------------------
 * Plugin Name: Update Manager
 * Description: Painlessly push updates to your ClassicPress plugin users! Serve updates from GitHub, your own site, or somewhere in the cloud. 100% integrated with the ClassicPress update process; slim and performant.
 * Version: 2.4.5
 * Requires PHP: 5.6
 * Requires CP: 1.0
 * Author: Simone Fioravanti
 * Author URI: https://software.gieffeedizioni.it
 * Plugin URI: https://software.gieffeedizioni.it
 * Text Domain: codepotent-update-manager
 * Domain Path: /languages
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright 2021, John Alarcon (Code Potent)
 * -----------------------------------------------------------------------------
 * Adopted by Simone Fioravanti, 06/01/2021
 * -----------------------------------------------------------------------------
 */

// Declare the namespace.
namespace CodePotent\UpdateManager;

// Prevent direct access.
if (!defined('ABSPATH')) {
	die();
}

class UpdateManager {

	/**
	 * A slim constructor makes the world a better place. Like, with sparkles.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Hook everything in!
		$this->init();

	}

	/**
	 * Plugin initialization.
	 *
	 * This method loads the constants and functions used by the plugin. It then
	 * hooks the plugin's code into the system and creates the needed objects.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Load constants.
		require_once(plugin_dir_path(__FILE__).'includes/constants.php');

		// Load functions.
		require_once(PATH_INCLUDES.'/functions.php');

		// Load update client.
		require_once(PATH_CLASSES.'/UpdateClient.class.php');

		// Register the autoload method.
		spl_autoload_register(__CLASS__.'::autoload_classes');

		// Register admin menu item.
		add_action('admin_menu', [$this, 'register_admin_menu']);

		// Register privacy page content.
		add_action('admin_init', [$this, 'register_privacy_disclosure']);

		// Enqueue backend scripts.
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Add the plugins query variable.
		add_filter('query_vars', [$this, 'filter_query_vars']);

		// Filter the template to create JSON instead.
		add_filter('template_include', [$this, 'filter_template_include']) ;

		// Filter plugin admin action links.
		add_filter('plugin_action_links_'.PLUGIN_IDENTIFIER, [$this, 'filter_plugin_action_links']);

		// Replace footer text with plugin name and version info.
		add_filter('admin_footer_text', [$this, 'filter_footer_text'], PHP_INT_MAX);

		// Plugin deletion.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall_plugin']);

		// Setup the plugin endpoint post types.
		new PluginEndpoint;

		// Setup the theme endpoint post types.
		new ThemeEndpoint;

		// Setup the transient inspector.
		new TransientInspector;

	}

	/**
	 * Register admin menu.
	 *
	 * This provides a hook for extensions to inject themselves into the menu.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 */
	public function register_admin_menu() {

		// Add primary menu functionality.
		add_menu_page(
			esc_html__('Update Manager', 'codepotent-update-manager'),
			esc_html__('Update Manager', 'codepotent-update-manager'),
			'manage_options',
			'update-manager',
			'',
			'dashicons-update',
			apply_filters(PLUGIN_PREFIX.'_menu_pos', null)
			);

		// Remove the duplicated entry.
		remove_submenu_page('update-manager', 'update-manager');

	}

	/**
	 * Privacy disclosure.
	 *
	 * This method creates a custom section on the core Privacy page to describe
	 * which data is received by the Update Manager plugin and how it used used.
	 * This method does not add anything to the actual Privacy Policy.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function register_privacy_disclosure() {

		// Description of data collected.
		$content = sprintf(
				esc_html__('

			%1$sWhat data is received and how is it used?%2$s

			When a remote plugin queries for information from the Update Manager
			plugin, the following data is sent along with the request:

			%3$s
				%5$sThe plugin identifier (i.e., plugin-folder/plugin-file.php)
					is a unique identifier that ensures you get the correct data.%6$s
				%5$sThe URL making the request is needed for queries when the
					endpoint is in Pending status; for whitelisting.%6$s
				%5$sThe URL to the local assets, if any; these are URLs to the
					banner, icon, and screenshots used in the various views.%6$s
				%5$sThe data from plugin headers provides version information;
					for determining if an update is available.%6$s
			%4$s

			', 'codepotent-update-manager'),
				'<h3>',
				'</h3>',
				'<ol>',
				'</ol>',
				'<li>',
				'</li>'
				);

		// Example text for a Privacy Policy entry.
		$content .= sprintf(
				esc_html__('

			%1$sExample Text for Privacy Policy%2$s

			%3$sNOTE%4$s: %5$sThe following text is not legal advice and should
			not be taken as such. It is merely a suggestion of how one might go
			about disclosing to end users the data that is received and how it
			is used by the Update Manager plugin. If collecting more information
			or storing any data, it would likely require additional disclosures
			to ensure compliance with GDPR.%6$s

			%7$sIn an effort to keep you up to date with latest developments, we
			will periodically make updates available for our products. When your
			site checks in for updates, your URL is sent along with the request.
			This allows us to whitelist certain domains to get updates early,
			allowing for test-runs to make sure there are no issues, before
			rolling out the update to all users. Additionally, if the product
			contains image assets such as a banner, icon, or screenshots, those
			URLs will also be transmitted with the request. These URLs are added
			to the latest data (in memory) and reflected back to your site as a
			data array that the core system can use to populate the various
			related views. This allows images to be served from your own server,
			speeding up the process a bit. Finally, the data from the header
			section of product files is transmitted to help in the determination
			of whether an update is available or not. All data sent withing the
			request is used on a per-request basis and is not stored in any way.
			However, while no transmitted data is stored, requests to a server
			will generally be captured by server access/error logs. Data may
			also be stored in other ways, such as in the logs of a security or
			auditing plugin. The duration of such logged data is subject to the
			policies under which the data was collected and stored and may be
			outside our control.%8$s

			', 'codepotent-update-manager'),
				'<h3>',
				'</h3>',
				'<strong>',
				'</strong>',
				'<em style="color:#f00;">',
				'</em>',
				'<p>',
				'</p>'
				);

		// Paragraph it up.
		$content = wpautop($content, false);

		// Add the content to the system.
		wp_add_privacy_policy_content(PLUGIN_NAME, $content);

	}

	/**
	 * Enqueue admin scripts.
	 *
	 * The method enqueues both scripts and styles on an as-needed basis.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string  $hook_suffix
	 */
	public function enqueue_admin_scripts($hook_suffix) {

		// In a potentioal view for this plugin?
		if (in_array($hook_suffix, ['edit.php', 'post.php', 'post-new.php'])) {

			// Is view related to this plugin? Enqueue the assets.
			if (in_array($post_type = get_post_type(), [CPT_FOR_PLUGIN_ENDPOINTS, CPT_FOR_THEME_ENDPOINTS], true)) {

				// Dealing with a plugin or theme endpoint?
				$component = 'plugin';
				if ($post_type === CPT_FOR_THEME_ENDPOINTS) {
					$component = 'theme';
				}

				// Enqueue assests.
				wp_enqueue_style(PLUGIN_SLUG.'-post-edit-'.$component, URL_STYLES.'/post-edit.css', [], time());
				wp_enqueue_script(PLUGIN_SLUG.'-post-edit-'.$component, URL_SCRIPTS.'/post-edit.js', ['jquery'], time(), true);

			}

		}

	}

	/**
	 * Filter query variables.
	 *
	 * Add the endpoint's public queryable variable to the pile.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars
	 * @return array
	 */
	public function filter_query_vars($vars) {

		// Add the endpoint variable.
		$vars[] = ENDPOINT_VARIABLE;

		// Return the array.
		return $vars;

	}

	/**
	 * Filter template include.
	 *
	 * A filter to replace the template used for requests to this plugin. Rather
	 * than showing it as a post, it's output as JSON.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string $template
	 * @return string
	 */
	public function filter_template_include($template) {

		// Get the query variable.
		$endpoint = get_query_var(ENDPOINT_VARIABLE);

		// Array of permissed endpoint requests.
		$endpoints = [
			'query_plugins',
			'query_themes',
			'plugin_information',
			'theme_information',
		];

		// If the query is for an endpoint, reset template path for JSON.
		if (in_array($endpoint, $endpoints, true)) {
			$template = PATH_SELF.'/endpoints/'.$endpoint.'.php';
		}

		// Return the template path.
		return $template;

	}

	/**
	 * Filter for plugin list table action links.
	 *
	 * Add a link to the plugin admin row. This is for Update Manager's entry in
	 * the admin table, not for plugin update endpoints.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $links
	 * @return array
	 */
	public function filter_plugin_action_links($links) {

		// Add a link to the plugin update manager.
		$links['plugin_endpoint'] = '<a href="'.admin_url('edit.php?post_type='.CPT_FOR_PLUGIN_ENDPOINTS).'">'.esc_html__('Manage Endpoints', 'codepotent-update-manager').'</a>';

		// Return all the things.
		return $links;

	}

	/**
	 * Filter footer text.
	 *
	 * This method changes the left-hand admin footer text with the plugin name,
	 * version, and a Code Potent wordmark.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string $text
	 * @return string
	 */
	public function filter_footer_text($text) {

		// Get current screen.
		$screen = get_current_screen();

		// Change footer text only for this plugin's screens.
		if (strstr($screen->base, PLUGIN_SHORT_SLUG) || $screen->post_type === CPT_FOR_PLUGIN_ENDPOINTS || $screen->post_type === CPT_FOR_THEME_ENDPOINTS) {
			// Contain the footer.
			$text = '<span id="footer-thankyou" style="vertical-align:text-bottom;">';
			// Code Potent info and link.
			$text .= '<a href="'.PLUGIN_AUTHOR_URL.'/" title="'.PLUGIN_DESCRIPTION.'">'.PLUGIN_NAME.'</a> '.PLUGIN_VERSION.' &#8211; by <a href="'.PLUGIN_AUTHOR_URL.'">'.PLUGIN_AUTHOR.'</a>';
			// Allow extension authors to add their credit link to the footer.
			if (!empty($GLOBALS['submenu'][PLUGIN_SHORT_SLUG])) {
				foreach ($GLOBALS['submenu'][PLUGIN_SHORT_SLUG] as $item) {
					if ($screen->base === PLUGIN_SHORT_SLUG.'_page_'.$item[2]) {
						$extension = apply_filters(PLUGIN_PREFIX.'_extension_footer_'.$item[2], '');
						$extension = wp_kses($extension, ['a' => ['href'=>[], 'title'=>[]]]);
						break;
					}
				}
			}

			// If there's an author credit, insert a separator, add the credit.
			if (!empty($extension)) {
				$text .= ' | '.$extension;
			}

			// Close the container.
			$text .= '</span>';

		}

		// Return the footer text.
		return $text;

	}

	/**
	 * Plugin uninstall.
	 *
	 * Cleanup activities for plugin deletion.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public static function uninstall_plugin() {

		// Make sure the plugin's constants are available.
		if (!defined(__NAMESPACE__.'\PLUGIN_VERSION')) {
			require_once('/includes/constants.php');
		}

		// Get ids for all CPT items created by the plugin.
		$posts = get_posts([
				'post_type'      => CPT_FOR_PLUGIN_ENDPOINTS,
				'post_status'    => ['draft', 'pending', 'publish', 'trash'],
				'posts_per_page' => -1,
				'fields'         => 'ids'
		]);

		// Delete posts, metadata, comments, all in one-fell-swoop.
		if (!is_wp_error($posts) && !empty($posts)) {
			foreach ($posts as $post) {
				wp_delete_post($post->ID, true);
			}
		}

		// Delete options set by the plugin.
		delete_option('cp_latest_version');

	}

	/**
	 * Autoload classes.
	 *
	 * A method to autoload any needed classes.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string $class
	 */
	public function autoload_classes($class) {

		// If the class is not inside the plugin's namespace, bail.
		if (!strstr($class, __NAMESPACE__)) {
			return;
		}

		// Replace underscores with backslashes.
		$class = str_replace('_', '\\', $class);

		// Separate qualified class name on backslashes.
		$class_parts = explode('\\', $class);

		// Get the class name.
		$class_name = array_pop($class_parts);

		// If the file exists, require it.
		if (file_exists($class_file = PATH_CLASSES.'/'.$class_name.'.class.php')) {
			require_once $class_file;
			return;
		}

	}

}

// Make run all the things!
new UpdateManager;