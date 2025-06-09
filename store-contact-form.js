jQuery( function ( $ ) {
	$( '#store-contact-form' ).on( 'submit', function ( event ) {
		event.preventDefault();

		var $form = $( this );
		var $response = $( '#store-contact-response' );
		var formData = $form.serialize();

		$response.text( 'Sending...' );

		$.post( storeContactForm.ajax_url, formData, function ( res ) {
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
