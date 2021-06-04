<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: Namespaced constants for ClassicPress plugins.
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

// Ensure needed functions are present.
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/plugin.php');

// -----------------------------------------------------------------------------
// Plugin basics.
// -----------------------------------------------------------------------------

// Ex: codepotent
const VENDOR_PREFIX = 'codepotent';
// Ex: Our company is cool!
define(__NAMESPACE__.'\VENDOR_TAGLINE', esc_html__('', 'codepotent-update-manager'));
// Ex: plugin-folder-name
const PLUGIN_SHORT_SLUG = 'update-manager';
// Ex: dashicons-whatever
const PLUGIN_MENU_ICON = null;
// Ex: 23
const PLUGIN_MENU_POS = null;
// Ex: Admin Menu Text
const PLUGIN_MENU_TEXT = null;
// Ex: plugin_folder_name
define(__NAMESPACE__.'\PLUGIN_PREFIX', VENDOR_PREFIX.'_'.str_replace('-', '_', PLUGIN_SHORT_SLUG));
// Ex: codepotent-my-plugin-name
const PLUGIN_SLUG = VENDOR_PREFIX.'-'.PLUGIN_SHORT_SLUG;
// Ex: vendor-plugin-name
const PLUGIN_DIRNAME = PLUGIN_SLUG;
// Ex: vendor-plugin-name.php
const PLUGIN_FILENAME = PLUGIN_DIRNAME.'.php';
// Ex: vendor-plugin-name/vendor-plugin-name.php
const PLUGIN_IDENTIFIER = PLUGIN_DIRNAME.'/'.PLUGIN_FILENAME;
// Ex: /site/wp-content/plugins/vendor-plugin-name/vendor-plugin-name.php
const PLUGIN_FILEPATH = WP_PLUGIN_DIR.'/'.PLUGIN_IDENTIFIER;
// Ex: vendor_plugin_name_settings
const PLUGIN_SETTINGS_VAR = PLUGIN_PREFIX.'_settings';
// Get plugin data from header file.
$plugin = get_plugin_data(PLUGIN_FILEPATH, false, false);
// Ex: My Plugin Name
define(__NAMESPACE__.'\PLUGIN_NAME', $plugin['Name']);
// Ex: Some plugin description
define(__NAMESPACE__.'\PLUGIN_DESCRIPTION', $plugin['Description']);
// Ex: 1.2.3
define(__NAMESPACE__.'\PLUGIN_VERSION', $plugin['Version']);
// Ex: Code Potent
define(__NAMESPACE__.'\PLUGIN_AUTHOR', $plugin['AuthorName']);
// Ex: https://codepotent.com
define(__NAMESPACE__.'\PLUGIN_AUTHOR_URL', $plugin['AuthorURI']);
// Ex: https://codepotent.com/classicpress/plugins/
define(__NAMESPACE__.'\PLUGIN_URL', $plugin['PluginURI']);

// -----------------------------------------------------------------------------
// Custom post type and JSON identifiers.
// -----------------------------------------------------------------------------

// Primary query variable for the JSON endpoints.
const ENDPOINT_VARIABLE = 'update';
// Custom post type for plugin update endpoints.
const CPT_FOR_PLUGIN_ENDPOINTS = 'plugin_endpoint';
// Custom post type for theme update endpoints.
const CPT_FOR_THEME_ENDPOINTS = 'theme_endpoint';

// -----------------------------------------------------------------------------
// Plugin paths and URLs
// -----------------------------------------------------------------------------

// Ex: /home/user/mysite
define(__NAMESPACE__.'\PATH_HOME', untrailingslashit(get_home_path()));
// Ex: /home/user/mysite/wp-admin
const PATH_ADMIN = PATH_HOME.'/wp-admin';
// Ex: /home/user/mysite/wp-content/plugins
define(__NAMESPACE__.'\PATH_PLUGINS', untrailingslashit(plugin_dir_path(dirname(dirname(__FILE__)))));
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name
const PATH_SELF = PATH_PLUGINS.'/'.PLUGIN_SLUG;
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/classes
const PATH_CLASSES = PATH_SELF.'/classes';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/endpoints
const PATH_ENDPOINTS = PATH_SELF.'/endpoints';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/extensions
const PATH_EXTENSIONS = PATH_SELF.'/extensions';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/fonts
const PATH_FONTS = PATH_SELF.'/fonts';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/images
const PATH_IMAGES = PATH_SELF.'/images';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/includes
const PATH_INCLUDES = PATH_SELF.'/includes';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/languages
const PATH_LANGUAGES = PATH_SELF.'/languages';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/scripts
const PATH_SCRIPTS = PATH_SELF.'/scripts';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/styles
const PATH_STYLES = PATH_SELF.'/styles';
// Ex: /home/user/mysite/wp-content/plugins/my-plugin-name/templates
const PATH_TEMPLATES = PATH_SELF.'/templates';
// Ex: https://mysite.com
define(__NAMESPACE__.'\URL_HOME', untrailingslashit(home_url()));
// Ex: https://mysite.com/wp-admin
const URL_ADMIN = URL_HOME.'/wp-admin';
// Ex: https://mysite.com/wp-content/plugins
define(__NAMESPACE__.'\URL_PLUGINS', plugins_url());
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name
const URL_SELF = URL_PLUGINS.'/'.PLUGIN_SLUG;
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/endpoints
const URL_ENDPOINTS = URL_SELF.'/endpoints';
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/extensions
const URL_EXTENSIONS = URL_SELF.'/extensions';
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/fonts
const URL_FONTS = URL_SELF.'/fonts';
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/images
const URL_IMAGES = URL_SELF.'/images';
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/scripts
const URL_SCRIPTS = URL_SELF.'/scripts';
// Ex: https://mysite.com/wp-content/plugins/my-plugin-name/styles
const URL_STYLES = URL_SELF.'/styles';