<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: CPT for endpoint entries with meta boxes and custom columns.
 * Package: CodePotent\UpdateManager
 * Author: John Alarcon
 * Author URI: https://codepotent.com
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright 2021, John Alarcon (Code Potent)
 * -----------------------------------------------------------------------------
 */

// Declare the namespace.
namespace CodePotent\UpdateManager;

// Prevent direct access.
if (!defined('ABSPATH')) {
	die();
}

class PluginEndpoint {

	/**
	 * Constructor.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function init() {

		// Register custom post type for updates.
		add_action('init', [$this, 'register_custom_post_type']);

		// Filter CPT title placeholder.
		add_filter('enter_title_here', [$this, 'filter_cpt_title_placeholder'], 10, 2);

		// Filter links in the admin list table for this CPT.
		add_filter('post_row_actions', [$this, 'filter_post_row_actions'], 10, 2);

		// Register metabox.
		add_action('add_meta_boxes', [$this, 'register_meta_box_primary']);

		// Register metabox.
		add_action('add_meta_boxes', [$this, 'register_meta_box_autocompleters']);

		// Update metaboxes.
		add_action('save_post', [$this, 'update_meta_box_primary'], 10, 2);

		// Add custom columns.
		add_filter('manage_'.CPT_FOR_PLUGIN_ENDPOINTS.'_posts_columns', [$this, 'filter_columns']);

		// Declare which custom columns are sortable.
		add_filter('manage_edit-'.CPT_FOR_PLUGIN_ENDPOINTS.'_sortable_columns', [$this, 'filter_columns_sortable']);

		// Make sortable the things.
		add_action('pre_get_posts', [$this, 'filter_columns_sort_field']);

		// Populate custom columns.
		add_action('manage_'.CPT_FOR_PLUGIN_ENDPOINTS.'_posts_custom_column', [$this, 'filter_columns_content'], 10, 2);

	}

	/**
	 * Register custom post type.
	 *
	 * This post type is for the update endpoints.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function register_custom_post_type() {

		// Labels for the post type.
		$labels = [
			'name'                => esc_html__('Update Manager &#8211; Plugins', 'codepotent-update-manager'),
			'singular_name'       => esc_html__('Plugin Endpoint',                'codepotent-update-manager'),
			'add_new'             => esc_html__('New Plugin',                     'codepotent-update-manager'),
			'add_new_item'        => esc_html__('Add New Plugin',                 'codepotent-update-manager'),
			'edit_item'           => esc_html__('Edit Plugin',                    'codepotent-update-manager'),
			'new_item'            => esc_html__('New Plugin',                     'codepotent-update-manager'),
			'all_items'           => esc_html__('Plugins',                        'codepotent-update-manager'),
			'view_item'           => esc_html__('View Plugin',                    'codepotent-update-manager'),
			'search_items'        => esc_html__('Search Plugins',                 'codepotent-update-manager'),
			'not_found'           => esc_html__('No Plugins found',               'codepotent-update-manager'),
			'not_found_in_trash'  => esc_html__('No Plugins found in Trash',      'codepotent-update-manager'),
			'menu_name'           => esc_html__('Update Manager',                 'codepotent-update-manager'),
		];

		// Arguments for the post type.
		$args = [
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => 'update-manager',
			'rewrite'       => false,
			'supports'      => ['title'],
			'labels'        => $labels,
		];

		// Last call!
		$args = apply_filters(CPT_FOR_PLUGIN_ENDPOINTS.'_post_type_args', $args);

		// Register the post type.
		register_post_type(CPT_FOR_PLUGIN_ENDPOINTS, $args);

	}

	/**
	 * Filter title placeholder.
	 *
	 * This method filters the placeholder text used for the title input on this
	 * custom post type's edit screen.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string $placeholder
	 * @param object $post
	 * @return string
	 */
	public function filter_cpt_title_placeholder($placeholder, $post) {

		// Dealing with this CPT? Swap the text!
		if (get_post_type($post) === CPT_FOR_PLUGIN_ENDPOINTS) {
			$placeholder = esc_html__('Plugin Name or Title', 'codepotent-update-manager');
		}

		// Return the possibly updated text.
		return $placeholder;

	}

	/**
	 * Filter post row actions.
	 *
	 * This method add links to the custom post type's list table row. Here, the
	 * only link addded is one that points to the endpoint for viewing.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions
	 * @param object $post
	 * @return string
	 */
	public function filter_post_row_actions($actions, $post) {

		// Dealing with this CPT? Add links!
		if (get_post_type() === CPT_FOR_PLUGIN_ENDPOINTS) {
			if ($post->post_status === 'publish' || $post->post_status === 'pending') {
				$identifier = get_post_meta($post->ID, 'id', true);
				$actions['endpoint'] = '<a href="'.site_url().'?'.ENDPOINT_VARIABLE.'=plugin_information&plugin='.esc_attr($identifier).'&site_url='.site_url().'">'.esc_html__('View Endpoint', 'codepotent-update-manager').'</a>';
			}
		}

		// Return the possibly updated links.
		return $actions;

	}

	/**
	 * This method registers the primary metabox which contains the input fields
	 * for the identifier, editor, test URLs and notification methods.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function register_meta_box_primary() {

		// Add metabox to primary area.
		add_meta_box(
			PLUGIN_SLUG.'-primary-editor',
			esc_html__('Endpoint Details', 'codepotent-update-manager'),
			[$this, 'render_meta_box_primary'],
			[CPT_FOR_PLUGIN_ENDPOINTS],
			'normal',
			'high'
			);

	}

	/**
	 * Default endpoint content
	 *
	 * This method returns the content that populates the editor when a new post
	 * (plugin or theme endpoint) is created.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Type of component; plugin or theme.
	 * @param string $template Type of content; fillable, minimum, or maximum.
	 * @return string Default content to populate editor.
	 */
	public function get_default_endpoint_content() {

		$content = esc_html__('=== Plugin Name Here ===', 'codepotent-update-manager')."\n\n";
		$content .= esc_html__('Version:           1.0.0', 'codepotent-update-manager')."\n";
		$content .= esc_html__('Requires:          1.0.0', 'codepotent-update-manager')."\n";
		$content .= esc_html__('Download link:     https://', 'codepotent-update-manager')."\n\n";
		$content .= esc_html__('== Description ==', 'codepotent-update-manager')."\n\n";
		$content .= esc_html__('This text displays in the modal windows; it is required. Write something!', 'codepotent-update-manager')."\n\n";

		return $content;

	}

	/**
	 * Render primary metabox.
	 *
	 * This method renders the output of the primary metabox, adds a nonce, etc.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param object $post
	 */
	public function render_meta_box_primary($post) {

		// Get the identifier.
		$identifier = get_post_meta($post->ID, 'id', true);

		// Initialization.
		$content = '';
		if (!empty($identifier)) {
			$content = get_post_meta($post->ID, $identifier, true);
		}

		// Default content for new items.
		if (empty($post->post_title) && empty($content)) {
			$content = $this->get_default_endpoint_content();
		}

		// Get test URLs, if any.
		$test_urls = get_post_meta($post->ID, 'test_urls', true);

		// Get notification email or URL.
		$notifications = get_post_meta($post->ID, 'notifications', true);

		// Open table.
		echo '<table class="form-table">'."\n";
		echo '	<tbody>'."\n";

		// Identifier.
		echo '		<tr>'."\n";
		echo '			<th scope="row"><label for="'.esc_html(PLUGIN_SLUG).'-identifier">'.esc_html__('Endpoint Identifier', 'codepotent-update-manager').'</label></th>'."\n";
		echo '			<td>'."\n";
		echo '				<p>'."\n";
		echo '					<input class="widefat" name="'.esc_html(PLUGIN_PREFIX).'_plugin_id" type="text" id="'.esc_html(PLUGIN_SLUG).'-identifier" value="'.esc_attr($identifier).'" placeholder="'.esc_attr('plugin-folder/plugin-file.php').'">'."\n";
		echo '				</p>'."\n";
		echo '				<p class="description">';
		echo sprintf(
				esc_html__('Use the form %sfolder-name/file-name.php%s to match that of your plugin.', 'codepotent-update-manager'),
					'<code>',
					'</code>');
		echo '</p>'."\n";
		echo '			</td>'."\n";
		echo '		</tr>'."\n";

		// Text editor.
		echo '		<tr>'."\n";
		echo '			<th scope="row"><label for="'.esc_html(PLUGIN_SLUG).'-editor">'.esc_html__('Plugin Details', 'codepotent-update-manager').'</label></th>'."\n";
		echo '			<td>'."\n";
		echo '				<p>';
		echo '					<textarea class="widefat" rows="20" name="'.esc_html(PLUGIN_PREFIX).'_editor" id="'.esc_html(PLUGIN_SLUG).'-editor">'.esc_textarea($content).'</textarea>';
		echo '				</p>'."\n";
		echo '				<p class="description">';
		echo sprintf(
			esc_html__('Describe the plugin as if writing a %sreadme.txt%s file for it. Basic %smarkdown%s is supported. HTML and PHP tags are stripped.', 'codepotent-update-manager'),
				'<a href="https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/">',
				'</a>',
				'<a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">',
			'</a>'
			);
		echo '				</p>'."\n";
		echo '			</td>'."\n";
		echo '		</tr>'."\n";

		// Testable URLs.
		echo '		<tr>'."\n";
		echo '			<th scope="row"><label for="'.esc_html(PLUGIN_SLUG).'-test-urls">'.esc_html__('Testing Domains', 'codepotent-update-manager').'</label></th>'."\n";
		echo '			<td>'."\n";
		echo '				<p>';
		echo '					<textarea class="widefat" rows="5" name="'.esc_html(PLUGIN_PREFIX).'_test_urls" id="'.esc_html(PLUGIN_SLUG).'-test-urls">'.esc_textarea($test_urls).'</textarea>';
		echo '				</p>'."\n";
		echo '				<p class="description">';
		echo sprintf(
				esc_html__('Only the domain(s) listed here will receive updates when the endpoint is in %1$sPending%2$s status. This is for testing updates on a limited basis before making them widely available. One per line.', 'codepotent-update-manager'),
				'<code>',
				'</code>');
		echo '&nbsp;';
		echo sprintf(
				esc_html__('URLs must point to the root of a ClassicPress installation. A couple examples might be: %1$shttps://www.yoursite.com%2$s or %1$shttps://www.yoursite.com/your/path/to/classicpress%2$s. The plugin related to this endpoint must also be installed and active there.', 'codepotent-update-manager'),
				'<code>',
				'</code>');
		echo '</p>'."\n";
		echo '			</td>'."\n";
		echo '		</tr>'."\n";

		// Notifications.
		echo '		<tr>'."\n";
		echo '			<th scope="row"><label for="'.esc_html(PLUGIN_SLUG).'-notifications">'.esc_html__('Notifications', 'codepotent-update-manager').'</label></th>'."\n";
		echo '			<td>'."\n";
		echo '				<p>'."\n";
		echo '					<input class="widefat" name="'.esc_html(PLUGIN_PREFIX).'_notifications" type="text" id="'.esc_html(PLUGIN_SLUG).'-notifications" value="'.esc_attr($notifications).'" placeholder="'.esc_attr('Ex: sue@mail.com, joe@mail.com, https://site.com/contact').'">'."\n";
		echo '				</p>'."\n";
		echo '				<p class="description">';
		echo sprintf(
			esc_html__('In case of issues, give your testers the means to make contact. You can add multiple email addresses and a URL, separated by commas. This creates a link that prepopulates an email and/or a link to a specific page for reporting issues.', 'codepotent-update-manager'),
				'<code>',
				'</code>');
		echo '</p>'."\n";
		echo '			</td>'."\n";
		echo '		</tr>'."\n";

		// Cheat sheet.
		echo '		<tr style="border-top:1px solid #ccc;">';
		echo '			<th scope="row"><label><a href="#" id="'.esc_html(PLUGIN_SLUG).'-toggle-cheat-sheet">'.esc_html__('Need a cheat sheet?', 'codepotent-update-manager').'</a></label></th>'."\n";
		/*
		markup_header_data_legend output a cheatsheet that contains a lot of hardcoded HTML, that is already escaped
		in the function when needed, so wp_kses is unuseful here. 
		*/
		echo '			<td>'.markup_header_data_legend('plugin').'</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '		</tr>';

		// Close table.
		echo '	</tbody>'."\n";
		echo '</table>'."\n";

		// Add a nonce.
		wp_nonce_field(PLUGIN_PREFIX.'_metabox_nonce', PLUGIN_PREFIX.'_metabox_nonce');

	}

	/**
	 * Update primary metabox.
	 *
	 * This method handles updating the data collected from the primary metabox.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id
	 * @param object $post
	 * @return integer The post id.
	 */
	public function update_meta_box_primary($post_id, $post) {
	
		// No nonce present? Bail.
		if (!isset($_REQUEST[PLUGIN_PREFIX.'_metabox_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_REQUEST[PLUGIN_PREFIX.'_metabox_nonce'])), PLUGIN_PREFIX.'_metabox_nonce')) {
			return $post_id;
		}

		// Remove any PHP tags prior to stripping data to prevent corruption. 
		if (!empty($_POST[PLUGIN_PREFIX.'_editor'])) {
			$_POST[PLUGIN_PREFIX.'_editor'] = str_replace(['<?','? >'], ['&lt;?','?&gt;',], wp_unslash($_POST[PLUGIN_PREFIX.'_editor'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		// Strip slashes from input. strip_tags_deep uses wp_strip_all_tags so it is sanitized.
		$request = strip_tags_deep(stripslashes_deep($_POST));

		// No expected data submitted? Bail.
		if (empty($request[PLUGIN_PREFIX.'_plugin_id'])) {
			return $post_id;
		}

		// Doing autosave? Bail.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		// Not a post of this type? Bail.
		if ($post->post_type !== CPT_FOR_PLUGIN_ENDPOINTS) {
			return $post_id;
		}

		// Get the identifier.
		$identifier = get_post_meta($post->ID, 'id', true);

		// User has no permission? Bail.
		if (!current_user_can('update_plugins', $post_id)) {
			return $post_id;
		}

		// Identifier.
		$new_identifier = isset($request[PLUGIN_PREFIX.'_plugin_id']) ? sanitize_text_field($request[PLUGIN_PREFIX.'_plugin_id']) : '';

		// Ensure data stays in sync if identifier changes.
		if ($new_identifier !== $identifier) {
			// Update the identifier's meta entry.
			update_post_meta($post_id, 'id', $new_identifier);
			// Update the primary content's meta entry to stay in sync.
			$meta = get_post_meta($post_id, $identifier, true);
			update_post_meta($post_id, $new_identifier, $meta);
			delete_post_meta($post_id, $identifier, $meta);
		}

		// Update content.
		if (isset($request[PLUGIN_PREFIX.'_editor'])) {
			// A quick dip in Lysol.
			$content = sanitize_textarea_field($request[PLUGIN_PREFIX.'_editor']);
			// Update the record.
			update_post_meta($post_id, $new_identifier, $content);
		}

		// Update test URLs.
		if (isset($request[PLUGIN_PREFIX.'_test_urls'])) {
			// Sanitize the data.
			$urls = sanitize_textarea_field($request[PLUGIN_PREFIX.'_test_urls']);
			// Ensure lines are trimmed before storage.
			$data = explode("\n", $urls);
			$trimmed_urls = [];
			foreach ($data as $line) {
				$trimmed_line = trim($line, "/ \r\n\t");
				if (!empty($trimmed_line)) {
					$trimmed_urls[] = str_replace(' ', '', $trimmed_line);
				}
			}
			$urls = implode("\n", $trimmed_urls);
			// Update the record.
			update_post_meta($post_id, 'test_urls', $urls);
		}

		// Notification targets.
		$old_targets = get_post_meta($post_id, 'notifications', true);
		$new_targets = isset($request[PLUGIN_PREFIX.'_notifications']) ? sanitize_text_field($request[PLUGIN_PREFIX.'_notifications']) : '';
		if ($new_targets !== $old_targets) {
			update_post_meta($post_id, 'notifications', $new_targets);
		}

		// In all cases.
		return $post_id;

	}

	/**
	 * Register auto-completers metabox.
	 *
	 * This method registers the autocompleters metabox.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function register_meta_box_autocompleters() {

		// Add sidebar metabox.
		add_meta_box(
			PLUGIN_SLUG.'-autocompleters',
			esc_html__('Auto Complete', 'codepotent-update-manager'),
			[$this, 'render_meta_box_autocompleters'],
			[CPT_FOR_PLUGIN_ENDPOINTS],
			'side',
			'default'
			);

	}

	/**
	 * Render auto-completers metabox.
	 *
	 * This method renders the output of the auto-completers metabox. Notable is
	 * that there is no need for an update method (or the accompanying filter to
	 * hook it in); this metabox is purely for browser-based use.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function render_meta_box_autocompleters() {

		// Explanatory text.
		echo '<p>';
		echo sprintf(
			esc_html__('%sWork smarter!%s Click to auto-fill the editor with a premade template you can fill out.', 'codepotent-update-manager'),
			'<strong>',
			'</strong>');
		echo '</p>';

		// CTA button.
		echo '<p><button type="button" class="button button-secondary button-hero widefat without-examples" data-component="plugin">';
		echo esc_html__('Insert Template', 'codepotent-update-manager');
		echo '</button></p>';

		// Additional options.
		echo '<p>';
		echo sprintf(
			esc_html__('You can insert a %sfully completed example%s to get your bearings, or even just see the %sabsolute minimum requirements%s.', 'codepotent-update-manager'),
			'<a href="#" class="with-examples" data-component="plugin">',
			'</a>',
			'<a href="#" class="reqs-only" data-component="plugin">',
			'</a>');
		echo '</p>';

	}

	/**
	 * Filter columns.
	 *
	 * This method filters in custom columns for use with the custom post type's
	 * admin list table.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns
	 * @return array
	 */
	public function filter_columns($columns) {

		// No unsets; just redo the columns and return.
		return [
			'cb'            => '<input type="checkbox">',
			'title'         => esc_html__('Plugin', 'codepotent-update-manager'),
			'version'       => esc_html__('Version', 'codepotent-update-manager'),
			'download'      => '<span class="dashicons dashicons-download" style="display:inline;margin:10px 0 0;color:#72777c;opacity:.65;"></span>',
			'identifier'    => esc_html__('Identifier', 'codepotent-update-manager'),
			'test_urls'     => esc_html__('Test URLs', 'codepotent-update-manager'),
			'notifications' => esc_html__('Notifications', 'codepotent-update-manager'),
			'modified'      => esc_html__('Updated', 'codepotent-update-manager'),
		];

	}

	/**
	 * Filter custom sort field
	 *
	 * This method handles sorting for filtered columns that don't map to a post
	 * type field.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @param object $wp_query
	 */
	public function filter_columns_sort_field($wp_query) {

		// Dealing with this post type? Reset the query.
		if ($wp_query->get('post_type') === CPT_FOR_PLUGIN_ENDPOINTS) {
			if ($wp_query->get('orderby') === 'identifier') {
				$wp_query->set('orderby', 'meta_value');
				$wp_query->set('meta_key', 'id');
			}
		}

	}

	/**
	 * Declare sortable columns
	 *
	 * This method desclares which custom columns are sortable.
	 *
	 * @author John Alarcon
	 *
	 * @since 2.0.0
	 *
	 * @return array Map of sortable columns.
	 */
	public function filter_columns_sortable() {

		return [
			'identifier' => 'identifier',
			'title'      => 'title',
			'modified'   => 'modified'
		];

	}

	/**
	 * Populate custom columns.
	 *
	 * This method populates the various custom columns registered by the custom
	 * post type.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param string $column
	 * @param integer $post_id
	 */
	public function filter_columns_content($column, $post_id) {

		// Bring current post into scope.
		global $post;

		// Get meta data for given post id.
		$meta = get_post_meta($post_id);

		// Content for version column.
		if ($column === 'version') {
			if (!empty($meta['id'])) {
				$lines = explode("\n", $meta[$meta['id'][0]][0]);
				$plugin = get_header_data($lines);
				echo !empty($plugin['version']) ? esc_attr($plugin['version']) : '&#8211;';
			} else {
				echo '&#8211;';
			}
		}

		// Content for download column.
		if ($column === 'download') {
			if (!empty($meta['id'])) {
				$lines = explode("\n", $meta[$meta['id'][0]][0]);
				$header = get_header_data($lines);
			}
			$style = 'opacity:.3;';
			$title = esc_html__('No Download Available', 'codepotent-update-manager');
			if (!empty($header['download_link'])) {
				echo '<a href="'.esc_url($header['download_link']).'">';
				$style = '';
				$title = esc_html__('Download', 'codepotent-update-manager');
			}
			echo '<span style="'.esc_html($style).'" title="'.esc_html($title).'" class="dashicons dashicons-download"></span>';
			if (!empty($header['download_link'])) {
				echo '</a>';
			}
		}

		// Content for identifier column.
		if ($column === 'identifier') {
			echo !empty($meta['id'][0]) ? esc_attr($meta['id'][0]) : '&#8211;';
		}

		// Content for test URLs column.
		if ($column === 'test_urls') {
			$test_urls = get_allowed_test_urls($post_id);
			if (!empty($test_urls[0])) {
				foreach ($test_urls as $test_url) {
					$truncated_url = (strlen($test_url)<=30) ? $test_url : substr($test_url, 0, 27).'...';
					echo '<a href="'.esc_url_raw($test_url).'" title="'.esc_url_raw($test_url).'">'.esc_url_raw($truncated_url).'</a><br>';
				}
			} else {
				echo '&#8211;';
			}
		}

		// Content for notifications column.
		if ($column === 'notifications') {
			// Get notification targe emails/urls.
			$targets = get_notification_targets($post_id);
			// No notification targets? Bail.
			if (empty($targets['email']) && empty($targets['url'])) {
				echo '&#8211;';
				return;
			}
			// Notification email addresses present? Print links.
			if (!empty($targets['email']) && !empty($meta['id'])) {
				$lines = explode("\n", $meta[$meta['id'][0]][0]);
				$header = get_header_data($lines);
				$subject = get_notification_email_subject($header);
				$body = get_notification_email_body($header);
				foreach ($targets['email'] as $email) {
					$email_url = get_notification_email_url($email, $subject, $body);
					$truncated_email = (strlen($email)<=20) ? $email : substr($email, 0, 17).'...';
					echo '<span class="dashicons dashicons-email"></span> ';
					echo '<a href="'.esc_url($email_url).'">'.esc_html($truncated_email).'</a>';
					echo '<br>';
				}
			}
			// Notification URLs present? Print links.
			if (!empty($targets['url'])) {
				foreach ($targets['url'] as $test_url) {
					$truncated_url = (strlen($test_url)<=20) ? $test_url : substr($test_url, 0, 17).'...';
					echo '<span class="dashicons dashicons-admin-site"></span> ';
					echo '<a href="'.esc_url_raw($test_url).'">'.esc_url_raw($truncated_url).'</a>';
				}
			}
		}

		// Content for last-updated column.
		if ($column === 'modified') {
			$timedate_parts = explode(' ', $post->post_modified);
			$date_parts = explode('-', $timedate_parts[0]);
			echo esc_html($date_parts[0].'/'.$date_parts[1].'/'.$date_parts[2]);
		}

	}

}