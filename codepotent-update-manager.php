<?php

/**
 * -----------------------------------------------------------------------------
 * Plugin Name: Update Manager
 * Description: Painlessly push updates to your ClassicPress plugin users! Serve updates from GitHub, your own site, or somewhere in the cloud. 100% integrated with the ClassicPress update process; slim and performant.
 * Version: 1.0.0-rc1
 * Author: Code Potent
 * Author URI: https://codepotent.com
 * Plugin URI: https://codepotent.com/classicpress/plugins
 * Text Domain: codepotent-update-manager
 * Domain Path: /languages
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright © 2019 - CodePotent
 * -----------------------------------------------------------------------------
 *           ____          _      ____       _             _
 *          / ___|___   __| | ___|  _ \ ___ | |_ ___ _ __ | |_
 *         | |   / _ \ / _` |/ _ \ |_) / _ \| __/ _ \ '_ \| __|
 *         | |__| (_) | (_| |  __/  __/ (_) | ||  __/ | | | |_
 *          \____\___/ \__,_|\___|_|   \___/ \__\___|_| |_|\__|.com
 *
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

		// Register the autoload method.
		spl_autoload_register(__CLASS__.'::autoload_classes');

		// Enqueue backend scripts.
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Add the plugins query variable.
		add_filter('query_vars', [$this, 'filter_query_vars']);

		// Filter the template to create JSON instead.
		add_filter('template_include', [$this, 'filter_template_include']) ;

		// Filter plugin admin action links.
		add_filter('plugin_action_links_'.PLUGIN_IDENTIFIER, [$this, 'filter_plugin_action_links']);

		// Replace footer text with plugin name and version info.
		add_filter('admin_footer_text', [$this, 'filter_footer_text'], 10000);

		// Plugin activation.
		register_activation_hook(__FILE__, [$this, 'activate_plugin']);

		// Plugin deactivation.
		register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);

		// Plugin deletion.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall_plugin']);

		// Run the main plugin code; the CPT.
		new PluginEndpoint;

		// Run the update client.
		UpdateClient::get_instance();

	}

	/**
	 * Enqueue admin scripts.
	 *
	 * The method enqueues both scripts and styles on an as-needed basis. Labels
	 * used in JavaScript functionality are also localized here.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string  $hook_suffix
	 */
	public function enqueue_admin_scripts($hook_suffix) {

		// Assets for CPT-related views.
		if (in_array($hook_suffix, ['post.php', 'post-new.php'])) {
			wp_enqueue_style(PLUGIN_SLUG.'-post-edit', URL_STYLES.'/post-edit.css', [], time());
			wp_enqueue_script(PLUGIN_SLUG.'-post-edit', URL_SCRIPTS.'/post-edit.js', ['jquery'], time());
			wp_localize_script(PLUGIN_SLUG.'-post-edit', 'slug', PLUGIN_SLUG);
			wp_localize_script(PLUGIN_SLUG.'-post-edit', 'confirmation', esc_html__('You are about to completely replace all the text currently in the editor! Is this what you meant to do?', 'codepotent-update-manager'));
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
			'plugin_information',
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
	 * Adds a link to the plugin's admin page.
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
		$links['plugin_endpoint'] = '<a href="'.admin_url('edit.php?post_type='.CPT_FOR_PLUGIN_REPOS).'">'.esc_html__('Manage Endpoints', 'codepotent-update-manager').'</a>';

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

		// Are we on this post type's screen? If so, change the footer text.
		if ($screen->post_type === CPT_FOR_PLUGIN_REPOS) {
			$text = '<span id="footer-thankyou"><a href="'.CODE_POTENT_HOME_URL.'/classicpress/plugins/" title="'.CODE_POTENT_TITLE_ALT.'">'.PLUGIN_NAME.'</a> '.PLUGIN_VERSION.' — A <a href="'.CODE_POTENT_HOME_URL.'" title="'.CODE_POTENT_TITLE_ALT.'"><img src="'.CODE_POTENT_LOGO_SVG_WORDS.'" alt="'.CODE_POTENT_TITLE_ALT.'" style="height:1em;vertical-align:sub;"></a> Production</span>';
		}

		// Return the footer text.
		return $text;

	}

	/**
	 * Plugin activation.
	 *
	 * This method is included for completeness.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function activate_plugin() {

		// Nothing to do here at this time.

	}

	/**
	 * Plugin deactivation.
	 *
	 * This method is included for completeness.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function deactivate_plugin() {

		// Nothing to do here at this time.

	}

	/**
	 * Plugin uninstallation/deletion.
	 *
	 * This method is included for completeness.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public static function uninstall_plugin() {

		// TODO: Add a confirmation dialog before performing the following.

		// Delete CPTs.
		// Delete meta.
		// Delete options.

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