> Painlessly push updates to your ClassicPress plugin and theme users! Serve updates from GitHub, your own site, or somewhere in the cloud. 100% integrated with the ClassicPress update process; super-slim and performant. Incredibly easy!

With the Update Manager plugin for ClassicPress, developers can push plugin and theme updates out to their end users with ease! This isn't a fork of a WordPress plugin – this is a brand new plugin built specifically for ClassicPress!

## Remote Updates made Easy

No matter where you host your plugin or theme code – GitHub, BitBucket, AWS, or even your own website – the Update Manager plugin makes quick work of pushing updates to your end users. A fantastic tool for plugin and theme developers, agencies, and freelancers!

## Simple & Lightweight

The Update Manager only requires that you add a single file to your plugin or theme – and it's under 30k! There's no libraries to think about. There's no huge files to add bloat. There's no complicated steps to trip you up! And, _best of all_, your users don't have to install an extra plugin just to deal with updates.

## Complete Integration

The Update Manager plugin is fully integrated with ClassicPress' core update process. Adding your own icons, banners, and screenshots is as simple as dropping your images into the right directory. When your users click for information about your plugin in their dashboard, the popup windows look fantastic, complete with details, imagery, changelog, reviews...and more! Themes look great, too!


---

## Plugin Integration

To setup a plugin to work with the Update Manager, the following general steps are recommended. This page details each of the steps and describes how to use the plugin going forward.

1. Install and activate the Update Manager plugin on your own site.
1. Add the Update Client file to your own plugin.
1. Create an update endpoint in the Update Manager.
1. Test the endpoint in a browser.
1. Test the update on an actual site.
1. Publish the endpoint.

### Install the Update Manager Plugin

1. Install the Update Manager plugin according to the <a href="#install-manual">installation instructions</a>.
1. Navigate to `Dashboard > Update Manager > Plugins > Add New`  – this is where you will be managing your plugin updates.

### Adding the Update Client file to a Plugin

1. Copy the `UpdateClient.class.php` file (from the Update Manager plugin) into your plugin's file structure.
1. Set `namespace` to a unique value; line 22.
1. Set `UPDATE_SERVER` to the URL where your _Update Manager_ plugin is installed; line 25.
1. Set `USE_DIRECTORY` to `true` to skip loading the class starting from ClassicPress v.2; line 30.
1. Set `SECURE_SOURCE` to the starting part of the URL of the updated zip file if you want to add an extra layer of security; line 35. See line 236 to understand how check is done.
1. Set `UPDATE_TYPE` to `plugin` or `theme`; line 38.
1. Use `require_once('/path/to/UpdateClient.class.php')` in your plugin's primary PHP file to run the update client.

### Creating an Update Endpoint for a Plugin

1. Navigate to `Dashboard > Update Manager > Plugins > Add New`
1. Set the **Endpoint Identifier** for your plugin; ie, `my-plugin-folder/my-plugin-file.php`.
1. Add your **Plugin Details** to the editor; if you have a readme.txt file, you can copy it right in.
1. Optionally, add URLs for any **Testing Domains** that can receive test updates.
1. Optionally, add contact methods for receiving **Notifications** of issues.
1. Click **Publish** to make the update immediately available.

### Viewing an Update Endpoint in the Browser
If your update endpoint isn't working as expected, neither will your update. So, before even testing the update process, it's a good idea to call up the endpoint in a browser and verify that it looks like valid JSON data. The following steps will allow you to view endpoints in the browser. This is not available for endpoints in `Draft` status.

1. Navigate to `Dashboard > Update Manager > Plugins`
1. Hover over any of the listed endpoints and click **View Endpoint**.

### Pushing an Update to all Plugin Users

When you create a new version of your plugin, you'll need to push it out to your end users. To do this:

1. Navigate to `Dashboard > Update Manager > Plugins`
1. Click to **Edit** the particular endpoint.
1. Update the **Plugin Details** section to reflect the new version's details.
1. Click to **Update** the endpoint.

### Pushing an Update to Limited Users for Testing Purposes

1. Navigate to `Dashboard > Update Manager > Plugins`
1. Click to **Edit** an endpoint.
1. Set the **Status** to _Pending Review_.
1. Click to **Update** the endpoint.

With the plugin in _Pending Review_ status, the update will be made available only to those domains specified in the **Testing Domains** field. Note that if you are trying to test an update on a plugin that is installed on the same server as the Update Manager plugin, _you must still whitelist the domain_, otherwise it will not receive the update notification.

### Promoting Feedback from your Test Team
1. Navigate to `Dashboard > Update Manager > Plugins`
1. Click to **Edit** an endpoint.
1. Add one or more contact methods to the **Notifications** field.
1. Click to **Update** the endpoint.

This field accepts multiple email addresses and a _single_ URL, separated by commas. The order is not important. If you supply email addresses, a link will be crafted that prepopulates an email with some basic information. If you supply a URL, it will be used to point the user toward a feedback form, GitHub repo, or wherever. If you supply email(s) _and_ URL, both options will be offered. These links will appear in the test notice that appears in the modal windows.

### Pushing Alpha, Beta, and Release Candidate Versions
Semantic versioning is used throughout. You can push updates for alpha, beta, and release candidate versions without any trouble. Let's say you wanted to run through the basic steps with a version 1.0.0 plugin. You might release a plugin at 1.0.0-alpha. Next, you could bump the version to 1.0.0-beta... and then 1.0.0-rc1, and then 1.0.0-rc2, and then 1.0.0. Each version in that chain succeeds the previous. Here's the upgrade path, a bit more visually.

> **1.0.0-alpha**&nbsp;  < &nbsp; **1.0.0-beta**&nbsp;  < &nbsp; **1.0.0-rc1**&nbsp;  < &nbsp; **1.0.0-rc2**&nbsp;  < &nbsp; **1.0.0**

**Supplemental Note**: You can also push sequential updates for a given pre-release (alpha, beta, or RC version). For example, _1.0.0-alpha1_ is less than _1.0.0-alpha2_ which is less than _1.0.0-alpha3_, which is less than...well, you get the idea.

### Using Custom Images

You can use your own plugin banner and icon images to improve the end user experience. If you have these images, create an `/images/` directory in your plugin and drop them there. They will be automatically discovered and you are free to mix and match any of the following filetypes. If you prefer, you can define your own custom image path; see the <a href="#docs-filters">filters</a> documentation. If you do not have such images, the default system image will be used for the icon, and your plugin modal windows just won't have a header graphic. The following are supported:

#### Normal Images
**SVG**: `icon.svg`, `banner.svg`
**PNG**: `icon-128.png`, `banner-772x250.png`
**JPG**: `icon-128.jpg`, `banner-772x250.jpg`

#### Retina Images
**SVG**: `icon.svg`, `banner.svg`
**PNG**: `icon-256.png`, `banner-1544x500.png`
**JPG**: `icon-256.jpg`, `banner-1544x500.jpg`

#### Screenshots
**PNG**: `screenshot-1.png`, `screenshot-2..png`, ...
**JPG**: `screenshot-1.jpg`, `screenshot-2.jpg`, ...

**Note**: Screenshots are displayed from lowest to highest, however, they need not be sequentially named. The plugin will find them regardless and will apply the correct caption(s) to the correct image(s).

## Theme Integration

To setup a theme to work with the Update Manager, the following general steps are recommended. This page details each of the steps and describes how to use the plugin going forward.

1. Install and activate the Update Manager plugin on your own site.
1. Add the Update Client file to your own theme.
1. Create an update endpoint in the Update Manager.
1. Test the endpoint in a browser.
1. Test the update on an actual site.
1. Publish the endpoint.

### Install the Update Manager Plugin

1. Install the Update Manager plugin according to the <a href="#install-manual">installation instructions</a>.
1. Navigate to `Dashboard > Update Manager > Themes > Add New`  – this is where you will be managing your theme updates.

### Adding the Update Client file to a Theme

1. Copy the UpdateClient.class.php file (from the Update Manager plugin) into your theme's file structure.
1. Set `namespace` to a unique value; line 25.
1. Set `UPDATE_SERVER` to the URL where your _Update Manager_ plugin is installed; line 28.
1. Set `UPDATE_TYPE` to 'theme'; line 31.
1. Use `require_once('/path/to/UpdateClient.class.php')` in your theme's `functions.php` file to run the update client.

### Creating an Update Endpoint for a Theme

1. Navigate to `Dashboard > Update Manager > Themes > Add New`
1. Set the **Endpoint Identifier** for your theme; ie, `my-theme-directory-name`.
1. Add your **Theme Details** to the editor; if you have a readme.txt file, you can copy it right in.
1. Optionally, add URLs for any **Testing Domains** that can receive test updates.
1. Optionally, add contact methods for receiving **Notifications** of issues.
1. Click **Publish** to make the update immediately available.

### Viewing an Update Endpoint in the Browser
If your update endpoint isn't working as expected, neither will your update. So, before even testing the update process, it's a good idea to call up the endpoint in a browser and verify that it looks like valid JSON data. The following steps will allow you to view endpoints in the browser. This is not available for endpoints in `Draft` status.

1. Navigate to `Dashboard > Update Manager > Themes`
1. Hover over any of the listed endpoints and click **View Endpoint**.

### Pushing an Update to all Theme Users

When you create a new version of your theme, you'll need to push it out to your end users. To do this:

1. Navigate to `Dashboard > Update Manager > Themes`
1. Click to **Edit** the particular endpoint.
1. Update the **Theme Details** section to reflect the new version's details.
1. Click to **Update** the endpoint.

### Pushing an Update to Limited Users for Testing Purposes

1. Navigate to `Dashboard > Update Manager > Themes`
1. Click to **Edit** an endpoint.
1. Set the **Status** to _Pending Review_.
1. Click to **Update** the endpoint.

With the theme in _Pending Review_ status, the update will be made available only to those domains specified in the **Testing Domains** field. Note that if you are trying to test an update on a theme that is installed on the same server as the Update Manager plugin, _you must still whitelist the domain_, otherwise it will not receive the update notification.

### Promoting Feedback from your Test Team
1. Navigate to `Dashboard > Update Manager > Themes`
1. Click to **Edit** an endpoint.
1. Add one or more contact methods to the **Notifications** field.
1. Click to **Update** the endpoint.

This field accepts multiple email addresses and a _single_ URL, separated by commas. The order is not important. If you supply email addresses, a link will be crafted that prepopulates an email with some basic information. If you supply a URL, it will be used to point the user toward a feedback form, GitHub repo, or wherever. If you supply email(s) _and_ URL, both options will be offered. These links will appear in the test notice that appears in the modal windows.

### Pushing Alpha, Beta, and Release Candidate Versions
Semantic versioning is used throughout. You can push updates for alpha, beta, and release candidate versions without any trouble. Let's say you wanted to run through the basic steps with a version 1.0.0 theme. You might release a theme at 1.0.0-alpha. Next, you could bump the version to 1.0.0-beta... and then 1.0.0-rc1, and then 1.0.0-rc2, and then 1.0.0. Each version in that chain succeeds the previous. Here's the upgrade path, a bit more visually.

> **1.0.0-alpha**&nbsp;  < &nbsp; **1.0.0-beta**&nbsp;  < &nbsp; **1.0.0-rc1**&nbsp;  < &nbsp; **1.0.0-rc2**&nbsp;  < &nbsp; **1.0.0**

**Supplemental Note**: You can also push sequential updates for a given pre-release (alpha, beta, or RC version). For example, _1.0.0-alpha1_ is less than _1.0.0-alpha2_ which is less than _1.0.0-alpha3_, which is less than...well, you get the idea.

## Transient Inspector

`Dashboard > Update Manager > Transients`

The Update Manager plugin contains functionality that allows you to view and delete the transients related to plugin and theme updates. This functionality provides a reliable method of forcing an update check, regardless of when the last check took place. Note that this functionality reveals all update data, so, even if a plugin or theme is not integrated with the Update Manager plugin, its update data can be viewed (and deleted) here.

### Inspecting Transient Data
Clicking the `Plugin Update Data` or `Theme Update Data` tabs will reveal the data stored in your database related to plugin and theme updates. The data is only presented for informational purposes and is not editable on these screens.

### Previewing Updates
Clicking the `Update Data` tab will show plugin and theme update information on the same screen and in a more reader-friendly format.

### Delete Plugin Transients
Clicking the text link to delete **Plugin** transients will delete the data stored in the options table as `_site_transient_update_plugins`. With this transient data deleted, the system will check for plugin updates on the next page load regardless of when the last update check took place.

### Delete Theme Transients
Clicking the text link to delete **Theme** transients will delete the data stored in the options table as `_site_transient_update_themes`. With this transient data deleted, the system will check for theme updates on the next page load regardless of when the last update check took place.

### Delete Both Transients
Clicking the text link to delete **Both** transients will delete the data stored in the options table for both `_site_transient_update_plugins` and `_site_transient_update_themes`. With this transient data deleted, the system will check for both plugin and theme updates on the next page load regardless of when the last update took place.

---

### Processing Incoming Requests
To work with the data received from incoming requests, the following filter can be added to a utility plugin. This might be used for tracking installations or adding/removing values to/from the request _before_ querying the endpoint for data. This filter accepts 1 argument, `$request`. Replace `{component-type}` with either `plugin` or `theme`, depending on the type of request you are trying to filter.

<pre>function some_function_name($request) {

     // Do your processing here.

    // Return the processed request.
    return $request;
}
add_filter('codepotent_update_manager_filter_{component-type}_request', 'some_function_name');
</pre>

---
### Processing Outgoing Responses
When a request is received by the Update Manager plugin, the relevant plugin (or theme) data is assembled and passed back as a response to the calling site. This filter allows final processing of that data before it is returned to the calling site. This filter accepts 2 arguments, `data` and `request` and returns the possible-amended `data`.

<pre>function some_function_name($data, $request) {

     // Do your processing on $data here.

    // Return the processed data.
    return $data;
}
add_filter('codepotent_update_manager_filter_parsed_component_data', 'some_function_name', 10, 2);
</pre>

---
### Active Installations
The Update Manager plugin does not track or know how many active installations there are of any given plugin. If you are otherwise tracking those numbers, you can filter them into the remote modal windows by adding the following filter to a utility plugin. The filter accepts only uses the first of the two arguments, `$number`. Replace `{identifier}` with your plugin identifier, ie, `my-plugin-dir/my-plugin-file.php`.

<pre>function some_function_name($number, $identifier) {
    return $number;
}
add_filter('codepotent_update_manager_{identifier}_active_installs', 'some_function_name', 10, 2);
</pre>

---
### Admin Menu
Position To change the admin menu item's position, this filter can be added to a utility plugin. See also the following table of menu position values.

<pre>function some_function_name($position) {
    return 22;
}
add_filter('codepotent_update_manager_menu_pos', 'some_function_name');
</pre>

**Menu Item Positions** **1**: _Top_ of menu **2**: below _Dashboard_ **5**: below _Posts_ **10**: below _Media_ **20**: below _Pages_ **25**: below _Comments_ **60**: below _first separator_ **65**: below _Plugins_ **70**: below _Users_ **75**: below _Tools_ **80**: below _Settings_ **100**: below _second separator_ **null**: below _Comments_, in natural order; default

---
### <a name="docs-filters">Plugin Images Path & URL</a>
These filters can be used if you already have a particular directory schema under which your plugin's images are stored. These filters would be used in your own plugin.

#### Plugin-specific filters
Those new filters prevents plugins from accidentally overwrite other's path.

<pre>function new_custom_image_path($path) {
	return plugin_dir_path(__FILE__).'beautifulimages';
}
add_filter('codepotent_update_manager_{plugin-slug}_image_path', 'new_custom_image_path');

function new_custom_image_url($url) {
	return plugin_dir_url(__FILE__).'beautifulimages';
}
add_filter('codepotent_update_manager_{plugin-slug}_image_url', 'new_custom_image_url');
</pre>

#### Global filters (deprecated since version 2.3.0)
Note that you should always check for the right plugin slug before altering the path, as shown in the examples here. Be sure to replace `{plugin-slug}` with the slug of your own plugin.

<pre>function my_custom_image_path($path) {
    if (strpos($path, {plugin-slug}) !== false) {
        $path = '/path/to/your-site/wp-content/plugins/your-plugin-name/assets/images';
    }
    return $path;
}
add_filter('codepotent_update_manager_image_path', 'my_custom_image_path');

function my_custom_image_url($url) {
    if (strpos($path, {plugin-slug}) !== false) {
        $path = 'https://yoursite.com/wp-content/plugins/your-plugin-name/assets/images';
    }
    return $path;
}
add_filter('codepotent_update_manager_image_url', 'my_custom_image_url');
</pre>

---
### Filter Notification
Email Properties If you are using the notification features for endpoints to allow your testers to contact you with feedback, you can filter the default subject and body of the email message with the following filters.

<pre>function some_function_name($subject) {
    return 'Some custom subject line';
}
add_filter('codepotent_update_manager_notification_email_subject', 'some_function_name');

function some_function_name($body) {
    $body = 'A line followed by two blank lines.'."\n\n";
    $body .= 'Another line...';
    return $body;
}
add_filter('codepotent_update_manager_notification_email_body', 'some_function_name');
</pre>

---

### Footer Credit
For extension authors, this filter allows for a credit link to be appended to the Code Potent footer text. This filter accepts a single argument, `$text`, which is an empty string, by default. Note that all HTML is stripped, except for the <a> tag. The URLs can be as long as needed, but, the visible text and links may be truncated at 50 characters. This filter is for adding a credit link, not for marketing text and upsells; misusing this feature will cause it to be removed.

<pre>function some_function_name($text) {
    return 'Featuring &lt;a href=&quot;#&quot;&gt;My Extension&lt;/a&gt; by &lt;a href=&quot;#&quot;&gt;Author Name&lt;/a&gt;';
}
add_filter('codepotent_update_manager_extension_footer_{your-slug-here}', 'some_function_name');
</pre>

---


### Request body
This filter allows to add fields to the request made by the `UpdateClient.class.php`. It's useful for plugin authors that wants to pass data to some Update Manager extension. For example Stats for Update Manager uses it to allow plugin authors to give their users the choice to opt-in or out from their site being counted usage in statistics.

<pre>function some_function_name($body) {
	if( 'no' === get_option( 'my-slug-usage-statistics' ) ) {
		$body['sfum'] = 'no-log';
	}
	return $body;
}
add_filter('codepotent_update_manager_filter_{your-slug-here}_client_request', 'some_function_name');
</pre>

---
### Manual Installation <a name="install-manual"></a>

- **Download** the zip file to your local computer
- **Login** to your ClassicPress website.
- **Navigate** to `Dashboard > Plugins > Add New > Upload Plugin`
- **Upload** the zip file to your site
- Click to **Install** the plugin
- Click to **Activate** the plugin
