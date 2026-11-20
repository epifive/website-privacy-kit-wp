/**
 * EpiFive Website Privacy Kit: connects [wpk_form_consent] placeholders (and the Contact Form 7 /
 * WPForms integrations) to the banner's form API, wpk.form(form, { purposes, subject }).
 * The banner script adds unticked consent checkboxes with the notice link above the submit button
 * and records the choices with the email or phone value when the form is sent.
 */
( function () {
	'use strict';

	function attach() {
		var markers = document.querySelectorAll( '.wpk-form-consent' );
		Array.prototype.forEach.call( markers, function ( marker ) {
			var form = marker.closest( 'form' );
			if ( ! form || form.getAttribute( 'data-wpk-attached' ) ) {
				return;
			}
			var purposes = ( marker.getAttribute( 'data-wpk-purposes' ) || '' ).split( ',' ).filter( Boolean );
			var field = marker.getAttribute( 'data-wpk-field' ) || '';
			if ( ! field || ! form.elements.namedItem( field ) ) {
				var email = form.querySelector( 'input[type=email]' ) || form.querySelector( 'input[type=tel]' );
				field = email && email.name ? email.name : field || 'email';
			}
			form.setAttribute( 'data-wpk-attached', '1' );
			var opts = { subject: field };
			if ( purposes.length ) {
				opts.purposes = purposes;
			}
			window.wpk.form( form, opts );
		} );
	}

	// The banner script loads asynchronously: wait for it (up to 15 seconds).
	var tries = 0;
	( function wait() {
		if ( window.wpk && typeof window.wpk.form === 'function' ) {
			attach();
			return;
		}
		if ( ++tries < 60 ) {
			window.setTimeout( wait, 250 );
		}
	} )();
} )();
