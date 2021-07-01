<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: For inspecting and deleting update transients.
 * Author: John Alarcon
 * Author URI: https://codepotent.com
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright 2021, John Alarcon (Code Potent)
 *           2021, Simone Fioravanti
 * -----------------------------------------------------------------------------
 */

// Declare the namespace.
namespace CodePotent\UpdateManager;

// Prevent direct access.
if (!defined('ABSPATH')) {
	die();
}

class TransientInspector {

	/**
	 * Whitelist of component types.
	 *
	 * @var array
	 */
	public $components = ['plugin', 'theme', 'all'];

	/**
	 * Constructor.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Initialization.
	 *
	 * Hook the code into the system.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function init() {

		// Register the menu item.
		add_action('admin_menu', [$this, 'register_admin_menu'], 20);

		// Enqueue backend scripts.
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Set object properties after post types are registered.
		add_action('registered_post_type', [$this, 'set_user_permissions']);
		add_action('registered_post_type', [$this, 'set_nonce']);
		add_action('registered_post_type', [$this, 'set_action']);
		add_action('registered_post_type', [$this, 'set_component']);

	}

	/**
	 * Enqueue admin scripts.
	 *
	 * The method enqueues both scripts and styles on an as-needed basis. Labels
	 * used in JavaScript functionality are also localized here.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook_suffix
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook_suffix) {

		if ($hook_suffix === PLUGIN_SHORT_SLUG.'_page_'.PLUGIN_SHORT_SLUG.'-transient-inspector') {

			// Enqueue thickbox assets.
			add_thickbox();

			// Enqueue transient inspector assets.
			wp_enqueue_script(PLUGIN_SLUG.'-transient-inspector', URL_SCRIPTS.'/transient-inspector.js', ['jquery'], time());
			wp_enqueue_style(PLUGIN_SLUG.'-transient-inspector', URL_STYLES.'/transient-inspector.css', [], time());

			wp_localize_script(PLUGIN_SLUG.'-transient-inspector', 'plugin_slug', PLUGIN_SLUG);
		}

	}

	/**
	 * Register admin menu item.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_admin_menu() {

		add_submenu_page(
			'update-manager',
			esc_html__('Transient Inspector', 'codepotent-update-manager'),
			esc_html__('Transients', 'codepotent-update-manager'),
			'manage_options',
			PLUGIN_SHORT_SLUG.'-transient-inspector',
			[$this, 'render_transient_inspector']
			);

	}

	/**
	 * Set nonce
	 *
	 * A nonce for use with deletion of update transients.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function set_nonce() {

		$this->nonce = wp_create_nonce(PLUGIN_PREFIX.'_transient_inspector');

	}

	/**
	 * Set action
	 *
	 * Set the type of action that is requested: show or purge.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function set_action() {

		// Default action.
		$this->action = 'show';

		// Reset action, if requested.
		if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'purge') {
			$this->action = 'purge';
		}

	}

	/**
	 * Set component type
	 *
	 * Set the type of component; plugin, theme, or all.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function set_component() {

		// Default value; empty string.
		$this->component = '';

		// If requested component is whitelisted, reset it locally.
		if (!empty($_REQUEST['component'])) {
			if (in_array($_REQUEST['component'], $this->components, true)) {
				$this->component = $_REQUEST['component'];
			}
		}


	}

	/**
	 * Set user permissions
	 *
	 * Set object properties indicating whether the user can manager plugins and
	 * themes.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function set_user_permissions() {

		$this->is_plugin_manager = current_user_can('update_plugins');
		$this->is_theme_manager = current_user_can('update_themes');

	}

	/**
	 * Render transient inspector view
	 *
	 * A method that puts together all the markup into a cohesive display.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function render_transient_inspector() {

		// ClassicPress 101: use the standard wrap class on the output.
		echo '<div id="'.PLUGIN_SLUG.'-container" class="wrap">';

		// Main title.
		echo '<h1>';
		echo sprintf(
			esc_html__('%s &#8211; Transient Inspector', 'codepotent-update-manager'),
			PLUGIN_NAME);
		echo '</h1>';

		// If no permission to view this data, bail.
		if (!$this->is_plugin_manager && !$this->is_theme_manager) {
			echo '<p>';
			echo esc_html__('You do not have sufficient permission to access this data.', 'codepotent-update-manager');
			echo '</p>';
			echo '</div><!--  #'.PLUGIN_SLUG.'-container.wrap -->';
			return;
		}

		// Purge transients, if requested.
		if ($this->action === 'purge') {
			$this->purge_transients();
		}

		// Get current (or fresh) plugin update data, if permissible.
		if ($this->is_plugin_manager) {
			$plugins = get_site_transient('update_plugins');
		}

		// Get current (or fresh) theme update data, if permissible.
		if ($this->is_theme_manager) {
			$themes = get_site_transient('update_themes');
		}

		// Transients menu.
		echo $this->markup_transients_menu();

		// Dump transients.
		echo $this->markup_transient_datadump($plugins, $themes);

		// Container.
		echo '</div><!--  #'.PLUGIN_SLUG.'-container.wrap -->';

	}

	/**
	 * Markup tranients menu
	 *
	 * Tabbed navigation for the Transient Inspector view.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return string HTML markup.
	 */
	private function markup_transients_menu() {

		// URL base for queries.
		$url_base = '/admin.php?page='.PLUGIN_SHORT_SLUG.'-transient-inspector';

		// Container.
		$markup = '<h2 class="nav-tab-wrapper">';

		// Plugin Update Data tab.
		if ($this->is_plugin_manager) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'action=show&component=plugin')) ? ' nav-tab-active' : '';
			$markup .= '<a class="nav-tab'.$current.'" href="'.admin_url($url_base.'&action=show&component=plugin').'">';
			$markup .= esc_html__('Plugin Update Data', 'codepotent-update-manager');
			$markup .= '</a>';
		}

		// Theme Update Data tab.
		if ($this->is_theme_manager) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'action=show&component=theme')) ? ' nav-tab-active' : '';
			$markup .= '<a class="nav-tab'.$current.'" href="'.admin_url($url_base.'&action=show&component=theme').'">';
			$markup .= esc_html__('Theme Update Data', 'codepotent-update-manager');
			$markup .= '</a>';
		}

		// Update Details tab.
		$current = ($_SERVER['QUERY_STRING'] === 'page='.PLUGIN_SHORT_SLUG.'-transient-inspector') ? ' nav-tab-active' : '';
		if (!$current) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'page='.PLUGIN_SHORT_SLUG.'-transient-inspector&action=show&component=all')) ? 'current' : '';
		}
		$markup .= '<a class="nav-tab'.$current.'" href="'.admin_url($url_base).'">';
		$markup .= esc_html__('Update Details', 'codepotent-update-manager');
		$markup .= '</a>';

		// Container.
		$markup .= '</h2>';

		// Return the markup.
		return $markup;

	}

	/**
	 * Markup last checked time/date
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param int $timestamp Unix timestamp
	 *
	 * @return string HTML markup.
	 */
	private function markup_last_checked_timedate($timestamp) {

		// Container.
		$markup = '<p class="'.PLUGIN_SLUG.'-last-checked">';

		// Date and time.
		$markup .= sprintf(
				esc_html__('%sLast checked%s: %s at %s', 'codepotent-update-manager'),
				'<strong>',
				'</strong>',
				date('F j, Y', $timestamp),
				date('h:i:s a', $timestamp)
				);

		// Container.
		$markup .= '</p>';

		// Return markup string.
		return $markup;

	}

	/**
	 * Markup deletion links
	 *
	 * Links for deleting plugin and/or theme update transients.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $plugins Transient object for plugin update data.
	 * @param object $themes Transient object for theme update data.
	 *
	 * @return string HTML markup.
	 */
	private function markup_deletion_links($plugins, $themes) {

		// URL base for queries.
		$url_base = '/admin.php?page='.PLUGIN_SHORT_SLUG.'-transient-inspector';

		// Container.
		$markup = '<p class="'.PLUGIN_SLUG.'-deletion-links">';

		// Title.
		$markup .= '<strong>'.esc_html__('Delete Transients', 'codepotent-update-manager').'</strong>: ';

		// Link to delete plugin update tranient.
		if ($this->is_plugin_manager) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'action=purge&component=plugin')) ? 'current' : '';
			$markup .= '<a class="'.$current.'" href="'.admin_url($url_base.'&action=purge&component=plugin&_wpnonce='.$this->nonce).'">';
			$markup .= esc_html__('Plugins', 'codepotent-update-manager');
			$markup .= '</a>';
		}
		$markup .= ' | ';

		// Link to delete theme update tranient.
		if ($this->is_theme_manager) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'action=purge&component=theme')) ? 'current' : '';
			$markup .= '<a class="'.$current.'" href="'.admin_url($url_base.'&action=purge&component=theme&_wpnonce='.$this->nonce).'">';
			$markup .= esc_html__('Themes', 'codepotent-update-manager');
			$markup .= '</a>';
		}
		$markup .= ' | ';

		// Link to delete both plugin and theme update tranient.
		if ($this->is_plugin_manager && $this->is_theme_manager) {
			$current = (strstr($_SERVER['QUERY_STRING'], 'action=purge&component=all')) ? 'current' : '';
			$markup .= '<a class="'.$current.'" href="'.admin_url($url_base.'&action=purge&component=all&_wpnonce='.$this->nonce).'">';
			$markup .= esc_html__('Both', 'codepotent-update-manager');
			$markup .= '</a>';
		}

		// Container.
		$markup .= '</p>';

		// Return markup string.
		return $markup;

	}

	/**
	 * Markup pending update list
	 *
	 * In the plugin and theme transient data views, a list is shown at the top;
	 * this list contains the names of plugins/themes that have pending updates.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $plugins Transient object for plugin update data.
	 * @param object $themes Transient object for theme update data.
	 *
	 * @return string HTML markup.
	 */
	private function markup_pending_updates_list($plugins, $themes) {

		// Container.
		$markup = '<h2>';

		// Title.
		$markup .= esc_html__('Available Updates: ', 'codepotent-update-manager');

		// Generate links from plugin/theme names, comma-separated.
		$links = '';
		if ($this->component === 'plugin') {
			$links .= '<span class="'.PLUGIN_SLUG.'-update-list">';
			if (!empty($plugins->response)) {
				$file = plugin_dir_path(dirname(dirname(__FILE__)));
				foreach (array_keys($plugins->response) as $identifier) {
					$path = str_replace('\\', '/', $file.$identifier);
					$data = get_plugin_data($path, false, true);
					$links .= $data['Name'].', ';
				}
				$links = rtrim($links, ', ');
			} else {
				$links .= esc_html__('None', 'codepotent-update-manager');
			}
			$links .= '</span>';
		} else if ($this->component === 'theme') {
			$links .= '<span class="'.PLUGIN_SLUG.'-update-list">';
			if (!empty($themes->response)) {
				foreach ($themes->response as $identifier=>$update_data) {
					$data = wp_get_theme($update_data['theme']);
					$links .= $data->Name.', ';
				}
				$links = rtrim($links, ', ');
			} else {
				$links .= esc_html__('None', 'codepotent-update-manager');
			}
			$links .= '</span>';
		}

		// Add links to markup.
		$markup .= $links;

		// Container.
		$markup .= '</h2>';

		// Return the string.
		return $markup;

	}

	/**
	 * Markup transient data
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $plugins Transient object for plugin update data.
	 * @param object $themes Transient object for theme update data.
	 *
	 * @return string HTML markup.
	 */
	private function markup_transient_datadump($plugins, $themes) {

		// Container.
		$markup = '<div class="'.PLUGIN_SLUG.'-transient-datadump">';

		// Markup overview.
		if ((empty($this->action) || empty($this->component)) ||
			($this->action == 'show' && $this->component === 'all')) {
				$markup .= $this->markup_deletion_links($plugins, $themes);
				$markup .= $this->markup_transient_inspector_overview_plugins($plugins);
				$markup .= $this->markup_transient_inspector_overview_themes($themes);
		} else

		// User is requesting plugin data and has sufficient permission?
		if ($this->component === 'plugin') {
			if ($this->is_plugin_manager) {
				if (!empty($plugins->last_checked)) {
					$markup .= $this->markup_last_checked_timedate($plugins->last_checked);
					$markup .= $this->markup_deletion_links($plugins, $themes);
					$markup .= $this->markup_pending_updates_list($plugins, $themes);
					$markup .= $this->debug_transients($plugins);
				}
			}

		} else

		// User is requesting theme data and has sufficient permission?
		if ($this->component === 'theme') {
			if ($this->is_theme_manager) {
				if (!empty($themes->last_checked)) {
					$markup .= $this->markup_last_checked_timedate($themes->last_checked);
					$markup .= $this->markup_deletion_links($plugins, $themes);
					$markup .= $this->markup_pending_updates_list($plugins, $themes);
					$markup .= $this->debug_transients($themes);
				}
			}
		}

		// Container.
		$markup .= '</div><!-- .transient-datadump -->';

		// Info note about where data is stored.
		global $wpdb;
		if (!empty($this->component) && $this->component !== 'all') {
			$markup .= '<p style="text-align:right;"><strong>Note</strong>: '.ucfirst($this->component).' update data is stored as <code>_site_transient_update_'.$this->component.'s</code> in the <code>'.$wpdb->prefix.'options</code> table.</h2>';
		}

		// Return the markup.
		return $markup;

	}

	/**
	 * Markup admin notice
	 *
	 * The notice displayed after deleting update transients.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $plugins Transient object for plugin update data.
	 * @param object $themes Transient object for theme update data.
	 *
	 * @return string HTML markup.
	 */
	private function markup_admin_notice($plugins, $themes) {

		// Containers.
		$markup = '<div class="notice notice-success is-dismissible">';
		$markup .= '<p>';

		// Status message, part 1 of 2.
		if ($plugins && $themes) {
			$markup .= esc_html__('Plugin and theme update transients deleted.', 'codepotent-update-manager');
		} else if ($plugins) {
			$markup .= esc_html__('Plugin update transients deleted.', 'codepotent-update-manager');
		} else if ($themes) {
			$markup .= esc_html__('Theme update transients deleted.', 'codepotent-update-manager');
		}

		// Status message, part 2 of 2.
		$markup .= ' ';
		$markup .= esc_html__('Your next page load will regenerate the transient data; expect a short delay.', 'codepotent-update-manager');

		// Close containers.
		$markup .= '</p>';
		$markup .= '</div>'."\n";

		// Return notice.
		return $markup;

	}

	/**
	 * Markup transient inspector overview
	 *
	 * This beast marks up up the overview using the core list table styles.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $plugins Transient object for plugin update data.
	 * @param object $themes Transient object for theme update data.
	 *
	 * @return string HTML markup.
	 */
	private function markup_transient_inspector_overview_plugins($plugins) {

		$markup = '';
		$plugin_update_count = count($plugins->response);

		// Plugin update table.
		$markup .= '<h2>'.esc_html__('Plugins', 'codepotent-update-manager').' ('.$plugin_update_count.')</h2>';
		$markup .= '<table class="wp-list-table widefat plugins">';
		$markup .= '	<thead>';
		$markup .= '		<tr>';
		$markup .= '			<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" name="SOMENAMEHERE" disabled></td>';
		$markup .= '<th></th>';
		$markup .= '			<th scope="col" id="name" class="manage-column column-name column-primary">'.esc_html__('Plugin', 'codepotent-update-manager').'</th>';
		$markup .= '			<th scope="col" id="description" class="manage-column column-description" style="width:100%;">'.esc_html__('Description', 'codepotent-update-manager').'</th>';
		$markup .= '		</tr>';
		$markup .= '	</thead>';
		$markup .= '	<tbody id="the-plugin-list">';
		if (empty($plugins->response)) {
			$markup .= '<tr>';
			$markup .= '	<th scope="row" class="check-column"><input type="checkbox" disabled style="display: none"></th>';
			$markup .= '	<td colspan="3"><div class="plugin-description"><p>'.esc_html__('All plugins are up to date.', 'codepotent-update-manager').'</p></div></td>';
			$markup .= '</tr>';
		}
		$active_plugins = get_option('active_plugins', []);
		$file = plugin_dir_path(dirname(dirname(__FILE__)));
		foreach ($plugins->response as $identifier=>$plugin) {
			// Initialization.
			$endpoint_view_link = '';
			$endpoint_edit_link = '';
			// Try to get an endpoint for this particular plugin.
			$endpoint = get_posts([
				'post_type' => CPT_FOR_PLUGIN_ENDPOINTS,
				'post_status' => ['pending', 'publish'],
				'posts_per_page' => 1,
				'meta_key' => 'id',
				'meta_value' => esc_attr($identifier),
			]);
			// Got an endpoint? Setup links to view and edit.
			if (!empty($endpoint)) {
				$endpoint_view_link = '<a href="'.site_url().'/?update=plugin_information&plugin='.$identifier.'&site_url='.site_url().'">'.esc_html__('View Endpoint', 'codepotent-update-manager').'</a>';
				$endpoint_edit_link = '<a href="'.admin_url('/post.php?post='.$endpoint[0]->ID.'&action=edit').'">'.esc_html__('Edit Endpoint', 'codepotent-update-manager').'</a>';
			}
			// Determine if plugin is active or inactive; for styling.
			$state = 'inactive';
			if (in_array($identifier, $active_plugins, true)) {
				$state = 'active';
			}
			$icon = '';
			if (!empty($plugin->icons['1x'])) {
				$icon = '<img style="width:48px;" src="'.$plugin->icons['1x'].'">';
			}
			// Get plugin header data.
			$path = str_replace('\\', '/', $file.$identifier);
			$data = get_plugin_data($path, false, true);
			// Markup the row.
			$markup .= '<tr class="'.$state.'" data-slug="'.$plugin->slug.'" data-plugin="'.$identifier.'">';
			$markup .= '	<th scope="row" class="check-column"><input type="checkbox" disabled></th>';
			$markup .= '	<td>'.$icon.'</td>';
			$markup .= '	<td class="plugin-title column-primary" style="white-space:nowrap;">';
			$markup .= 			(($state==='active')?'<strong>':'').$data['Name'].(($state==='active')?'</strong>':'');
			$markup .= '		<div style="color:#555;" class="row-actions visible">'.$data['Version'].' <span style="font-size:20px;">&rarr;</span> '.$plugin->new_version.'</div>';
			$markup .= '	</td>';
			$markup .= '	<td class="column-description desc">';
			$markup .= '		<div class="plugin-description"><p>'.$data['Description'].'</p></div>';
			$markup .= '		<div class="row-actions visible"><p>';
			$markup .= '			<a class="thickbox open-plugin-details-modal '.PLUGIN_SLUG.'-thickbox-plugin" href="#" data-url="'.admin_url('/plugin-install.php?tab=plugin-information&plugin='.$plugin->slug.'&section=changelog').'">'.esc_html__('View Changes', 'codepotent-update-manager').'</a>';
			if ($endpoint_view_link) {
				$markup .= ' | '.$endpoint_view_link;
			}
			if ($endpoint_edit_link) {
				$markup .= ' | '.$endpoint_edit_link;
			}
			$markup .= '		</p></div>';
			$markup .= '	</td>';
			$markup .= '</tr>';
		}

		$markup .= '</tbody>';
		$markup .= '<tfoot>';
		$markup .= '	<tr>';
		$markup .= '		<td scope="col" class="manage-column column-cb check-column"><input type="checkbox" name="SOMENAMEHERE" disabled></td>';
		$markup .= '<td></td>';
		$markup .= '		<th scope="col" class="manage-column column-name column-primary">'.esc_html__('Plugin', 'codepotent-update-manager').'</th>';
		$markup .= '		<th scope="col" class="manage-column column-description" style="width:100%;">'.esc_html__('Description', 'codepotent-update-manager').'</th>';
		$markup .= '	</tr>';
		$markup .= '</tfoot>';
		$markup .= '</table>';

		return $markup;
	}
	
	private function markup_transient_inspector_overview_themes($themes) {

		// Initialization.
		$markup = '';
		$theme_update_count =count($themes->response); 

		// Theme update table.
		$markup .= '<h2>'.esc_html__('Themes', 'codepotent-update-manager').' ('.$theme_update_count.')</h2>';
		$markup .= '<table class="wp-list-table widefat plugins">'; // "plugins", to leverage the styling
		$markup .= '	<thead>';
		$markup .= '		<tr>';
		$markup .= '			<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" name="SOMENAMEHERE" disabled></td>';
		$markup .= '<td></td>';
		$markup .= '			<th scope="col" id="name" class="manage-column column-name column-primary">'.esc_html__('Theme', 'codepotent-update-manager').'</th>';
		$markup .= '			<th scope="col" id="description" class="manage-column column-description" style="width:100%;">'.esc_html__('Description', 'codepotent-update-manager').'</th>';
		$markup .= '		</tr>';
		$markup .= '	</thead>';
		$markup .= '	<tbody id="the-theme-list">';
		// Get current theme slug.
		$current_theme = get_option('stylesheet');
		if (empty($themes->response)) {
			$markup .= '<tr>';
			$markup .= '	<th scope="row" class="check-column"><input type="checkbox" disabled style="display: none"></th>';
			$markup .= '	<td colspan="3"><div class="theme-description"><p>'.esc_html__('All themes are up to date.', 'codepotent-update-manager').'</p></div></td>';
			$markup .= '</tr>';
		}
		foreach ($themes->response as $identifier=>$theme) {
			// Initialization.
			$endpoint_view_link = '';
			$endpoint_edit_link = '';
			// Try to get an endpoint for this particular plugin.
			$endpoint = get_posts([
				'post_type' => CPT_FOR_THEME_ENDPOINTS,
				'post_status' => ['pending', 'publish'],
				'posts_per_page' => 1,
				'meta_key' => 'id',
				'meta_value' => esc_attr($identifier),
			]);
			// Got an endpoint? Setup links to view and edit.
			if (!empty($endpoint)) {
				$endpoint_view_link = '<a href="'.site_url().'/?update=theme_information&theme='.$identifier.'&site_url='.site_url().'">'.esc_html__('View Endpoint', 'codepotent-update-manager').'</a>';
				$endpoint_edit_link = '<a href="'.admin_url('/post.php?post='.$endpoint[0]->ID.'&action=edit').'">'.esc_html__('Edit Endpoint', 'codepotent-update-manager').'</a>';
			}
			$state = 'inactive';
			if ($identifier === $current_theme) {
				$state = 'active';
			}
			$screenshot = '';
			if (file_exists(WP_CONTENT_DIR.'/themes/'.$theme['theme'].'/screenshot.png')) {
				$screenshot = '<img src="'.WP_CONTENT_URL.'/themes/'.$theme['theme'].'/screenshot.png" style="max-width:48px;">';
			} else if (file_exists(WP_CONTENT_DIR.'/themes/'.$theme['theme'].'/screenshot.jpg')) {
				$screenshot = '<img src="'.WP_CONTENT_URL.'/themes/'.$theme['theme'].'/screenshot.jpg" style="max-width:48px;">';
			}
			$data = wp_get_theme($theme['theme']);
			$markup .= '<tr class="'.$state.'" data-slug="'.$identifier.'" data-theme="'.$identifier.'">';
			$markup .= '	<th scope="row" class="check-column"><input type="checkbox" disabled></th>';
			$markup .= '	<td>'.$screenshot.'</td>';
			$markup .= '	<td class="theme-title column-primary" style="white-space:nowrap;">';
			$markup .= (($state==='active')?'<strong>':'').$data['Name'].(($state==='active')?'</strong>':'');
			$markup .= '		<div style="color:#555;" class="row-actions visible"><p>';
			$markup .= 				$data['Version'].' <span style="font-size:20px;">&rarr;</span> '.$theme['new_version'];
			$markup .= '</p></div>';
			$markup .= '	</td>';
			$markup .= '	<td class="column-description desc">';
			$markup .= '		<div class="theme-description"><p>'.$data['Description'].'</p></div>';
			$markup .= '		<div class="row-actions visible"><p>';
			$markup .= '			<a class="thickbox open-plugin-details-modal '.PLUGIN_SLUG.'-thickbox-theme" href="#" data-theme="'.$identifier.'">'.esc_html__('Preview', 'codepotent-update-manager').'</a>';
			if ($endpoint_view_link) {
				$markup .= ' | '.$endpoint_view_link;
			}
			if ($endpoint_edit_link) {
				$markup .= ' | '.$endpoint_edit_link;
			}
			$markup .= '		</p></div>';
			$markup .= '	</td>';
			$markup .= '</tr>';
		}


		$markup .= '</tbody>';
		$markup .= '<tfoot>';
		$markup .= '	<tr>';
		$markup .= '		<td scope="col" class="manage-column column-cb check-column"><input type="checkbox" name="SOMENAMEHERE" disabled></td>';
		$markup .= '<td></td>';
		$markup .= '		<th scope="col" class="manage-column column-name column-primary">'.esc_html__('Theme', 'codepotent-update-manager').'</th>';
		$markup .= '		<th scope="col" class="manage-column column-description" style="width:100%;">'.esc_html__('Description', 'codepotent-update-manager').'</th>';
		$markup .= '	</tr>';
		$markup .= '</tfoot>';
		$markup .= '</table>';

	
	
		// Were there theme updates? If so, markup their previews; keep hidden.
		if (!empty($themes->response)) {
			// Get current (active) theme identifier.
			$stylesheet = get_option('stylesheet');
			// Iterate over update data.
			foreach ($themes->response as $identifier=>$theme) {
				// Get the current (iteration's) theme object.
				$this_theme = wp_get_theme($identifier);
				// Get the Update Manager endpoint related to this theme.
				$endpoint = get_posts([
					'post_type' => CPT_FOR_THEME_ENDPOINTS,
					'post_status' => ['pending', 'publish'],
					'posts_per_page' => 1,
					'meta_key' => 'id',
					'meta_value' => esc_attr($identifier),
				]);
				// Determine the description.
				$description = $this_theme->get('Description');
				if (!empty($endpoint[0])) {
					$content = get_post_meta($endpoint[0]->ID, $identifier, true);
					$content = str_replace('#', '##', $content);
					$lines = explode("\n", $content);
					$s = get_sections_data($lines);
					if (!empty($s['description'])) {
						$description = markup_generic_section($s['description']);
						$description = str_replace('<p>', '<p class="theme-description">', $description);
					}
					$header = get_header_data($lines);
				}
				// Determine the screenshot image.
				$image = '';
				if (file_exists(WP_CONTENT_DIR.'/themes/'.$identifier.'/screenshot.png')) {
					$image = '<img src="'.WP_CONTENT_URL.'/themes/'.$identifier.'/screenshot.png" alt="">';
				} else if (file_exists(WP_CONTENT_DIR.'/themes/'.$identifier.'/screenshot.jpg')) {
					$image = '<img src="'.WP_CONTENT_URL.'/themes/'.$identifier.'/screenshot.jpg" alt="">';
				}
				// Determine the tags.
				$tags = '';
				if (!empty($header['tags'])) {
					$tags = $header['tags'];
				} else if (!empty($this_theme->get('Tags'))) {
					$tags = implode(', ', $this_theme->get('Tags'));
				}
				// Markup the theme similar to core's display.
				$markup .= '<div class="theme-overlay" id="display-theme-'.$identifier.'" style="display:none;">';
				$markup .= '	<div class="theme-overlay active">';
				$markup .= '		<div class="theme-wrap wp-clearfix">';
				$markup .= '			<div class="theme-about wp-clearfix">';
				$markup .= '				<div class="theme-screenshots">';
				$markup .= '					<div class="screenshot">'.$image.'</div>';
				$markup .= '				</div>';
				$markup .= '				<div class="theme-info">';
				if ($identifier === $stylesheet) {
					$markup .= '					<span class="current-label">'.esc_html__('Current Theme', 'codepotent-update-manager').'</span>';
				}
				$markup .= '					<h2 class="theme-name">'.$this_theme->get('Name').'<span class="theme-version">Version: '.$this_theme->get('Version').'</span></h2>';
				$markup .= '					<p class="theme-author">By <a href="'.$this_theme->get('AuthorURI').'">'.$this_theme->get('Author').'</a></p>';
				$markup .= '					<div class="theme-description">'.$description.'</div>';
				$markup .= '					<p class="theme-tags"><span>Tags:</span> '.$tags.'</p>';
				$markup .= '				</div><!-- .theme-info -->';
				$markup .= '			</div><!-- .theme-about -->';
				$markup .= '		</div><!-- .theme-wrap -->';
				$markup .= '	</div><!-- .theme-overlay.active-->';
				$markup .= '</div><!-- .theme-overlay #display-theme-'.$identifier.' -->';

			} // foreach ($themes->response as $identifier=>$theme) {

		} // if (!empty($themes->response)) {

		// Return the markup.
		return $markup;

	}

	/**
	 * Debug transients
	 *
	 * A method to convert transient data into readable format.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param $var object Transient data for plugin or theme updates.
	 *
	 * @return string HTML markup.
	 */
	private function debug_transients($var) {

		$markup = '<pre class="'.PLUGIN_SLUG.'-debug-panel">';
		$markup .= print_r($var->response, true);
		$markup .= '</pre>';
		return $markup;

	}

	/**
	 * Purge transients
	 *
	 * A method to purge the relevant update transient data.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function purge_transients() {

		// No permission? Bail.
		if (!$this->is_plugin_manager && !$this->is_theme_manager) {
			return;
		}

		// Nonce no good? Bail.
		if (!wp_verify_nonce($this->nonce, PLUGIN_PREFIX.'_transient_inspector')) {
			return;
		}

		// Flags to indicate what was purged.
		$plugins_purged = $themes_purged = false;

		// Purge plugins transient?
		if ($this->component === 'plugin' || $this->component === 'all') {
			if ($this->is_plugin_manager) {
				delete_site_transient('update_plugins');
				$plugins_purged = true;
			}
		}

		// Purge themes transient?
		if ($this->component === 'theme' || $this->component === 'all') {
			if ($this->is_theme_manager) {
				delete_site_transient('update_themes');
				$themes_purged = true;
			}
		}

		// Display admin notice.
		if ($plugins_purged || $themes_purged) {
			echo $this->markup_admin_notice($plugins_purged, $themes_purged);
		}

	}

}