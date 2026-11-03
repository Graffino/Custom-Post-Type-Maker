(function($){
	var l10n = window.cptmCodeL10n || {};

	// "Copy code" buttons: copy the code box next to the button.
	$(document).on('click', '.cptm-copy-code', function() {
		var $button = $(this);
		var $box = $button.closest('.inside, .card').find('.cptm-code').first();
		var $status = $button.siblings('.cptm-copied');
		var done = function() { $status.text( l10n.copied ); };

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( $box.val() ).then( done );
		} else {
			$box.trigger('select');
			document.execCommand('copy');
			done();
		}
	});
})(jQuery);
