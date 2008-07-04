jQuery( function($) {
	var form = $( '#all-my-blogs' ).submit( function() { document.location = form.find( 'select' ).val(); return false;} );
	var tab = $('#all-my-blogs-tab a');
	var head = $('#wphead');
	$('.blog-picker-toggle').click( function() {
		form.toggle();
		tab.toggleClass( 'current' );
		if ( form.is( ':visible' ) ) {
			head.css( 'padding-top', form.height() + 16 );
		} else {
			head.css( 'padding-top', 0 );
		}
		return false;
	} );
} );
