<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: JSON endpoint template when querying for single plugins or themes.
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

// Query for component details.
$data = component_information('plugin');

// Prevent caching.
nocache_headers();

// Set HTTP headers.
header('Content-Type: application/json; charset=utf-8');
header('Expires: 0');

// Output JSON.
echo wp_json_encode($data);

// Bail.
exit;