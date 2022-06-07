<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: Public functions for the Update Manager plugin.
 * Package: CodePotent\UpdateManager
 * Author: John Alarcon
 * Author URI: https://codepotent.com
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright 2021 - Code Potent
 * -----------------------------------------------------------------------------
 */

// Declare the namespace.
namespace CodePotent\UpdateManager;

// Prevent direct access.
if (!defined('ABSPATH')) {
	die();
}

/**
 * Get request.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @return array|string
 */
function get_request() {

	// Strip slashes, then tags from request data.
	$request = strip_tags_deep(stripslashes_deep($_REQUEST));

	// Ensure the raw data can't be used elsewhere.
	unset($_REQUEST);

	// Endpoint variable not set? Bail.
	if (empty($request[ENDPOINT_VARIABLE])) {
		return [];
	}

	// Endpoint invalid? Bail.
	if (empty($request['plugin']) && empty($request['theme'])) {
		return [];
	}

	// Type of update being checked for.
	$type = (!empty($request['plugin'])) ? 'plugin' : 'theme';

	// No requesting URL submitted (ie, RC1)? Get it from user agent instead.
	if (empty($request['site_url'])) {
		// User agent messed with? Bail.
		if (empty($_SERVER['HTTP_USER_AGENT'])) {
			return [];
		}
		// Split the user agent parts.
		$user_agent_parts = explode(';', $_SERVER['HTTP_USER_AGENT']);
		// Extract url from string: ClassicPress/x.x.x; https://www.the-site.com
		$site_url = trim(array_pop($user_agent_parts));
		// Still no URL? Bail.
		if (empty($site_url) || !filter_var($site_url, FILTER_VALIDATE_URL)) {
			return [];
		}
		// Alrighty, then! Got a URL, clean it up.
		$request['site_url'] = esc_url($site_url);
	}

	// Add a nonce.
	$request['nonce'] = wp_create_nonce($type.'_request_'.$request['site_url']);

	// Post type to retrieve.
	$post_type = ($type==='plugin' ? CPT_FOR_PLUGIN_ENDPOINTS : CPT_FOR_THEME_ENDPOINTS);

	// Get the endpoint, if it exists, whether published or pending.
	$endpoint = get_posts([
		'post_type' => $post_type,
		'post_status' => ['pending', 'publish'],
		'posts_per_page' => 1,
		'meta_key' => 'id',
		'meta_value' => esc_attr($request[$type]),
	]);

	// No data to work with? Bail.
	if (is_wp_error($endpoint) || empty($endpoint)) {
		return [];
	}

	// Add the endpoint to the request.
	$request['endpoint'] = $endpoint;

	// Deprecated as of 2.0.0; allow for filtering the incoming request.
	$request = apply_filters(PLUGIN_PREFIX.'_filter_request', $request);

	// New in 2.0.0; allow for filtering the incoming request.
	$request = apply_filters(PLUGIN_PREFIX.'_filter_'.$type.'_request', $request);

	// Return the cleansed request.
	return $request;

}

/**
 * Get allowed test URLs.
 *
 * This function retrieves an array of URLs that are allowed to be notified when
 * the plugin has an update set to "Pending" status.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param integer $post_id
 * @return array
 */
function get_allowed_test_urls($post_id) {

	// Get the permissible URLs.
	$meta = get_post_meta($post_id, 'test_urls', true);

	// Turn the URLs into an array.
	$meta = explode("\n", $meta);

	// Return the URLs.
	return $meta;

}

/**
 * Get notification targets.
 *
 * This function retrieves any notification emails/urls associated to the plugin
 * endpoint.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param integer $post_id
 * @return array[]
 */
function get_notification_targets($post_id) {

	// Get the notification targets.
	$meta = get_post_meta($post_id, 'notifications', true);

	// Turn the targets into an array.
	$targets = explode(',', $meta);

	// Initialization.
	$notify = [];

	// Extract the emails and URLs.
	foreach ($targets as $target) {
		if (!empty($target = trim($target))) {
			if (strpos($target, 'http') === 0) {
				$notify['url'][] = $target;
			} else {
				$notify['email'][] = $target;
			}
		}
	}

	// Return the goods.
	return $notify;

}

function get_notification_email_subject($header) {

	// Default email subject.
	$subject = sprintf(
		esc_html__('Report: %s v%s', 'codepotent-update-manager'),
		$header['name'],
		$header['version']);

	// Allow users to filter the value.
	$subject = apply_filters(PLUGIN_PREFIX.'_notification_email_subject', $subject);

	// Ensure sanitization; in case it was filtered.
	$subject = sanitize_text_field($subject);

	// Return the string.
	return $subject;

}

function get_notification_email_body($header) {

	// Initialization.
	$lines = [];

	// Add default text.
	$lines[] = sprintf(
		esc_html__('We greatly appreciate your help with testing this update for *%s*! Please let us know how it went.', 'codepotent-update-manager'),
		$header['name'])."\r\n\r\n";

	// Say thanks.
	$lines[] = esc_html__('Thanks!', 'codepotent-update-manager')."\r\n\r\n";

	// Allow users to filter the email body.
	$lines = apply_filters(PLUGIN_PREFIX.'notification_email_body', $lines);

	// Glue together the lines.
	$body = implode('', $lines);

	// Final sanitization; in case it was filtered.
	$body = sanitize_textarea_field($body);

	// Return the string.
	return $body;

}

function get_notification_email_url($email, $subject, $body) {

	$args = [];
	$args[] = 'subject='.rawurlencode($subject);
	$args[] = 'body='.rawurlencode($body);
	$mail_url = 'mailto:'.$email.'?';
	foreach ($args as $arg) {
		$mail_url .= $arg.'&';
	}

	return $mail_url;
}

/**
 * Plugin information endpoint data
 *
 * This function retrieves the data that is required to fully populate the modal
 * update window with header images, tabbed navigation and content, and data for
 * populating the sidebar.
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @return array|mixed
 */
function component_information($component) {

	// Initialize the return variable.
	$data = [];

	// Get the request.
	$request = get_request();

	// No requesting URL submitted? Bail.
	if (empty($request['site_url'])) {
		return $data;
	}

	// No endpoint passed in? Bail.
	if (empty($request['endpoint']) || !is_object($request['endpoint'][0])) {
		return $data;
	}

	// No plugin id submitted? Bail.
	if (empty($request['plugin']) && empty($request['theme'])) {
		return $data;
	}

	// Whitelist the component type.
	if ($component !== 'plugin') {
		$component = 'theme';
	}

	// Nonce seems suspicious? Bail.
	if (!wp_verify_nonce($request['nonce'], $component.'_request_'.$request['site_url'])) {
		return $data;
	}

	// Get the plugin textual data.
	$content = get_post_meta($request['endpoint'][0]->ID, $request[$component], true);

	// No worthwhile result? Bail.
	if (is_wp_error($content) || empty($content)) {
		return $data;
	}

	// Turn the plugin content into usable data.
	$data = parse_component_data($request[$component], $request['endpoint'], $request, $content);

	// Only whitelisted sites get links to Pending (ie, can test) updates.
	if ($request['endpoint'][0]->post_status === 'pending') {
		$can_test_updates = get_allowed_test_urls($request['endpoint'][0]->ID);
		if (!in_array($request['site_url'], $can_test_updates, true)) {
			$data['download_link'] = '';
			$data['url'] = '';
		}
	}

	// Return the assembled data to the JSON endpoint.
	return apply_filters(PLUGIN_PREFIX.'_'.$component.'_information', $data, $request);

}

/**
 * Plugin information endpoint data
 *
 * This function retrieves the data that is required to fully populate the modal
 * update window with header images, tabbed navigation and content, and data for
 * populating the sidebar.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.0 Use component_information() function instead.
 *
 * @return array|mixed
 */
function plugin_information() {
	return component_information('plugin');
}

/**
 * Query for plugin or theme updates.
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @return mixed
 */
function query_components($component) {

	// Get the request.
	$request = get_request();

	// Ensure a valid component type.
	if ($component !== 'plugin') {
		$component = 'theme';
	}

	// Data returned in case of any issues.
	$nothing = [
		'notice' => sprintf(
			esc_html__('No %s data available', 'codepotent-update-manager'),
			$component)
	];

	// No requesting URL submitted? Bail.
	if (empty($request['site_url'])) {
		return $nothing;
	}

	// No plugin id submitted? Bail.
	if (empty($request[$component])) {
		return $nothing;
	}

	// Nonce seems suspicious? Bail.
	if (!wp_verify_nonce($request['nonce'], $component.'_request_'.$request['site_url'])) {
		return $nothing;
	}

	// Get plugin identifier; ie, plugin-name/plugin-name.php
	$identifier = !empty($request[$component]) ? $request[$component] : false;

	// Get plugins' header data passed in from remote server.
	$remote_headers = !empty($request[$component.'s']) ? $request[$component.'s'] : [];

	// Is the identifier or plugin list unworkable? Bail.
	if (!$identifier && empty($remote_headers)) {
		return $nothing;
	}

	// If querying via a URL in the browser, bail.
	if (empty($remote_headers[$identifier])) {
		return $nothing;
	}

	// Ensure there's a remote version number present, or bail.
	if (empty($remote_version = $remote_headers[$identifier]['Version'])) {
		return $nothing;
	}

	// Surface the endpoint object.
	$endpoint = $request['endpoint'];

	// Get plugin data string.
	$content = get_post_meta($endpoint[0]->ID, $identifier, true);

	// No worthwhile result? Bail.
	if (is_wp_error($content) || empty($content)) {
		return $nothing;
	}

	// Get latest data for the component.
	$latest = parse_component_data($identifier, $endpoint, $request, $content);

	// Get latest version number, or bail.
	if (empty($latest['version'])) {
		return $nothing;
	}

	// Setup default return; contains empty array; meaning no update.
	$data[$identifier] = [];

	// Check if remote version is less than latest version.
	if (version_compare($remote_version, $latest['version'], '<')) {

		if ($component === 'plugin') {
			// Remote version was less? Mmmkay, capture any found details.
			$data[$identifier] = [
					'type'           => $component,
					'id'             => $identifier,
					'identifier'     => $identifier,
					'slug'           => dirname($identifier),
					'plugin'         => $identifier,
					'new_version'    => isset($latest['version'])        ? $latest['version'] : '',
					'package'        => isset($latest['download_link'])  ? $latest['download_link'] : '',
					'url'            => isset($latest['plugin_uri'])     ? $latest['plugin_uri'] : '',
					'requires'       => isset($latest['requires'])       ? $latest['requires'] : '',
					'tested'         => isset($latest['tested'])         ? $latest['tested'] : '',
					'requires_php'   => isset($latest['requires_php'])   ? $latest['requires_php'] : '',
					'updated'        => isset($latest['updated'])        ? $latest['updated'] : '',
					'upgrade_notice' => isset($latest['upgrade_notice']) ? $latest['upgrade_notice'] : '',
			];

		} else if ($component === 'theme') {
			// Remote version was less? Mmmkay, capture any found details.
			$data[$identifier] = [
					'type'           => $component,
					'id'             => $identifier,
					'identifier'     => $identifier,
					'slug'           => $identifier,
					'theme'          => $identifier,
					'new_version'    => isset($latest['version'])        ? $latest['version'] : '',
					'package'        => isset($latest['download_link'])  ? $latest['download_link'] : '',
					'url'            => isset($latest['theme_uri'])      ? $latest['theme_uri'] : '',
					'requires'       => isset($latest['requires'])       ? $latest['requires'] : '',
					'tested'         => isset($latest['tested'])         ? $latest['tested'] : '',
					'requires_php'   => isset($latest['requires_php'])   ? $latest['requires_php'] : '',
					'updated'        => isset($latest['updated'])        ? $latest['updated'] : '',
					'upgrade_notice' => isset($latest['upgrade_notice']) ? $latest['upgrade_notice'] : '',
			];

		}

	}

	// Only whitelisted sites get Pending (ie, can test) updates.
	if ($endpoint[0]->post_status === 'pending') {
		$can_test_updates = get_allowed_test_urls($endpoint[0]->ID);
		if (!in_array($request['site_url'], $can_test_updates, true)) {
			$data[$identifier] = [];
		}
	}

	// Return empty array (no update) or populated array (update data) as JSON.
	return apply_filters(PLUGIN_PREFIX.'_query_'.$component.'s', $data, $request);

}

/**
 * Query for plugin updates.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.0 Use query_components() function instead.
 *
 * @return mixed
 */
function query_plugins() {
	return query_components('plugin');
}

/**
 * Parse readme-style data into usable data.
 *
 * The decision to describe plugin properties through a readme-style format came
 * from several considerations. Speed – many plugin developers already have such
 * files available and can then copy and paste more easily. Bloat – consider how
 * many inputs it would take to flesh out an interface that could accept all the
 * required data.
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @param string $identifier
 * @param object $nedpoint_post
 * @param array $request
 * @param string $content
 * @return array
 */
function parse_component_data($identifier, $endpoint, $request, $content) {

	// Surface the component type.
	$component = 'plugin';
	if (!strstr($request[ENDPOINT_VARIABLE], 'plugin')) {
		$component = 'theme';
	}

	// Initialize the return variable.
	$data = [];

	// Read in the content.
	$lines = explode("\n", $content);

	// Extract the header data.
	$header = get_header_data($lines);

	// Extract the sectional data.
	$sections = get_sections_data($lines);

	// And now, for my next trick... markup all the modal sections!

	// Get markup for the Description tab.
	if (!empty($sections['description'])) {
		$sections['description'] = markup_generic_section($sections['description']);
	}

	// Get markup for the FAQ tab.
	if (!empty($sections['faq'])) {
		$sections['faq'] = markup_generic_section($sections['faq']);
	}

	// Get markup for the Installation tab.
	if (!empty($sections['installation'])) {
		$sections['installation'] = markup_generic_section($sections['installation']);
	}

	// Get markup for the Screenshots tab.
	if (!empty($sections['screenshots']) && !empty($request['screenshot_urls'])) {
		$sections['screenshots'] = markup_screenshots($request['screenshot_urls'], $sections['screenshots']);
	} else {
		unset($sections['screenshots']);
	}

	// Get markup for the Reviews tab.
	if (!empty($sections['reviews'])) {
		$reviews_raw = $sections['reviews'];
		$sections['reviews'] = markup_reviews($sections['reviews']);
	}

	// Get markup for the Other Notes tab.
	if (!empty($sections['other_notes'])) {
		$sections['other_notes'] = markup_generic_section($sections['other_notes']);
	}

	// Get markup for the Reviews tab.
	if (!empty($sections['changelog'])) {
		$sections['changelog'] = markup_generic_section($sections['changelog']);
	}

	// Get markup for the Upgrade Notices; tab and plugin row.
	if (!empty($sections['upgrade_notice'])) {
		// Plugin row; no markup; string.
		$data['upgrade_notice'] = markup_upgrade_notice($sections['upgrade_notice'], true);
		// Modal window; markdown array converted to markup string.
		$sections['upgrade_notice'] = markup_upgrade_notice($sections['upgrade_notice']);
	} else {
		// No upgrade notice? No need for a tab.
		unset($sections['upgrade_notice']);
	}

	// If update is being tested, show a cautionary note above every section.
	if ($endpoint[0]->post_status === 'pending') {
		$test_urls = get_allowed_test_urls($endpoint[0]->ID);
		if (in_array($request['site_url'], $test_urls, true)) {
			$notice = markup_testing_notice($test_urls, $identifier, $header);
			foreach ($sections as $heading=>$content) {
				$sections[$heading] = $notice.$content;
			}
		}
	}

	// Flag indicating plugin is not hosted via ClassicPress plugin directory.
	$data['external']        = true;

	// Unique identifier; ie, my-plugin-dir/my-plugin-file.php
	$data['identifier']      = $identifier;

	// Plugin/theme slug; ie, my-theme-name or my-plugin-name/my-plugin-name.php
	if ($component === 'plugin') {
		$data['slug']        = dirname($identifier);
	} else if ($component === 'theme') {
		$data['slug']        = $identifier;
	}

	// Display name for the plugin.
	$data['name']            = !empty($header['name'])          ? $header['name'] : '';

	// Plugin description.
	$data['description']     = !empty($sections['description']) ? $sections['description'] : '';

	// Plugin version; ie, 1.2.3
	$data['version']         = !empty($header['version'])       ? $header['version'] : '';

	// Text domain; ie, my-plugin-name
	$data['text_domain']     = !empty($header['text_domain'])   ? $header['text_domain'] : '';

	// Text domain path; ie, /languages
	$data['domain_path']     = !empty($header['domain_path'])   ? $header['domain_path'] : '';

	// Minimum PHP requirement; ie, 5.6, or 7, or 7.2, etc
	$data['requires_php']    = !empty($header['requires_php'])  ? $header['requires_php'] : '';

	// Minimum ClassicPress requirement; ie, 1.1.1
	$data['requires']        = !empty($header['requires'])      ? $header['requires'] : '';

	// Maximum ClassicPress compatibility; ie, 1.1.1
	$data['tested']          = !empty($header['tested'])        ? $header['tested'] : '';

	// Author URL string.
	$data['author']          = '';
	if (!empty($header['author_uri']) && !empty($header['author'])) {
		$data['author']      = '<a href="'.$header['author_uri'].'">'.$header['author'].'</a>';
	}
	// Author URL.
	$data['author_uri']      = !empty($header['author_uri'])    ? $header['author_uri'] : '';

	// Plugin/theme URI.
	if ($component === 'plugin') {
		$data['plugin_uri']      = !empty($header['plugin_uri'])    ? $header['plugin_uri'] : '';
	} else if ($component === 'theme') {
		$data['theme_uri']       = !empty($header['theme_uri'])     ? $header['theme_uri'] : '';
	}

	// Download link; ie, https://somesite.com/some-file.zip
	$data['download_link']   = !empty($header['download_link']) ? $header['download_link'] : '';
	if ($component === 'theme') {
		$data['url']             = $data['download_link'];
	}

	// Donation link; ie, https://pay.me/?now-would-be-nice
	$data['donate_link']     = !empty($header['donate_link'])   ? $header['donate_link'] : '';

	// License; ie, GPL v2
	$data['license']         = !empty($header['license'])       ? $header['license'] : '';

	// License URL; ie, https://somesite.com/licence.html
	$data['license_uri']     = !empty($header['license_uri'])   ? $header['license_uri'] : '';

	// Plugin/theme home page.
	if ($component === 'plugin') {
		$data['homepage']        = !empty($header['plugin_uri'])    ? $header['plugin_uri'] : '';
	} else if ($component === 'theme') {
		$data['homepage']        = !empty($header['theme_uri'])     ? $header['theme_uri'] : '';
	}

	// Plugin last update date; ie, 2019-12-16 15:02:08
	$data['last_updated']    = $endpoint[0]->post_modified;

	// Plugin release date; ie, 2019-12-16 15:02:08
	$data['added']           = $endpoint[0]->post_date;

	// Active installs; filterable since active installs aren't counted.
	$data['active_installs'] = apply_filters(PLUGIN_PREFIX.'_'.$identifier.'_active_installs', 0, $identifier);
	if (empty($data['active_installs'])) {
		unset($data['active_installs']);
	}

	// Plugin banners and icons.
	if ($component === 'plugin') {
		// Array of URLs to the banner images for the plugin.
		$data['banners']         = get_plugin_banners($request);
		// Array of URLs to the icons images for the plugin.
		$data['icons']           = get_plugin_icons($endpoint, $identifier);
	}

	// Compatibility map; is this needed?
	$data['compatibility']   = [];
	if (!empty($header['requires'])) {
		$data['compatibility']   = [
				$header['requires'] => true
		];
	}

	// Before capturing sections to the data array; add star rating data.
	if (!empty($sections['reviews'])) {
		$data['ratings']     = get_ratings($reviews_raw);
		$data['num_ratings'] = array_sum($data['ratings']);
		$data['rating']      = get_score($data['ratings'], $data['num_ratings']);
	}

	// Place sections at the end; purely for a more visually aesthetic endpoint.
	$data['sections']        = $sections;

	// Assignments complete!

	// Filter the assembled data, if needed.
	$data = apply_filters(PLUGIN_PREFIX.'_filter_parsed_component_data', $data, $request);

	// Remove certain fields if URL not whitelisted for (Pending) updates.
	$data = prevent_unauthorized_pending_updates($request, $endpoint, $data);

	// Finally!
	return $data;

}

/**
 * Parse readme-style data into usable data.
 *
 * The decision to describe plugin properties through a readme-style format came
 * from several considerations. Speed – many plugin developers already have such
 * files available and can then copy and paste more easily. Bloat – consider how
 * many inputs it would take to flesh out an interface that could accept all the
 * required data.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.0 Use parse_component_data() function instead.
 *
 * @param string $identifier
 * @param object $nedpoint_post
 * @param array $request
 * @param string $content
 * @return array
 */
function parse_plugin_data($identifier, $endpoint, $request, $content) {
	return parse_component_data($identifier, $endpoint, $request, $content);
}

/**
 * Filter data for Pending updates.
 *
 * This function ensures that, when an update endpoint is in Pending status, the
 * requestors who are whitelisted receive the update data while other requestors
 * receive a bit less data – basic info only, without triggering an update. This
 * function defaults, resets, or otherwise nullifies a few $data elements, which
 * then causes the update to not be found.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $request The incoming request.
 * @param object $endpoint The endpoint associated with the remote plugin.
 * @param array $data The full data provided to the update endpoint.
 * @return array The possibly-amended data array.
 */
function prevent_unauthorized_pending_updates($request, $endpoint, $data) {

	// No endpoint id? Bail.
	if (empty($endpoint[0]->ID)) {
		return [];
	}

	// Endpoint not in Pending status? Return unaltered data.
	if ($endpoint[0]->post_status !== 'pending') {
		return $data;
	}

	// Get URLs allowed to have Pending updates.
	$test_urls = get_allowed_test_urls($endpoint[0]->ID);

	// Requesting URL is whitelisted for Pending updates? Return unaltered data.
	if (in_array($request['site_url'], $test_urls, true)) {
		return $data;
	}

	// Alter data to provide info only without update.
	$data['version'] = !empty($request['version']) ? $request['version'] : '';
	$data['requires_php'] = '';
	$data['requires'] = '';
	$data['tested'] = '';
	$data['download_link'] = '';
	$data['last_updated'] = '';
	$data['compatibility']   = [];

	// Unset the (list-table) upgrade notice and its related (modal window) tab.
	unset($data['upgrade_notice'], $data['sections']['upgrade_notice']);

	// Return the data only; no update.
	return $data;

}

/**
 * Get plugin icons.
 *
 * For the plugin with an available update, check its /images/ directory and see
 * if there are any icon files. If any icons are found, they will be are used in
 * the list table that shows available updates. Icon files are expected to be in
 * png or jpg format. The icons should be named as follows:
 *
 * 		Normal Icons: icon-128.{png|jpg}
 * 		Retina Icons: icon-256.{png|jpg}
 *
 * If the remote plugin has no icon files in its /images/ directory, then a core
 * placeholder image is used.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param object $plugin_post
 * @param string $plugin_identifer
 * @return array
 */
function get_plugin_icons($plugin_post, $identifer) {

	// Initialize return variable.
	$icons = [];

	// Path and URL to the plugin's image directory.
	$path = WP_PLUGIN_DIR.'/'.dirname($identifer).'/images';
	$url  = WP_PLUGIN_URL.'/'.dirname($identifer).'/images';

	// Check for an SVG icon.
	if (file_exists($path.'/icon.svg')) {
		// SVG image can be safely used at all saves.
		$icons['default'] = $url.'/icon.svg';
		$icons['1x']      = $url.'/icon.svg';
		$icons['2x']      = $url.'/icon.svg';
		// Got what we need; return early.
		return $icons;
	}

	// Check next for PNG or JPG images.
	foreach (['png', 'jpg'] as $ext) {
		// Normal icon; set as default for all sizes.
		if (file_exists($path.'/icon-128.'.$ext)) {
			$icons['default'] = $url.'/icon-128.'.$ext;
			$icons['1x']      = $url.'/icon-128.'.$ext;
			$icons['2x']      = $url.'/icon-128.'.$ext;
		}
		// Retina icon; overwrite if found.
		if (file_exists($path.'/icon-256.'.$ext)) {
			$icons['2x'] = $url.'/icon-256.'.$ext;
		}
	}

	// Return png or jpg icons, if found.
	return $icons;

}

/**
 * Get plugin banners.
 *
 * For the plugin with an available update, check its /images/ directory and see
 * if there are any banner files. If any icons are found, they will be used in
 * the list table that shows available updates. Icon files are expected to be in
 * png or jpg format. The icons should be named as follows:
 *
 * 		Normal Icons: icon-128.{png|jpg}
 * 		Retina Icons: icon-256.{png|jpg}
 *
 * If the remote plugin has no icon files in its /images/ directory, then a core
 * placeholder image is used.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param object $plugin_post
 * @param string $plugin_identifier
 * @return array
 */
function get_plugin_banners($request) {

	// Initialization.
	$banners = [
		'default' => '',
		'low'     => '',
		'high'    => '',
	];

	// If banner URLs passed in remotely, return those after a quick cleanse.
	if (!empty($request['banner_urls'])) {
		$allowed_keys = ['default', 'low', 'high'];
		foreach ((array)$request['banner_urls'] as $key=>$banner) {
			if (in_array($key, $allowed_keys, true)) {
				$banners[$key] = esc_url($banner);
			}
		}
	}

	// Return any banner URLs.
	return $banners;

}

/**
 * Recursive tag removal.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array|string $value
 * @return array|string String devoid of tags.
 */
function strip_tags_deep($value) {

	// Oh, it's an array? Drill down.
	if (is_array($value)) {
		return array_map(__NAMESPACE__.'\strip_tags_deep', $value);
	}

	// At last, a string! Strip the tags.
	return wp_strip_all_tags($value);

}

/**
 * Get plugin ratings.
 *
 * This function will eventually gather the actual "stars" received with a given
 * rating and translate those values into an array. Since this functionality has
 * not been implemented yet, all the ratings are being presumed 5-stars and math
 * is done base on that to come up with plugins' score (out of 100).
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @param string $opinions
 * @return array
 */
function get_ratings($opinions) {

	// Default data to start with.
	$ratings = [ 5=>0, 4=>0, 3=>0, 2=>0, 1=>0 ];

	// Capture counts of ratings.
	foreach ($opinions as $line) {
		if (!empty($line = trim($line))) {
			if (strpos($line, '*') === 0) {
				$ratings[strlen($line)] += 1;
			}
		}
	}

	// Return the array.
	return $ratings;

}

/**
 * Get plugin ratings.
 *
 * This function will eventually gather the actual "stars" received with a given
 * rating and translate those values into an array. Since this functionality has
 * not been implemented yet, all the ratings are being presumed 5-stars and math
 * is done base on that to come up with plugins' score (out of 100).
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.0 Use get_ratings() function instead.
 *
 * @param string $opinions
 * @return array
 */
function get_plugin_ratings($opinions) {
	return get_ratings($opinions);
}

/**
 * Get plugin score.
 *
 * The function calculates the plugin's user-rated score, up to 100.
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @param array $ratings
 * @param integer $total
 * @return integer
 */
function get_score($ratings, $total) {

	// Missing arguments? Bail.
	if (empty($ratings) || empty($total)) {
		return 0;
	}

	// Determine the sum of all ratings.
	$rating_sum = 0;
	for ($i=1; $i<=5; $i++) {
		$rating_sum += (int)$ratings[$i] * $i;
	}

	// Calculate the score.
	$score = floor($rating_sum / (int)$total * 20);

	// Retuen the score.
	return $score;

}

/**
 * Get plugin score.
 *
 * The function calculates the plugin's user-rated score, up to 100.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.0 Use get_score() function instead.
 *
 * @param array $ratings
 * @param integer $total
 * @return integer
 */
function get_plugin_ratings_score($ratings, $total) {
	return get_score($ratings, $total);
}

/**
 * Get header data.
 *
 * This method extracts the various properties expected in a CLassicPress plugin
 * header section. The data is coming in as an array of lines.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $lines
 * @return array The data from the endpoint's header section.
 */
function get_header_data(&$lines) {

	// Initialize return variable.
	$header = [];

	// Line, please?!
	foreach ($lines as $k=>$line) {
		// Trim up the right side.
		$line = rtrim($line);
		// Assign trimmed value back to original array.
		$lines[$k] = $line;
		// Grab the plugin name: === Plugin Name ===
		if (strpos(strtolower($line), '===') === 0) {
			// Remove === and spaces; then unset from original array.
			$header['name'] = trim(str_replace('===', '', $line));
			unset($lines[$k]);
		}
		// Other values to check for in the header; lowercase; with colon.
		$header_properties = ['name:', 'description:', 'version:',
			'text domain:', 'domain path:', 'requires php:', 'requires:',
			'tested:', 'author:', 'author uri:', 'plugin uri:', 'download link:',
			'theme uri:', 'donate link:', 'license:', 'license uri:', 'tags:',
		];
		// Iterate over $header_properties to check if they are in $line.
		foreach ($header_properties as $str) {
			// If $line is relevant, capture as k/v pair; unset from original array.
			$key   = str_replace(' ', '_', substr($str, 0, strlen($str)-1));
			$value = trim(substr($line, strlen($str)));
			if (strpos(strtolower($line), $str) === 0) {
				$header[$key] = $value;
				unset($lines[$k]);
			}
		}

	} // foreach $lines

	return $header;

}

/**
 * Get plugin sections data.
 *
 * This method extracts the various sections expected in a ClassicPress plugin's
 * readme.txt file, although the data is actually coming in as an array of lines
 * and not a string read from a file.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $lines
 * @return array[][]
 */
function get_sections_data(&$lines) {

	// Particular sections of interest.
	$natural_sections = [
		'description'    => 'description',
		'faq'            => 'frequently asked questions',
		'installation'   => 'installation',
		'screenshots'    => 'screenshots',
		'reviews'        => 'reviews',
		'other_notes'    => 'other notes',
		'changelog'      => 'changelog',
		'upgrade_notice' => 'upgrade notice',
	];

	// Throw my thing down, flip it, and reverse it. ~ Missy Elliot
	$inverse_sections = array_flip($natural_sections);

	// Initialization.
	$sections = [];
	// Flag.
	$started = false;

	// Iterate over remaining lines.
	foreach ($lines as $line) {
		// Check flag; continue until first actual section.
		if (!$started && strpos($line, '==') !== 0) {
			continue;
		}
		// Capture headings.
		if (strpos($line, '==') === 0) {
			$started = true;
			// frq aske qustion
			$heading = trim(str_replace('==', '', strtolower($line)));
			// If the key exists...
			if (!empty($inverse_sections[$heading])) {
				$sections[$inverse_sections[$heading]] = [];
			} else {
				$sections[$heading][] = $heading;
			}
			continue;
		}
		// If $line wasn't a heading, it's data...capture it with a one-liner.
		if (!empty($inverse_sections[$heading])) {
			$sections[$inverse_sections[$heading]][] = $line;
		} else {
			$sections[$heading][] = $line;
		}

	}

	// Remove any unexpected sections.
	foreach (array_keys($sections) as $heading) {
		if (empty($natural_sections[$heading])) {
			unset($sections[$heading]);
		}
	}


	// Return the sections data.
	return $sections;

}

/**
 *
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $targets
 * @param string $identifier
 * @param array $header
 * @return string
 */
function markup_testing_notice($targets, $identifier, $header) {

	// Create an email-generating URL, if needed.
	$args = [];
	if (!empty($targets['email'])) {
		$subject = sprintf(
			esc_html__('Pending Update Report: %s v%s', 'codepotent-update-manager'),
			$header['name'],
			$header['version']);
		$lines = [];
		$lines[] = sprintf(
			esc_html__('We greatly appreciate your help and feedback in testing the update to %s version %s — thanks!', 'codepotent-update-manager'),
			$header['name'],
			$header['version'])."\r\n\r\n";
		$body = implode('', $lines);
		$args[] = array_shift($targets['email']);
		$args[] = 'subject='.rawurlencode($subject);
		$args[] = 'body='.rawurlencode($body);
		$args[] = 'cc='.rawurlencode(implode(',', $targets['email']));
		$mail_url = 'mailto:'.array_shift($args).'?';
		foreach ($args as $arg) {
			$mail_url .= $arg.'&';
		}
	}

	// Create an HTTP URL, if needed.
	if (!empty($targets['url'])) {
		$http_url = array_shift($targets['url']);
	}

	// Initilazation.
	$note1 = $note2 = '';

	// Cautionary note regarding this being a testing update.
	$note1 = sprintf(
			esc_html__('%sCaution%s: This update is currently undergoing testing &#8211; it is not yet intended for production.', 'codepotent-update-manager'),
			'<strong>',
			'</strong>');

	// Texts for nudging users toward notifying you about any issues.
	if (!empty($mail_url) && !empty($http_url)) {
		$note2 = sprintf(
			esc_html__('If you experience any issues, please %ssend an email%s or %sreport it here%s.', 'codepotent-update-manager'),
			'<a href="'.esc_url($mail_url).'">',
			'</a>',
			'<a href="'.esc_url($http_url).'">',
			'</a>');
	} else if (!empty($mail_url)) {
		$note2 = sprintf(
			esc_html__('If you experience any issues, please %ssend an email%s.', 'codepotent-update-manager'),
			'<a href="'.esc_url($mail_url).'">',
			'</a>');
	} else if (!empty($http_url)) {
		$note2 = sprintf(
			esc_html__('If you experience any issues, please %sreport it here%s.', 'codepotent-update-manager'),
			'<a href="'.esc_url($http_url).'">',
			'</a>');
	}

	// Markup the notice.
	$markup  = '<div class="plugin_testing_notice">';
	$markup .= '<p><span>';
	$markup .= $note1.' '.$note2;
	$markup .= '</span></p>';
	$markup .= '</div>';

	// Return the markup.
	return $markup;

}

/**
 * Markup for generic sections.
 *
 * For sections that don't need any special processing, they can be passed right
 * into this fuction as an array of lines of markdown.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param string $changelog A string of markdown.
 * @return string Markup for the Changelog tab in the modal windows.
 */
function markup_generic_section($content) {

	// Initialization.
	$markup = '';

	// Now Parsedown gets a turn; and returns the final markup.
	$markup .= Parsedown::instance()->text(implode("\r\n", $content));

	// Return the markup.
	return $markup;

}

/**
 * Markup for Description tab.
 *
 * The content displayed in the Description tab (of modal windows) is simply the
 * post content body.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param string $description The post content.
 * @return string Markup for the Description tab in the modal windows.
 */
function markup_description($description) {

	// Initialization.
	$markup = '';

	// Filter to preserve formatting, expand shortcodes, etc.
	$markup .= apply_filters('the_content', $description[0]);

	// Return the markup.
	return $markup;

}

/**
 * Markup for Reviews tab.
 *
 * The content for the reviews tab takes a bit of string manipulation to add the
 * proper quotes and preserve the formatting expected from the endpoint editor.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param string $reviews A string of markdown.
 * @return string Markup for the Reviews tab in the modal windows.
 */
function markup_reviews($reviews) {

	// No data? Bail.
	if (empty($reviews) || !is_array($reviews)) {
		return;
	}

	// Initialization.
	$markup = '';

	// Convert asterisks to stars; Parsedown is only so powerful.
	foreach ($reviews as $n=>$line) {
		if (strpos($line, '*') === 0) {
			$stars  = '<div class="star-rating">'."\n";
			$stars .= '<div class="wporg-ratings">'."\n";
			$stars .= str_replace('*', '<span class="star dashicons dashicons-star-filled"></span>'."\n", $line);
			$stars .= '</div>'."\n";
			$stars .= '</div>'."\n";
			$reviews[$n] = $stars;
		}
	}

	// Now Parsedown gets a turn; and returns the final markup.
	$markup .= Parsedown::instance()->setBreaksEnabled(true)->text(implode("\r\n", $reviews));

	// Return markup string.
	return $markup;

}

/**
 * Markup for Screenshots tab.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $urls
 * @param array $raw_captions
 * @return string Markup for the Screenshots tab in the modal windows.
 */
function markup_screenshots($urls, $raw_captions) {

	// Initialization.
	$screenshots = '';

	// No URLs to work with? Bail.
	if (empty($urls)) {
		return $screenshots;
	}

	// Allow for a header text.
	foreach ($raw_captions as $n=>$raw_caption) {
		if (strpos($raw_caption, '#') === 0) {
			$screenshots .= Parsedown::instance()->setBreaksEnabled(true)->text($raw_caption);
			unset($raw_captions[$n]);
			break;
		}
	}

	// Map any found captions to the received URLs.
	if (!empty($raw_captions)) {
		foreach ($raw_captions as $n=>$raw_caption) {
			if (!empty($raw_caption)) {
				$captions[(int)$raw_caption] = substr($raw_caption, strpos($raw_caption, ' ')+1);
			}
		}
	}

	// List-item the screenshots.
	foreach ((array)$urls as $n=>$file) {
		$caption = !empty($captions[$n]) ? $captions[$n] : '';
		$screenshots .= '<p><img src="'.esc_url($file).'" alt="'.esc_attr($caption).'"></p>';
		if ($caption) {
			$screenshots .= '<div>'.Parsedown::instance()->setBreaksEnabled(true)->text($caption).'</div>';
		}
	}

	// Return the markup.
	return $screenshots;

}

/**
 * Markup for Upgrade Notice tab.
 *
 * This function name may be a bit misleading. It will only ever return plaintext.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 */
function markup_upgrade_notice($notice, $text_only=false) {

	// If notice is a string, return it.
	if (is_string($notice)) {
		return $notice;
	}

	// Plugin row gets 1 line of text; make it so.
	if ($text_only) {
		foreach ($notice as $line) {
			$line = trim($line);
			if (empty($line) || strpos($line, '#') === 0) {
				continue;
			}
			return $line;
		}
	}

	// Convert $notice array to markup.
	$notice = markup_generic_section($notice);

	// Return markup.
	return $notice;

}

/**
 * Markup for inline cheat sheet.
 *
 * This markup is displayed under the primary metabox inputs and provides a fast
 * way to recall what values are expected in the endpoint header section.
 *
 * @author John Alarcon
 *
 * @since 2.0.0
 *
 * @param string $component Will be either 'plugin' or 'theme'.
 *
 * @return string Markup of the cheat sheet.
 */
function markup_header_data_legend($component) {

	// Whitelist the component type.
	if ($component !== 'plugin') {
		$component = 'theme';
	}

	// Container.
	$markup = '<div id="'.PLUGIN_SLUG.'-cheat-sheet">';

	// Heading.
	$markup .= '<h3>'.esc_html__('Header Properties Cheat Sheet', 'codepotent-update-manager').'</h3>';

	// Informational note.
	$markup .= '<blockquote>';
	$markup .= '<p>';
	$markup .= sprintf(
			esc_html__('In the %s editor above, the lines between %s and %s are known as the &quot;header properties&quot;.', 'codepotent-update-manager'),
			'<strong>'.ucfirst($component).' Details</strong>',
			'<code>=== '.ucfirst($component).' Name ===</code>',
			'<code>== Description ==</code>');
	$markup .= ' ';
	$markup .= esc_html__('The following header properties can be used.', 'codepotent-update-manager');
	$markup .= ' ';
	$markup .= sprintf(
			esc_html__('Note that each property name must be followed by a colon %1$s:%2$s and the value for the property must be on the same line, for example, %1$sVersion: 1.2.3%2$s or %1$sAuthor: John Alarcon%2$s.', 'codepotent-update-manager'),
			'<code>',
			'</code>');
	$markup .= '</p>';
	$markup .= '</blockquote>';

	// Description.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Description', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('A succinct description or overview of the %1$s. This text can span more than a single line in the editor, but, must not contain any line breaks or carriage returns. Used in %1$s update admin rows.', 'codepotent-update-manager'),
			$component);
	$markup .= '</p>';

	// Version.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Version', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="required">'.esc_html__('Required', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The latest version number for the %s. Semantic versions are supported, for example, %s.', 'codepotent-update-manager'),
			$component,
			'<code>2.3.10</code>');
	$markup .= '</p>';

	// Text domain.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Text Domain', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The text domain used in your translated texts; often simply the %s own directory name, for example, %s.', 'codepotent-update-manager'),
			$component.'&#39;s',
			'<code>my-'.$component.'-name</code>');
	$markup .= '</p>';

	// Domain path.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Domain Path', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The path to the directory that holds your translation files, relative to the text domain directory, often %s.', 'codepotent-update-manager'),
			'<code>/languages</code>');
	$markup .= '</p>';

	// Requires PHP.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Requires PHP', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The minimum version of PHP required by the %s, for example, %s, %s, or %s.', 'codepotent-update-manager'),
			$component,
			'<code>5.6.40</code>',
			'<code>7</code>',
			'<code>7.2</code>'
			);
	$markup .= '</p>';

	// Minimum ClassicPress requirement.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Requires', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="required">'.esc_html__('Required', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The minimum version of ClassicPress required by the %s, for example, %s or %s.', 'codepotent-update-manager'),
			$component,
			'<code>1.0.0</code>',
			'<code>1.1.2</code>'
			);
	$markup .= '</p>';

	// Maximum ClassicPress requirement.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Tested', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The maximum version of ClassicPress the %s has been tested with. You can set a specific ClassicPress version number here to indicate compatibility up to a certain version, or, you can set it to %s to indicate 100&#37; compatible.', 'codepotent-update-manager'),
			$component,
			'<code>4.9.99</code>');
	$markup .= '</p>';

	// Author name.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Author', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The author, developer, or agency name in plain text, for example %s.', 'codepotent-update-manager'),
			'<code>Code Potent</code>');
	$markup .= '</p>';

	// Author URI.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Author URI', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('A link to the author, developer, or agency website, for example, %s.', 'codepotent-update-manager'),
			'<code>https://www.site.com</code>');
	$markup .= '</p>';

	// Component URI.
	$markup .= '<p>';
	if ($component === 'plugin') {
		$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Plugin URI', 'codepotent-update-manager').'</code> ';
	} else {
		$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Theme URI', 'codepotent-update-manager').'</code> ';
	}
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('A link to the %s own page on the web, for example, %s.', 'codepotent-update-manager'),
			$component.'&#39;s',
			'<code>https://site.com/my-'.$component.'/</code>');
	$markup .= '</p>';

	// Download link.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Download link', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="required">'.esc_html__('Required', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('A link to the zip file with the updated version of the %s, for example, %s.', 'codepotent-update-manager'),
			$component,
			'<code>https://site.com/the-updated-file.zip</code>');
	$markup .= '</p>';

	// Donate link.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('Donate link', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= esc_html__('A link to solicit donations for the effort. This link might be to a Patreon or PayPal account, or even just to a page on your own site.', 'codepotent-update-manager');
	$markup .= '</p>';

	// License type.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('License', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('The license type under which the %s is released, for example, %s or %s.', 'codepotent-update-manager'),
			$component,
			'<code>GPLv2</code>',
			'<code>GPLv3</code>'
			);
	$markup .= '</p>';

	// License URI.
	$markup .= '<p>';
	$markup .= '<code class="'.PLUGIN_SLUG.'-property-name">'.esc_html__('License URI', 'codepotent-update-manager').'</code> ';
	$markup .= '<em class="optional">'.esc_html__('Optional', 'codepotent-update-manager').'</em><br>';
	$markup .= sprintf(
			esc_html__('A link to the license applicable to the %s.', 'codepotent-update-manager'),
			$component);
	$markup .= '</p>';

	// Container.
	$markup .= '</div>';

	// Return the markup string.
	return $markup;

}