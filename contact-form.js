jQuery( function ( $ ) {
	$( '#contact-form' ).on( 'submit', function ( event ) {
		event.preventDefault();

		var $form = $( this );
		var $response = $( '#contact-form-response' );

		// enforce HTML5 field validation before ajax
		if ( ! $form[0].checkValidity() ) {
			$form[0].reportValidity();
			return;
		}

		var formData = $form.serialize();
		$response.text( 'Sending...' );

		$.post( contactForm.ajax_url, formData, function ( res ) {
			if ( res.success ) {
				$response.text( res.data );
				$form[0].reset();
			} else {
				$response.text( res.data || 'Error occurred.' );
			}
		} ).fail( function () {
			$response.text( 'Request failed. Please try again.' );
		} );
	} );
} );
