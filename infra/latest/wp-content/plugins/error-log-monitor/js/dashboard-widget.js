//Below is the js-cookie library, included here in-line to avoid loading another
//JS file for such a small library. This is the minified version of the library.
//Origin: https://github.com/js-cookie/js-cookie
//License: MIT

/*! js-cookie v3.0.5 | MIT */
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self,function(){var n=e.Cookies,o=e.Cookies=t();o.noConflict=function(){return e.Cookies=n,o}}())}(this,(function(){"use strict";function e(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)e[o]=n[o]}return e}var t=function t(n,o){function r(t,r,i){if("undefined"!=typeof document){"number"==typeof(i=e({},o,i)).expires&&(i.expires=new Date(Date.now()+864e5*i.expires)),i.expires&&(i.expires=i.expires.toUTCString()),t=encodeURIComponent(t).replace(/%(2[346B]|5E|60|7C)/g,decodeURIComponent).replace(/[()]/g,escape);var c="";for(var u in i)i[u]&&(c+="; "+u,!0!==i[u]&&(c+="="+i[u].split(";")[0]));return document.cookie=t+"="+n.write(r,t)+c}}return Object.create({set:r,get:function(e){if("undefined"!=typeof document&&(!arguments.length||e)){for(var t=document.cookie?document.cookie.split("; "):[],o={},r=0;r<t.length;r++){var i=t[r].split("="),c=i.slice(1).join("=");try{var u=decodeURIComponent(i[0]);if(o[u]=n.read(c,u),e===u)break}catch(e){}}return e?o[e]:o}},remove:function(t,n){r(t,"",e({},n,{expires:-1}))},withAttributes:function(n){return t(this.converter,e({},this.attributes,n))},withConverter:function(n){return t(e({},this.converter,n),this.attributes)}},{attributes:{value:Object.freeze(o)},converter:{value:Object.freeze(n)}})}({read:function(e){return'"'===e[0]&&(e=e.slice(1,-1)),e.replace(/(%[\dA-F]{2})+/gi,decodeURIComponent)},write:function(e){return encodeURIComponent(e).replace(/%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g,decodeURIComponent)}},{path:"/"});return t}));

//End of js-cookie library.

jQuery(function ($) {
	const elmCookies = window.Cookies.noConflict();

	var widget = $('#ws_php_error_log'),

		dashboardNoFilterOption = widget.find('#elm_dashboard_message_filter_all'),
		dashboardCustomFilterOption = widget.find('#elm_dashboard_message_filter_selected'),

		emailMatchFilterOption = widget.find('#elm_email_message_filter_same'),
		emailCustomFilterOption = widget.find('#elm_email_message_filter_selected'),

		dashboardFilterOptions = widget.find('input[name^="ws_php_error_log[dashboard_severity_option-"]'),
		emailFilterOptions = widget.find('input[name^="ws_php_error_log[email_severity_option-"]');

	function updateDashboardOptions() {
		dashboardFilterOptions.prop('disabled', !dashboardCustomFilterOption.is(':checked'))
	}

	function updateEmailOptions() {
		emailFilterOptions.prop('disabled', !emailCustomFilterOption.is(':checked'));
	}

	//First enable/disable the checkboxes when the page loads.
	updateDashboardOptions();
	updateEmailOptions();

	//Then refresh them when the user changes filter settings.
	dashboardCustomFilterOption.add(dashboardNoFilterOption).on('change', function () {
		updateDashboardOptions();
	});
	emailCustomFilterOption.add(emailMatchFilterOption).on('change', function () {
		updateEmailOptions();
	});

	//Handle the "Ignore" and "Mark as fixed" links.
	widget.on('click', '.elm-ignore-message, .elm-mark-as-fixed', function () {
		var row = $(this).closest('.elm-entry'),
			message = row.data('raw-message');

		//Hide all copies of this message.
		row.closest('.elm-log-entries').find('.elm-entry').filter(function () {
			return $(this).data('raw-message') === message;
		}).hide().remove();

		var action;
		if ($(this).hasClass('elm-mark-as-fixed')) {
			action = AjawV1.getAction('elm-mark-as-fixed');
		} else {
			action = AjawV1.getAction('elm-ignore-message');
		}
		action.post({message: message});

		return false;
	});

	//And the "Unignore" and "Mark as not fixed" links.
	widget.on('click', '.elm-unignore-message, .elm-mark-as-not-fixed', function () {
		var row = $(this).closest('tr'),
			message = row.data('raw-message');

		row.remove();

		var action;
		if ($(this).hasClass('elm-mark-as-not-fixed')) {
			action = AjawV1.getAction('elm-mark-as-not-fixed');
		} else {
			action = AjawV1.getAction('elm-unignore-message');
		}
		action.post({message: message});

		return false;
	});

	function handleClearMessagesButton(button, tableSelector, noticeSelector, ajaxActionName) {
		var actionText = button.text();

		button.prop('disabled', true);
		button.text(button.data('progressText'));

		//Hide the entire table.
		var table = widget.find(tableSelector);
		var totalMessages = table.find('tr').length;
		table.hide();

		var action = AjawV1.getAction(ajaxActionName);
		if (action) {
			action.post(
				{total: totalMessages},
				function () {
					//Success!
					table.remove();
					button.remove();
					widget.find(noticeSelector).show();
				},
				function () {
					//Something went wrong. Restore the table and the button.
					button.text(actionText);
					button.prop('disabled', false)
					table.show();
				}
			);
		}
	}

	//Handle the "Clear Ignored Messages" button.
	widget.find('#elm-clear-ignored-messages').on('click', function () {
		handleClearMessagesButton(
			$(this),
			'.elm-ignored-messages',
			'#elm-no-ignored-messages-notice',
			'elm-clear-ignored-messages'
		);
		return false;
	});

	//Handle the "Clear Fixed Messages" button.
	widget.find('#elm-clear-fixed-messages').on('click', function () {
		handleClearMessagesButton(
			$(this),
			'.elm-fixed-messages',
			'#elm-no-fixed-messages-notice',
			'elm-clear-fixed-messages'
		);
		return false;
	});

	//Handle the "Show X more" context link.
	widget.on('click', '.elm-show-mundane-context', function () {
		var link = $(this),
			container = link.closest('.elm-context-group-content');
		container.removeClass('elm-hide-mundane-items');
		link.hide().closest('tr,li').hide();
		return false;
	});

	//Handle collapsible context groups.
	//Remember the state of the last N toggled groups.
	let groupVisibility = null;
	const maxStoredGroupStates = 40; //Could fit about 120 in 4 KiB, but let's be conservative.
	const groupStateCookie = 'elm_context_group_state';
	widget.on('click', '.elm-context-group-caption', function () {
		const $group = $(this).closest('.elm-context-group');
		$group.toggleClass('elm-closed-context-group');

		//Parse the stored group states.
		if (groupVisibility === null) {
			groupVisibility = new Map();
			const cookie = elmCookies.get(groupStateCookie);
			if (cookie && (typeof cookie === 'string')) {
				try {
					const storedStates = JSON.parse(cookie);
					//Expected format: Array of [groupName, isOpen] pairs.
					if (Array.isArray(storedStates)) {
						for (const state of storedStates) {
							if (
								Array.isArray(state)
								&& (typeof state[0] === 'string')
								&& (typeof state[1] === 'boolean')
							) {
								groupVisibility.set(state[0], state[1]);
							}
						}
					}
				} catch (e) {
					//Ignore errors.
				}
			}
		}

		const $entry = $group.closest('.elm-entry');
		const groupName = $group.data('group');
		const hash = $entry.data('hash');
		if (groupName && hash) {
			const key = hash + ':' + groupName;
			//Always add the changed group state at the end of the map, so it stays
			//sorted from oldest to most recent. JS maps use insertion order.
			groupVisibility.delete(key);
			groupVisibility.set(key, !$group.hasClass('elm-closed-context-group'));

			if (groupVisibility.size > maxStoredGroupStates) {
				//Delete the oldest entries.
				const keys = Array.from(groupVisibility.keys());
				const numKeysToDelete = groupVisibility.size - maxStoredGroupStates;
				for (let i = 0; i < numKeysToDelete; i++) {
					groupVisibility.delete(keys[i]);
				}
			}

			//Store the group states in a cookie.
			const states = Array.from(groupVisibility.entries());
			elmCookies.set(
				groupStateCookie,
				JSON.stringify(states),
				{expires: 90, sameSite: 'lax'}
			);
		}

		return false;
	});

	//Handle the "Hide" link that hides the "Upgrade to Pro" notice.
	widget.on('click', '.elm-hide-upgrade-notice', function (event) {
		$(this).closest('.elm-upgrade-to-pro-footer').hide();
		AjawV1.getAction('elm-hide-pro-notice').post();
		event.preventDefault();
		return false;
	});

	//Move the "Upgrade to Pro" section to the very bottom of the widget settings panel, below the "Submit" button.
	var settingsForm = widget.find('.dashboard-widget-control-form'),
		proSection = settingsForm.find('#elm-pro-version-settings-section');
	if (settingsForm.length > 0) {
		proSection.appendTo(settingsForm).show();
	}
});
