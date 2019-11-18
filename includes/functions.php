<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: Public functions for the Update Manager plugin.
 * Package: CodePotent\UpdateManager
 * Version: 1.0.0
 * Author: Code Potent
 * Author URI: https://codepotent.com
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

	// No requesting URL submitted? Bail.
	if (empty($request['site_url'])) {
		return [];
	}

	// Add a nonce.
	$request['nonce'] = wp_create_nonce('plugin_request_'.$request['site_url']);

	// Get the endpoint, if it exists, whether published or pending.
	$endpoint = get_posts([
		'post_type' => CPT_FOR_PLUGIN_REPOS,
		'post_status' => ['pending', 'publish'],
		'posts_per_page' => 1,
		'meta_key' => 'id',
		'meta_value' => esc_attr($request['plugin']),
	]);

	// No data to work with? Bail.
	if (is_wp_error($endpoint) || empty($endpoint)) {
		return [];
	}

	// Add the endpoint to the request.
	$request['endpoint'] = $endpoint;

	// Only whitelisted sites get Pending updates (ie, can test updates.)
	if ($endpoint[0]->post_status === 'pending') {
		$can_test_updates = get_allowed_test_urls($endpoint[0]->ID);
		if (!in_array($request['site_url'], $can_test_updates, true)) {
			return [];
		}
	}

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

/**
 * Query for plugin updates.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function query_plugins() {

	// Initialize the return variable.
	$data = [];

	// Get the request.
	$request = get_request();

	// No requesting URL submitted? Bail.
	if (empty($request['site_url'])) {
		return $data;
	}

	// No plugin id submitted? Bail.
	if (empty($request['plugin'])) {
		return $data;
	}

	// Nonce seems suspicious? Bail.
	if (!wp_verify_nonce($request['nonce'], 'plugin_request_'.$request['site_url'])) {
		return $data;
	}

	// Get plugin identifier; ie, plugin-name/plugin-name.php
	$identifier = !empty($request['plugin']) ? $request['plugin'] : false;

	// Get plugins' header data passed in from remote server.
	$remote_headers = !empty($request['plugins']) ? $request['plugins'] : [];

	// Is the identifier or plugin list unworkable? Bail.
	if (!$identifier || empty($remote_headers)) {
		return $data;
	}

	// Ensure there's a remote version number present, or bail.
	if (empty($remote_version = $remote_headers[$identifier]['Version'])) {
		return $data;
	}

	// Surface the endpoint object.
	$endpoint = $request['endpoint'];

	// Only whitelisted sites get Pending (ie, can test) updates.
	if ($endpoint[0]->post_status === 'pending') {
		$can_test_updates = get_allowed_test_urls($endpoint[0]->ID);
		if (!in_array($request['site_url'], $can_test_updates, true)) {
			return [];
		}
	}

	// Get plugin data string.
	$content = get_post_meta($endpoint[0]->ID, $identifier, true);

	// No worthwhile result? Bail.
	if (is_wp_error($content) || empty($content)) {
		return $data;
	}

	// Parse the plugin data into usable data.
	$latest = parse_plugin_data($identifier, $endpoint, $request, $content);

	update_option('latest-asdfa', $latest);
	// Get latest version number, or bail.
	if (empty($latest['version'])) {
		return $data;
	}

	// Setup default return; contains empty array; meaning no update.
	$data[$identifier] = [];

	// Check if remote version is less than latest version.
	if (version_compare($remote_version, $latest['version'], '<')) {

		// Remote version was less? Mmmkay, capture any found details.
		$data[$identifier] = [
			'slug'           => dirname($identifier),
			'plugin'         => $identifier,
			'new_version'    => isset($latest['version'])        ? $latest['version'] : '',
			'package'        => isset($latest['download_link'])  ? $latest['download_link'] : '',
			'requires'       => isset($latest['requires'])       ? $latest['requires'] : '',
			'tested'         => isset($latest['tested'])         ? $latest['tested'] : '',
			'requires_php'   => isset($latest['requires_php'])   ? $latest['requires_php'] : '',
			'updated'        => isset($latest['updated'])        ? $latest['updated'] : '',
			'upgrade_notice' => isset($latest['upgrade_notice']) ? $latest['upgrade_notice'] : '',
		];

	}

	// Return empty array (no update) or populated array (update data) as JSON.
	return apply_filters(PLUGIN_PREFIX.'_query_plugins', $data, $request);

}

/**
 * Plugin informaiton endpoint data
 *
 * This function retrieves the data that is required to fully populate the modal
 * update window with header images, tabbed navigation and content, and data for
 * populating the sidebar.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @return array|mixed
 */
function plugin_information() {

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
	if (empty($request['plugin'])) {
		return $data;
	}

	// Nonce seems suspicious? Bail.
	if (!wp_verify_nonce($request['nonce'], 'plugin_request_'.$request['site_url'])) {
		return $data;
	}

	// Only whitelisted sites get Pending (ie, can test) updates.
	if ($request['endpoint'][0]->post_status === 'pending') {
		$can_test_updates = get_allowed_test_urls($request['endpoint'][0]->ID);
		if (!in_array($request['site_url'], $can_test_updates, true)) {
			return [];
		}
	}

	// Get the plugin textual data.
	$content = get_post_meta($request['endpoint'][0]->ID, $request['plugin'], true);

	// No worthwhile result? Bail.
	if (is_wp_error($content) || empty($content)) {
		return $data;
	}

	// Turn the plugin content into usable data.
	$data = parse_plugin_data($request['plugin'], $request['endpoint'], $request, $content);

	// Return the assembled data to the JSON endpoint.
	return apply_filters(PLUGIN_PREFIX.'_plugin_information', $data, $request);

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
 * @param string $identifier
 * @param object $nedpoint_post
 * @param array $request
 * @param string $content
 * @return array
 */
function parse_plugin_data($identifier, $endpoint, $request, $content) {

	// Initialize the return variable.
	$data = [];

	// Read in the content.
	$lines = explode("\n", $content);

	// Extract the header data.
	$header = get_header_data($lines);

	// Extract the sectional data.
	$sections = get_sections_data($lines);


	// Get markup for the Description tab.
	if (!empty($sections['description'])) {
		$sections['description'] = markup_plugin_generic_section($sections['description']);
	}

	// Get markup for the FAQ tab.
	if (!empty($sections['faq'])) {
		$sections['faq'] = markup_plugin_generic_section($sections['faq']);
	}

	// Get markup for the Installation tab.
	if (!empty($sections['installation'])) {
		$sections['installation'] = markup_plugin_generic_section($sections['installation']);
	}

	// Get markup for the Screenshots tab.
	if (!empty($sections['screenshots']) && !empty($request['screenshot_urls'])) {
		$sections['screenshots'] = markup_plugin_screenshots($request['screenshot_urls'], $sections['screenshots']);
	} else {
		unset($sections['screenshots']);
	}

	// Get markup for the Reviews tab.
	if (!empty($sections['reviews'])) {
		$reviews_raw = $sections['reviews'];
		$sections['reviews'] = markup_plugin_reviews($sections['reviews']);
	}

	// Get markup for the Other Notes tab.
	if (!empty($sections['other_notes'])) {
		$sections['other_notes'] = markup_plugin_generic_section($sections['other_notes']);
	}

	// Get markup for the Reviews tab.
	if (!empty($sections['changelog'])) {
		$sections['changelog'] = markup_plugin_generic_section($sections['changelog']);
	}

	// Get markup for the Upgrade Notice tab; actually is plaintext.
	if (!empty($sections['upgrade_notice'])) {
		$sections['upgrade_notice'] = markup_plugin_upgrade_notice($sections['upgrade_notice']);
		$data['upgrade_notice'] = $sections['upgrade_notice'];
	}

	// If update is being tested, show a cautionary note above every section.
	if ($endpoint[0]->post_status === 'pending') {
		$targets = get_notification_targets($endpoint[0]->ID);
		$notice = markup_testing_notice($targets, $identifier, $header);
		foreach ($sections as $heading=>$content) {
			$sections[$heading] = $notice.$content;
		}
	}

	// And, now, assign all the things.
	$data['external']        = true;
	$data['identifier']      = $identifier;
	$data['slug']            = dirname($identifier);
	$data['name']            = !empty($header['name'])          ? $header['name'] : '';
	$data['description']     = !empty($sections['description']) ? $sections['description'] : '';
	$data['version']         = !empty($header['version'])       ? $header['version'] : '';
	$data['text_domain']     = !empty($header['text_domain'])   ? $header['text_domain'] : '';
	$data['domain_path']     = !empty($header['domain_path'])   ? $header['domain_path'] : '';
	$data['requires_php']    = !empty($header['requires_php'])  ? $header['requires_php'] : '';
	$data['requires']        = !empty($header['requires'])      ? $header['requires'] : '';
	$data['tested']          = !empty($header['tested'])        ? $header['tested'] : '';
	$data['author']          = ''; // Initialization.
	if (!empty($header['author_uri']) && !empty($header['author'])) {
		$data['author']      = '<a href="'.$header['author_uri'].'">'.$header['author'].'</a>';
	}
	$data['author_uri']      = !empty($header['author_uri'])    ? $header['author_uri'] : '';
	$data['plugin_uri']      = !empty($header['plugin_uri'])    ? $header['plugin_uri'] : '';
	$data['download_link']   = !empty($header['download_link']) ? $header['download_link'] : '';
	$data['donate_link']     = !empty($header['donate_link'])   ? $header['donate_link'] : '';
	$data['license']         = !empty($header['license'])       ? $header['license'] : '';
	$data['license_uri']     = !empty($header['license_uri'])   ? $header['license_uri'] : '';
	$data['homepage']        = !empty($header['plugin_uri'])    ? $header['plugin_uri'] : '';
	$data['last_updated']    = $endpoint[0]->post_modified;
	$data['added']           = $endpoint[0]->post_date;
	//$data['active_installs'] = 0; // Not currently viable.
	if (!empty($sections['reviews'])) {
		$data['ratings']     = get_plugin_ratings($reviews_raw);
		$data['num_ratings'] = array_sum($data['ratings']);
		$data['rating']      = get_plugin_ratings_score($data['ratings'], $data['num_ratings']);
	}
	$data['banners']         = get_plugin_banners($request);//['banner_urls'];//\CodePotent\UpdateManager\UpdateClient::get_plugin_images('banner', dirname($identifier));
	$data['icons']           = get_plugin_icons($endpoint, $identifier);//UpdateClient::get_plugin_images('icon', dirname($identifier));
	$data['compatibility']   = [];
	if (!empty($header['requires'])) {
		$data['compatibility']   = [
			$header['requires'] => 100 // <------  k/v pair, not assignment
		];
	}

	// Capture $sections here at the end to keep them at the end of the array.
	$data['sections'] = $sections;

	// Phew!
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
	return strip_tags($value);

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
 * @param string $opinions
 * @return array
 */
function get_plugin_ratings($opinions) {

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
 * Get plugin score.
 *
 * The function calculates the plugin's user-rated score, up to 100.
 *
 * @author John Alarcon
 *
 * @since 1.0.0
 *
 * @param array $ratings
 * @param integer $total
 * @return integer
 */
function get_plugin_ratings_score($ratings, $total) {

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
		// Assign trimmed value back to orginal array.
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
			'donate link:', 'license:', 'license uri:',
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
// 	debug($lines);
// 	exit;
	// Iterate over remaining lines.
	foreach ($lines as $k=>$line) {
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

	// The long description array may have empty fields; capture the right one.
// 	foreach ($sections['description'] as $value) {
// 		if (!empty($value)) {
// 			$sections['description'] = [];
// 			$sections['description'][] = $value;
// 			break;
// 		}
// 	}

	// Remove any unexpected sections.
	foreach ($sections as $heading=>$content) {
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

	// Create a mail URL, if needed.
	$args = [];
	if (!empty($targets['email'])) {
		$subject = sprintf(
			'Pending Update Report: %s v%s',
			$header['name'],
			$header['version']);
		$lines = [];
		$lines[] = 'We greatly appreciate your help and feedback in testing the update to '.$header['name'].' version '.$header['version'].' — thanks!'."\r\n\r\n";
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

	$note1 = sprintf(
			esc_html__('%sCaution%s: This update is currently undergoing testing &#8211; it is not yet intended for production.', 'codepotent-update-manager'),
			'<strong>',
			'</strong>',
			'<em>'.$header['name'].'</em>');

	// Texts for pointing users toward notifying about issues.
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
function markup_plugin_generic_section($content) {

	// Initialization.
	$markup = '';

	// Now Parsedown gets a turn; and returns the final markup.
	$markup .= Parsedown::instance()->setBreaksEnabled(true)->text(implode("\r\n", $content));

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
function markup_plugin_description($description) {

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
function markup_plugin_reviews($reviews) {

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
function markup_plugin_screenshots($urls, $raw_captions) {

	// Initialization.
	$screenshots = '';

	// No URLs to work with? Bail.
	if (empty($urls)) {
		return $screenshots;
	}

	// Map any found captions to the received URLs.
	if (!empty($raw_captions)) {
		foreach ($raw_captions as $raw_caption) {
			if (!empty($raw_caption)) {
				$captions[(int)$raw_caption] = substr($raw_caption, strpos($raw_caption, ' ')+1);
			}
		}
	}
//debug($captions);exit;
	// List-item the screenshots.
	$screenshots = '<ol>'."\n";
	foreach ((array)$urls as $n=>$file) {
		$caption = !empty($captions[$n]) ? $captions[$n] : '';
		$screenshots .= '<li>';
		$screenshots .= '<img src="'.esc_url($file).'" alt="'.esc_attr($caption).'">';
		if ($caption) {
			$screenshots .= '<p>'.esc_html($caption).'</p>';
		}
		$screenshots .= '</li>'."\n";

	}
	$screenshots .= '</ol>'."\n";


	//debug(htmlentities($screenshots));exit;
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
function markup_plugin_upgrade_notice($notice) {

	if (!is_array($notice)) {
		return $notice;
	}
	foreach ($notice as $line) {
		if (!empty(trim($line))) {
			$notice = Parsedown::instance()->setBreaksEnabled(true)->line($line);
			break;
		}
	}

	// Return the markup string.
	return $notice;

}
