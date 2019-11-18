/**
 * -----------------------------------------------------------------------------
 * Purpose: JavaScript for the plugin's post-edit screen.
 * Package: CodePotent\UpdateManager
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright © 2019 CodePotent
 * -----------------------------------------------------------------------------
 *           ____          _      ____       _             _
 *          / ___|___   __| | ___|  _ \ ___ | |_ ___ _ __ | |_
 *         | |   / _ \ / _` |/ _ \ |_) / _ \| __/ _ \ '_ \| __|
 *         | |__| (_) | (_| |  __/  __/ (_) | ||  __/ | | | |_
 *          \____\___/ \__,_|\___|_|   \___/ \__\___|_| |_|\__|.com
 *
 * -----------------------------------------------------------------------------
 */

// Wait until the page has loaded.
jQuery(document).ready(function($) {
	
	// Click handler for inserting template text.
	$('#'+slug+'-autocompleters button').click(function (e) {
		// Prevent default link behavior.
		e.preventDefault();
		// Replacement cannot be undone; have the user confirm.
		var ok = confirm(confirmation);
		if (!ok) return;
		// Get the template text.
		if ($(this).hasClass('without-examples')) {
			// Unpopulated template.
			var value = get_default_text();
		} else if ($(this).hasClass('with-examples')) {
			// Prepopulated template.
			var value = get_default_text_with_example_data();
		} else if ($(this).hasClass('reqs-only')) {
			// Prepopulated template.
			var value = get_default_text_with_example_data();
		}
		// Place the text in the editor.
		$('#'+slug+'-editor').val(value)
		// Nothing to see here; move along.
		return false;
	});
	
	// Click handler for inserting template text.
	$('#'+slug+'-autocompleters a').click(function (e) {
		// Prevent default link behavior.
		e.preventDefault();
		// Replacement cannot be undone; have the user confirm.
		var ok = confirm(confirmation);
		if (!ok) return;
		if ($(this).hasClass('reqs-only')) {
			var value = get_default_text_requirements();
		} else if ($(this).hasClass('with-examples')) {
			var value = get_default_text_with_example_data();
		}
		// Place the text in the editor.
		$('#'+slug+'-editor').val(value)
		// Nothing to see here; move along.
		return false;
	});
	
	// Return only the bare requirements.
	function get_default_text_requirements() {
		var text = '';
		text += '=== PluginNameHere ===\n';
		text += '\n';
		text += 'Version:           x.x.x\n';
		text += 'Requires:          x.x.x\n';
		text += 'Download link:     https://\n';
		text += '\n';
		text += '== Description ==\n';
		text += '\n';
		text += 'This text displays in the modal windows; it is required. Write something!\n';
		return text;
	}
	
	// Unpopulated text template.
	function get_default_text() {
		var text = '';
		text += '=== PluginNameHere ===\n';
		text += '\n';
		text += 'Description:       \n';
		text += 'Version:           \n';
		text += 'Text Domain:       \n';
		text += 'Domain Path:       \n';
		text += 'Requires PHP:      \n';
		text += 'Requires:          \n';
		text += 'Tested:            4.9.99\n';
		text += 'Author:            \n';
		text += 'Author URI:        \n';
		text += 'Plugin URI:        \n';
		text += 'Download link:     \n';
		text += 'Donate link:       \n';
		text += 'License:           \n';
		text += 'License URI:       \n';
		text += '\n';
		text += 'This is the short description and consists of a few sentences under 150 characters in all.\n\n';
		text += '== Description ==\n\n';
		text += 'This is the long description. It can be used to more fully describe the plugin and be divided into sections, headings, lists, links, etc. It is been recommended to keep this section under 10k.\n\n';
		text += '== Frequently Asked Questions ==\n\n';
		text += '== Screenshots ==\n\n';
		text += '== Reviews ==\n\n';
		text += '== Other Notes ==\n\n';
		text += '== Installation ==\n\n';
		text += '== Changelog ==\n\n';
		text += '== Upgrade Notice ==\n\n';
		return text;
	}

	// Prepopulated text template.
	function get_default_text_with_example_data() {
		var text = '';
		text += '=== PluginNameHere ===\n';
		text += '\n';
		text += 'Description:       A succinct description of the plugin that talks about features, benefits, problems solved, whatever. Note that this text appears to span multiple lines here in the editor, but, it is actually a single line; this is a requirement.\n';
		text += 'Version:           1.0.0\n';
		text += 'Text Domain:       my-plugin-folder-name\n';
		text += 'Domain Path:       /languages\n';
		text += 'Requires PHP:      5.6\n';
		text += 'Requires:          1.0.0\n';
		text += 'Tested:            4.9.99\n';
		text += 'Author:            Code Potent\n';
		text += 'Author URI:        https://codepotent.com\n';
		text += 'Plugin URI:        https://codepotent.com/classicpress/plugins/\n';
		text += 'Download link:     https://\n';
		text += 'Donate link:       https://codepotent.com\n';
		text += 'License:           GPLv2\n';
		text += 'License URI:       https://www.gnu.org/licenses/gpl-2.0.html\n';
		text += '\n';		
		text += 'This is the short description and consists of a few sentences under 150 characters in all.\n';
		text += '== Description ==\n\n';
		text += 'This is the long description. It can be used to more fully describe the plugin and be divided into sections, headings, lists, links, etc. It is been recommended to keep this section under 10k.\n\n';
		text += '### Heading Size 3\n\n';
		text += 'Some text to [https://](describe) the content _related to this section_. Get creative.\n\n';
		text += '### Heading Size 3\n\n';
		text += 'Some text to [https://](describe) the content _related to this section_. Get creative.\n\n';
		//text += '\n';
		text += '== Frequently Asked Questions ==\n\n';
		text += '**Is this a question?**\n';
		text += 'Why, yes, it appears to be!\n';
		text += '\n';
		text += '**How about this??**\n';
		text += 'Yes, it, too, appears to be a question.\n';
		text += '\n';
		text += '**What\'s with all the commas?**\n';
		text += 'What\'s with all the questions?\n';
		text += '\n';
		text += '== Screenshots ==\n\n';
		text += '1. A caption for screenshot 1, yay! Screenshots are great, but, with a caption, they are just that much better. Adding captions looks great!\n';
		text += '2. If a numbered caption exists for a given numbered screenshot, the caption will be shown with it. If there is no caption, no big deal.\n';
		text += '3. Screenshots need not be sequential; if you decide you later hate screenshot-2.jpg, just delete it – there is no need to rename anything!\n';
		text += '\n';
		text += '== Reviews ==\n\n';
		text += '*****\n';
		text += '"Yes, you can even add user feedback and star ratings! Don\'t they look cool?"\n';
		text += '~ **Marcy Blaine**, _Personnel Manager_, [codepotent.com](https://codepotent.com)\n';
		text += '\n';
		text += '*****\n';
		text += '"The star rating (on the right) is calculated in realtime based on the reviews in this section!"\n';
		text += '~ **Jason Clancy**, _Web Developer_, [codepotent.com](https://codepotent.com)\n';
		text += '\n';
		text += '*\n';
		text += '"See how this one-star review dragged the score down by a bit? Yeah, that\'s math at work. But, I mean, who is going to add a 1-star review to their own plugin when they have control over it?"\n';
		text += '~ **Kelly Manchester**, _Technical Analyst_, [codepotent.com](https://codepotent.com)\n';
		text += '\n';
		text += '== Other Notes ==\n\n';
		text += '**About Code Potent**\n';
		text += 'Code Potent is a ClassicPress **plugin development** and resource site focused on **security**, **performance**, and **best practices**. Much of the content is geared toward developers, coders, and DIY’ers. In the plugins, substantial time and thought goes into creating predictable **user interfaces** that lend well to a pleasant **user experience**. Code Potent plugins are **well-documented**, very **extensible**, and **widely used** throughout the ClassicPress ecosystem. Check out the Code Potent **[2019 Year in Review](https:\/\/codepotent.com/2019-year-in-review/)** for more information!\n';
		text += '\n';
		text += '**Stay Informed**\n';
		text += 'Get the <a href="#">newsletter</a> and follow on <a href="https://codepotent.com/faq/#what-are-your-official-social-channels">social media</a> for exclusive tips, offers, sneak-previews, and latest developments.\n';
		text += '\n';
		text += '== Installation ==\n\n';
		text += '1) **Download** the zip file to your local computer\n';
		text += '2) **Login** to your ClassicPress website.\n';
		text += '3) **Navigate** to `Dashboard > Plugins > Add New > Upload Plugin`\n';
		text += '4) **Upload** the zip file to your site\n';
		text += '5) Click to **Install** the plugin\n';
		text += '6) Click to **Activate** the plugin\n';
		text += '\n';
		text += '1 — **Download** the zip file to your local computer\n';
		text += '2 — **Login** to your ClassicPress website.\n';
		text += '3 — **Navigate** to `Dashboard > Plugins > Add New > Upload Plugin`\n';
		text += '4 — **Upload** the zip file to your site\n';
		text += '5 — Click to **Install** the plugin\n';
		text += '6 — Click to **Activate** the plugin\n';
		text += '\n';
		text += '== Changelog ==\n\n';
		text += '* **[1.0.0](https://)** — 00-00-0000\n';
		text += ' — Initial release \n';
		text += ' — Fix \n';
		text += ' — Add \n';
		text += ' — Remove \n';
		text += ' — Replace \n';
		text += '\n';
		text += '* **[1.0.0](https://)** — 00-00-0000\n';
		text += ' > Initial release \n';
		text += ' > Fix \n';
		text += ' > Add \n';
		text += ' > Remove \n';
		text += ' > Replace \n';
		text += '\n';
		text += '* **[1.0.0](https://)** — 00-00-0000\n';
		text += '`Initial release \n';
		text += '`Fix stuff`\n';
		text += '`Add stuff`\n';
		text += '`Remove stuff`\n';
		text += '`Replace stuff`\n';
		text += '\n';
		text += '* **[1.0.0](https://)** — 00-00-0000\n';
		text += '```\n';
		text += 'Initial release \n';
		text += 'Fix stuff\n';
		text += 'Add stuff\n';
		text += 'Remove stuff\n';
		text += 'Replace stuff\n';
		text += '```\n';
		text += '\n';
		text += '== Upgrade Notice ==\n\n';
		text += 'This notice is displayed on the updates page and in the modal windows as a tabbed view.\n';
		text += '\n';
		return text;
	}

});