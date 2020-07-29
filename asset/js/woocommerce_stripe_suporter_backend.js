jQuery( document ).ready( function( $ ) {
	'use strict';

	// create namespace to avoid any possible conflicts
	$.wc_product_vendors_vendor_admin = {
		showHideBookings: function() {
			if ( 'booking' !== $( 'select#product-type' ).val() ) {
				$( '.show_if_booking' ).hide();
			} else if ( 'booking' === $( 'select#product-type' ).val() ) {
				$( '.show_if_booking' ).show();
			}
		},

		init: function() {


			$( '.taxonomy-wcpv_product_vendors, .toplevel_page_wcpv-suporter-vendor-settings' ).on( 'click', '.wcpv-upload-logo', function( e ) {
				e.preventDefault();

				// create the media frame
				var i18n = wcpv_vendor_admin_local,
					inputField = $( this ).parents( '.form-field' ).find( 'input[name="vendor_data[logo]"]' ),
					previewField = $( this ).parents( '.form-field' ).find( '.wcpv-logo-preview-image' ),
					mediaFrame = wp.media.frames.mediaFrame = wp.media({

						title: i18n.modalLogoTitle,

						button: {
							text: i18n.buttonLogoText
						},

						// only images
						library: {
							type: 'image'
						},

						multiple: false
					});

				// after a file has been selected
				mediaFrame.on( 'select', function() {
					var selection = mediaFrame.state().get( 'selection' );

					selection.map( function( attachment ) {

						attachment = attachment.toJSON();

						if ( attachment.id ) {

							// add attachment id to input field
							inputField.val( attachment.id );

							// show preview image
							previewField.prop( 'src', attachment.url ).removeClass( 'hide' );

							// show remove image icon
							$( inputField ).parents( '.form-field' ).find( '.wcpv-remove-image' ).show();
						}
					});
				});

				// open the modal frame
				mediaFrame.open();
			});

			$( '.taxonomy-wcpv_product_vendors, .toplevel_page_wcpv-suporter-vendor-settings' ).on( 'click', '.wcpv-remove-image', function( e ) {
				e.preventDefault();

				$( this ).hide();
				$( this ).parents( '.form-field' ).find( '.wcpv-logo-preview-image' ).prop( 'src', '' ).addClass( 'hide' );
				$( 'input[name="vendor_data[logo]"]' ).val( '' );
			});



		}
	}; // close namespace

	$.wc_product_vendors_vendor_admin.init();
// end document ready
});
