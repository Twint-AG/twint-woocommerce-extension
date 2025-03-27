'use strict';

jQuery(function ($) {
	var $notice = $('#elm-sw-setup-wizard-notice'),

		$autoSetupButton = $('#elm-sw-use-recommended-settings'),
		$instructionButton = $('#elm-sw-toggle-manual-instructions'),

		$errorContainer = $notice.find('#elm-sw-setup-error'),
		$errorMessage = $notice.find('#elm-sw-setup-error-message'),
		$manualInstructions = $notice.find('#elm-sw-manual-instructions'),

		$step2 = $notice.find('#elm-sw-step-2');

	$autoSetupButton.prop('disabled', false);
	$instructionButton.prop('disabled', false);

	//Show/hide manual configuration instructions.
	$instructionButton.click(function () {
		var $button = $(this);

		$manualInstructions.toggle();
		if ($manualInstructions.is(':visible')) {
			$button.val($button.data('hide-label'));
		} else {
			$button.val($button.data('show-label'));
		}
	});

	function handleErrorResponse(data, textStatus) {
		if (data && (typeof data['error'] !== 'undefined') && (typeof data['error']['message'] !== 'undefined')) {
			$errorMessage.html(data['error']['message']);
			$errorContainer.show();
			$manualInstructions.show();
		} else {
			var message = 'An unexpected response was received from the server.';
			if (textStatus) {
				message = 'Unexpected error: ' + textStatus;
			} else if (data && (typeof data['error'] !== 'undefined')) {
				message = data['error']['message'];
			}
			$errorMessage.text(message);
			$errorContainer.show();
			$manualInstructions.show();
		}
	}

	//"Use Recommended Settings".
	$autoSetupButton.click(function () {
		var $step1Progress = $notice.find('#elm-sw-step-1-progress');
		$step1Progress.show();
		$notice.find('#elm-sw-step-1').hide();

		AjawV1.getAction('elm-start-auto-setup').post(
			{},
			function (data, textStatus) {
				$step1Progress.hide();

				if (data && (typeof data['success'] !== 'undefined') && data.success) {
					$('#elm-sw-config-code').text(data.code);
					$step2.show();
				} else {
					handleErrorResponse(data, textStatus);
				}
			},
			handleErrorResponse
		);
	});

	//"Done" - verify that logging has been enabled.
	$('#elm-sw-step-2-done-button').click(function () {
		var $step2Progress = $notice.find('#elm-sw-step-2-progress');
		$step2Progress.show();
		$step2.hide();

		AjawV1.getAction('elm-check-configuration').post(
			{},
			function (data, textStatus) {
				$step2Progress.hide();

				if (data && (typeof data['success'] !== 'undefined') && data.success) {
					if ((typeof data['message'] !== 'undefined') && data.message) {
						$('#elm-sw-success-comment').text(data.message);
					}
					$('#elm-sw-setup-success').show();
					$('#elm-sw-heading').hide();

					$notice.addClass('notice-success').removeClass('notice-info');
				} else {
					handleErrorResponse(data, textStatus)
				}
			},
			handleErrorResponse
		);
	});
});