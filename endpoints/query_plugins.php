<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: JSON endpoint template when querying for plugin updates.
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
 * Copyright © 2019 - Code Potent
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

// Query for plugin updates.
$data = query_plugins();

// Prevent caching.
nocache_headers();

// Set HTTP headers.
header('Content-Type: application/json; charset=utf-8');
header('Expires: 0');

// Output JSON.
echo json_encode($data);

// Bail.
exit;