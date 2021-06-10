/**
 * -----------------------------------------------------------------------------
 * Purpose: JavaScript for the plugin's transient inspector screens.
 * Package: CodePotent\UpdateManager
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

// Wait until the page has loaded.
jQuery(document).ready(function($) {

	// Click handler for popping plugin details.
	$('.codepotent-update-manager-thickbox-plugin').click(function(e) {
		e.preventDefault;
		var width = $(window).width() * .5;
		if (width > 750) {
			width = 750;
		}
		var height = $(window).height() * .85;
		tb_show('', e.target.dataset.url+'&TB_iframe=true&width='+width+'&height='+height);
		$("#TB_iframeContent").on("load", function(e){
			button=$(this).contents().find("#plugin_update_from_iframe");
			button.on("click", function(e){
				window.location.href = button.attr('href');
			});
		});
		return false;
	});

	// Click handler for popping theme details.
	$('.codepotent-update-manager-thickbox-theme').click(function(e) {
		e.preventDefault;
		var width = $(window).width() * .85;
		if (width > 1200) {
			width = 1200;
		}
		var height = $(window).height() * .85;
		tb_show('', '?TB_inline&inlineId=display-theme-'+e.target.dataset.theme+'&width='+width+'&height='+height);
		return false;
	});

	// Direct URL calls and click handling for navigation.
	var my_hash = window.location.hash.substr(1);
	if (!my_hash) {
		$('h2.'+plugin_slug+'-admin-nav-tab-wrapper a:first-child').addClass('nav-tab-active');
		$('.'+plugin_slug+'-admin-tab.first').css('display', 'block');
	} else {
		$('a#tab-'+my_hash).addClass('nav-tab-active');
		$('p.'+plugin_slug+'-admin-tab.tab-'+my_hash+'-submenu').css('display', 'block');
		$('div.'+plugin_slug+'-admin-tab.tab-'+my_hash+'-content').css('display', 'block');
	}
	$('h2.'+plugin_slug+'-admin-nav-tab-wrapper a').click(function(e){
		$('h2.'+plugin_slug+'-admin-nav-tab-wrapper a').attr('class', 'nav-tab');
		$('.'+plugin_slug+'-admin-tab').css('display', 'none');
		$(this).addClass('nav-tab-active');
		$(this).onmouseup = this.blur();
		$('p.'+plugin_slug+'-admin-tab.tab-'+e.currentTarget.hash.substr(1)+'-submenu').css('display', 'block');
		$('div.'+plugin_slug+'-admin-tab.tab-'+e.currentTarget.hash.substr(1)+'-content').css('display', 'block');
	});

});