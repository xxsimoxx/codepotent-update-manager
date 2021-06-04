/**
 * -----------------------------------------------------------------------------
 * Purpose: JavaScript for Update Manager's CPT edit screens.
 * Package: CodePotent\UpdateManager
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright 2021, John Alarcon (Code Potent)
 * -----------------------------------------------------------------------------
 */

// Wait until the page has loaded.
jQuery(document).ready(function($) {

	// Disable save buttons as a default state.
	if ($('#codepotent-update-manager-identifier').val() === '') {
		$('input#save-post').prop('disabled', true);
		$('input#publish').prop('disabled', true);
	}

	// Enable save buttons only when identifier has a value (even if invalid).
	$('#codepotent-update-manager-identifier').keyup(function () {
		if ($(this).val() === '') {
			$('input#save-post').prop('disabled', true);
			$('input#publish').prop('disabled', true);
		} else {
			$('input#save-post').prop('disabled', false);
			$('input#publish').prop('disabled', false);
		}
	});

	// Prevent enter-key saves.
	$('form#post').keydown(function (e) {
		if (e.keyCode == 13) {
			if ($('#codepotent-update-manager-identifier').val() === '') {
				e.preventDefault();
				return false;
			}
		}
	});

	$('#submitpost input').mouseover(function(e) {
		if ($(this)[0]['disabled']) {
			alert(endpoint_notice);
		}
	});

	// Click handler for inserting template text via button.
	$('#'+slug+'-autocompleters button').click(function (e) {
		// Prevent default link behavior.
		e.preventDefault();
		// Replacement cannot be undone; have the user confirm.
		var ok = confirm(confirmation);
   		if (!ok) {
   			return
   		};
   		// Get the template text.
		if ($(this).hasClass('without-examples')) {
			var value = get_default_text($(this)[0]['dataset']['component']);
		}
		// Place the text in the editor.
		$('#'+slug+'-editor').val(value)
		// Nothing to see here; move along.
		return false;
	});

	// Click handler for inserting template text via link.
	$('#'+slug+'-autocompleters a').click(function (e) {
		// Prevent default link behavior.
		e.preventDefault();
		// Replacement cannot be undone; have the user confirm.
		var ok = confirm(confirmation);
		if (!ok) {
			return
		};
		if ($(this).hasClass('reqs-only')) {
			var value = get_default_text_requirements(this.dataset.component);
		} else if ($(this).hasClass('with-examples')) {
			var value = get_default_text_with_example_data(this.dataset.component);
		}
		// Place the text in the editor.
		$('#'+slug+'-editor').val(value)
		// Nothing to see here; move along.
		return false;
	});

	// Click handler for toggling the cheat sheet.
	$('#codepotent-update-manager-toggle-cheat-sheet').click(function(e) {
		// Prevent default link behavior.
		e.preventDefault();
		$('#codepotent-update-manager-cheat-sheet').toggle('slow', function(){});
	});

	// Return only the bare minimum requirements.
	function get_default_text_requirements(component) {
		var text = '';
		text += '=== '+component.charAt(0).toUpperCase()+component.slice(1)+' Name ===\n\n';
		text += 'Version:           1.0.0\n';
		text += 'Requires:          1.0.0\n';
		text += 'Download link:     https://\n\n';
		text += '== Description ==\n\n';
		text += 'This text displays in the modal windows; it is required. Write something!\n\n';
		return text;
	}

	// Unpopulated text template.
	function get_default_text(component) {
		var text = '';
		text += '=== '+component.charAt(0).toUpperCase()+component.slice(1)+' Name ===\n\n';
		text += 'Description:       \n';
		text += 'Version:           \n';
		text += 'Text Domain:       \n';
		text += 'Domain Path:       \n';
		text += 'Requires PHP:      \n';
		text += 'Requires:          \n';
		text += 'Tested:            4.9.99\n';
		text += 'Author:            \n';
		text += 'Author URI:        \n';
		if (component === 'plugin') {
			text += 'Plugin URI:        \n';
		} else {
			text += 'Theme URI:         \n';
		}
		text += 'Download link:     \n';
		text += 'Donate link:       \n';
		text += 'License:           \n';
		text += 'License URI:       \n\n';
		text += 'This is the short description and consists of a few sentences under 150 characters in all.\n\n';
		text += '== Description ==\n\n';
		text += '# About the '+component.charAt(0).toUpperCase()+component.slice(1)+'\n\n';
		if (component === 'plugin') {
			text += '== Screenshots ==\n\n';
			text += '# Screenshots\n\n';
			text += '== Reviews ==\n\n';
			text += '# User Reviews\n\n';
			text += '== Frequently Asked Questions ==\n\n';
			text += '# Frequently Asked Questions\n\n';
			text += '== Installation ==\n\n';
			text += '# Installation\n\n';
			text += '== Other Notes ==\n\n';
			text += '# About the Developer\n\n';
			text += '== Changelog ==\n\n';
			text += '# Changelog\n\n';
			text += '== Upgrade Notice ==\n\n';
			text += '# Upgrade Notice\n\n';
			text += 'Keeping up with updates is a great idea!';
		}

		return text;

	}

	// Prepopulated text template.
	function get_default_text_with_example_data(component) {
		var text = '';
		text += '=== '+component.charAt(0).toUpperCase()+component.slice(1)+' Name ===\n\n';
		text += 'Description:       A succinct description of the '+component+'. Text added here must be a single, unbroken line.\n';
		text += 'Version:           1.0.0\n';
		text += 'Text Domain:       '+component+'-folder-name\n';
		text += 'Domain Path:       /languages\n';
		text += 'Requires PHP:      5.6\n';
		text += 'Requires:          1.0.0\n';
		text += 'Tested:            4.9.99\n';
		text += 'Author:            Code Potent\n';
		text += 'Author URI:        https://codepotent.com\n';
		if (component === 'plugin') {
			text += 'Plugin URI:        https://codepotent.com/classicpress/plugins/plugin-name\n';
		} else {
			text += 'Theme URI:         https://codepotent.com/classicpress/themes/theme-name/\n';
		}
		text += 'Download link:     #\n';
		text += 'Donate link:       #\n';
		text += 'License:           GPLv2\n';
		text += 'License URI:       https://www.gnu.org/licenses/gpl-2.0.html\n\n';
		text += 'A succinct description of the '+component+'. Text added here must be a single, unbroken line.\n\n';
		text += '== Description ==\n\n';
		text += '# Sell the _sizzle_, not the _steak_!\n\n';
		text += 'Use this section to describe the top features and benefits of your '+component+'. Avoid the temptation of placing every last detail here. Give your reader a reason to click click through and learn more.\n\n';
		text += '# Give your '+component+' details some flair!\n\n';
		text += 'You can easily break up your sections with headings and paragraphs using _markdown_. Of course, you can also make lists, [links](https://codepotent.com), and _formatted_ **text**. And, _yes_, you can include images, too!\n\n';
		if (component === 'plugin') {
			text += '# Need help?\n\n';
			text += 'The official [online documentation](https://codepotent.com/classicpress/plugins/) is the best source for immediate answers. You may also find helpful information and/or user-to-user support at the official ClassicPress [support forums](https://forums.classicpress.net/c/plugins/plugin-support/67).\n\n';
			text += '== Screenshots ==\n\n';
			text += '# Screenshots\n\n';
			text += '1. **An Image Caption** An image description.\n';
			text += '2. **An Image Caption** An image description.\n';
			text += '3. **An Image Caption** An image description.\n';
			text += '\n';
			text += '== Reviews ==\n\n';
			text += '# User Reviews\n\n';
			text += '*****\n';
			text += '"Adding stars and reviews is easy. Make sure to add quotes only around the text of the review!"\n';
			text += '~ **John Alarcon**, _Plugin Developer_\n';
			text += '[codepotent.com](https://codepotent.com)\n\n';
			text += '*****\n';
			text += '"Adding stars and reviews is easy. Make sure to add quotes only around the text of the review!"\n';
			text += '~ **John Alarcon**, _Plugin Developer_\n';
			text += '[codepotent.com](https://codepotent.com)\n\n';
			text += '*****\n';
			text += '"Adding stars and reviews is easy. Make sure to add quotes only around the text of the review!"\n';
			text += '~ **John Alarcon**, _Plugin Developer_\n';
			text += '[codepotent.com](https://codepotent.com)\n\n';
			text += '== Frequently Asked Questions ==\n\n';
			text += '# Frequently Asked Questions\n\n';
			text += '**Got more questions?** Check the [documentation](https://codepotent.com/classicpress/plugins/) and [support forums](https://forums.classicpress.net/c/plugins/plugin-support/67).\n\n';
			text += '### Is this a question?\n';
			text += 'Why, yes, it appears to be!\n\n';
			text += '### How about this??\n';
			text += 'Yes, it, too, appears to be a question.\n\n';
			text += '### What\'s with all the commas?\n';
			text += 'What\'s with all the questions?\n\n';
			text += '== Installation ==\n\n';
			text += '# Standard Installation\n\n';
			text += '- **Login** to your ClassicPress website\n';
			text += '- **Navigate** to `Dashboard > Plugins > Add New`\n';
			text += '- **Search** for the plugin by name and find it in the results\n';
			text += '- Click to **Install** the plugin\n';
			text += '- Click to **Activate** the plugin\n\n';
			text += '# Manual Installation\n\n';
			text += '- **Download** the zip file to your local computer\n';
			text += '- **Login** to your ClassicPress website.\n';
			text += '- **Navigate** to `Dashboard > Plugins > Add New > Upload Plugin`\n';
			text += '- **Upload** the zip file to your site\n';
			text += '- Click to **Install** the plugin\n';
			text += '- Click to **Activate** the plugin\n\n';
			text += '== Other Notes ==\n\n';
			text += '# About Code Potent\n\n';
			text += '**Code Potent has blazed a trail in supporting ClassicPress!** Creating free [plugins](https://codepotent.com/classicpress/plugins), writing informative [articles](https://codepotent.com/classicpress/) and handy [tutorials](https://codepotent.com/tutorials/), making substantial [donations](https://www.classicpress.net/donate/), moderating and helping others on the [support forums](https://forums.classicpress.net/u/CodePotent/summary), serving on the [Management Committee](https://forums.classicpress.net/t/introducing-the-2020-classicpress-management-committee/1814?u=codepotent) – Code Potent is highly engaged with the ClassicPress project and community. When you need well-defined, high value solutions for your business, check out Code Potent!\n\n';
			text += '== Changelog ==\n\n';
			text += '# Changelog\n\n';
			text += '### [Version 1.0.0](https://)\n';
			text += '**JAN-07-2020**\n';
			text += ' 1. Initial release \n';
			text += ' 1. Fix \n';
			text += ' 1. Add \n';
			text += ' 1. Remove \n';
			text += ' 1. Replace \n';
			text += '\n';
			text += '== Upgrade Notice ==\n\n';
			text += '# Upgrade Notice\n\n';
			text += 'This notice is displayed on the updates page and in the modal windows as a tabbed view.\n\n';
		}
		return text;
	}

});