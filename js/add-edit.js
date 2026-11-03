(function($){
	var file_frame;
	var l10n = window.cptmL10n || {};

	$('.media-uploader-button').on('click', function(e) {
		e.preventDefault();

		if ( file_frame ) {
			file_frame.open();
			return;
		}

		file_frame = wp.media.frames.file_frame = wp.media({
			title: l10n.frameTitle,
			button: {
				text: l10n.frameButton,
			},
			library: {
				type: 'image',
			},
			multiple: false
		});

		file_frame.on( 'select', function() {
			var attachment = file_frame.state().get('selection').first().toJSON();
			if ( attachment ) {
				// The cptm_icon size does not exist when CPTM_DONT_GENERATE_ICON is set (C-003).
				var url = ( attachment.sizes && attachment.sizes.cptm_icon ) ? attachment.sizes.cptm_icon.url : attachment.url;
				$('#cptm_icon_url').val( url );
				$('.current-cptm-icon').empty().append( $('<img>', { src: url, height: 16, width: 16, alt: '' }) );
				$('.remove-cptm-icon').show();
				$('.media-uploader-button').text( l10n.uploadIcon );
			}
		});

		file_frame.open();
		return false;
	});

	// Capability type: show the name field only for "Custom".
	var $capabilitySelect = $('#cptm_capability_type_select');
	var toggleCapability = function() {
		$('#cptm_capability_type_custom').toggle( 'custom' === $capabilitySelect.val() );
	};
	$capabilitySelect.on('change', toggleCapability);
	toggleCapability();

	$('.remove-cptm-icon').on('click', function(e) {
		e.preventDefault();
		$('#cptm_icon_url').val('');
		$('.current-cptm-icon').empty();
		$('.remove-cptm-icon').hide();
		$('.media-uploader-button').text( l10n.addIcon );
	});
})(jQuery);
