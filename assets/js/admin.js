jQuery( function( $ ) {

	setTimeout(function () {
		
		jQuery( document ).on( 'click', '#patreon-image-lock-icon', function( e ) {
			
			var image_classes = tinymce.editors[0].selection.getNode().className.split(' ');
			var arrayLength = image_classes.length;
			
			for ( var i = 0; i < arrayLength; i++ ) {
				if ( image_classes[i].indexOf( 'wp-image-' ) !== -1 ) {
					var attachment_id = image_classes[i].replace( "wp-image-", "" );
				}
			}		

			tb_show( 'Patron only image', "#TB_inline?width=350&height=150&inlineId=mygallery-form", false );
			
			jQuery( document ).find( '#TB_window' ).width( TB_WIDTH ).height( TB_HEIGHT ).css( 'margin-left', - TB_WIDTH / 2 );
			
			data = { 
				action: "patreon_make_attachment_pledge_editor",
				patreon_attachment_id: attachment_id
			};     
			jQuery.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'html',
				type: 'post',
				success: function(response) {
					jQuery( document ).find( '#TB_window' ).html( response );
				}
			});
		})
	}, 1000 );
	
	jQuery( document ).on( 'submit', '#patreon_attachment_patreon_level_form', function( e ) {
		e.preventDefault();
		jQuery.ajax({
			url: ajaxurl,
			data: jQuery( '#patreon_attachment_patreon_level_form' ).serialize(),
			dataType: 'html',
			type: 'post',
			success: function( response ) {
				jQuery( document ).find( '#TB_window' ).empty();
				jQuery( document ).find( '#TB_window' ).html(response);
			}
		});	
	});
	
	// Need to bind to event after tinymce is initialized - so we hook to all tinymce instances after a timeout
	setTimeout( function () {
		
		if( typeof tinymce === 'undefined' ) {
			return;
		}
		for ( var i = 0; i < tinymce.editors.length; i++ ) {
			
			tinymce.editors[i].on( 'click', function ( e ) {
				
				if( e.target.nodeName = 'img' ) {
					
					var $ = tinymce.dom.DomQuery;
					var tinymce_iframe_offset = jQuery( '#content_ifr' ).offset();
					var clicked_image_inside_frame_offset = $( e.target ).offset();
					
					// Add a special attribute to easily find the item because tinymce's domquery doesnt have functions to get width of the relevant element - we have to get it from jquery from outside the iframe. 
					
					$( e.target ).attr( 'data-patreon-selected-image', '1' );
					
					// Get the clicked image inside iframe
					var clicked_image = jQuery( '#content_ifr' ).contents().find( '[data-patreon-selected-image="1"]' );
					var clicked_image_width = clicked_image.width();
					
					// Remove the added attribute
					$( e.target ).removeAttr( 'data-patreon-selected-image' );
					
					
					jQuery( '#patreon-image-toolbar' ).css({
							position : 'absolute',
							top: tinymce_iframe_offset.top + clicked_image_inside_frame_offset.top + 20 + "px",
							left: tinymce_iframe_offset.left + clicked_image_inside_frame_offset.left + clicked_image_width + 10 + "px"
						}).show();
					}
			});
		}
	}, 1000);

	jQuery(document).on( 'click', '.patreon-wordpress .notice-dismiss', function(e) {

		jQuery.ajax({
			url: ajaxurl,
			type:"POST",
			dataType : 'html',
			data: {
				action: 'patreon_wordpress_dismiss_admin_notice',
				notice_id: jQuery( this ).parent().attr( "id" ),
			}
		});
	});	
	
	// Doing the below via jQuery because we have to submit some POST info inside another form. Avoided using a link inside button to account for older devices
	
	jQuery(document).on( 'click', '#patreon_wordpress_disconnect_from_patreon', function(e) {
		e.preventDefault();
		var target = jQuery(this).attr( 'target' );
		window.location.replace( target );
	});
	
	// Doing the below via jQuery because we have to submit some POST info inside another form. Avoided using a link inside button to account for older devices
	
	jQuery(document).on( 'click', '#patreon_wordpress_reconnect_to_patreon', function(e) {
		e.preventDefault();
		var target = jQuery(this).attr( 'target' );
		window.location.replace( target );
	});
	
	jQuery(document).on( 'click', '.patreon-wordpress-admin-toggle', function(e) {
		
		e.preventDefault();
		
		var toggle_id = jQuery( this ).attr( 'toggle' );
		var toggle_target = document.getElementById( toggle_id );

		jQuery( toggle_target ).slideToggle();
		
		if( jQuery( this ).attr( 'togglestatus' ) == 'off' ) {
			
			jQuery( this ).html( jQuery( this ).attr( 'ontext' ) );
			jQuery( this ).attr( 'togglestatus', 'on' );
			
		}
		else if( jQuery( this ).attr( 'togglestatus' ) == 'on' ) {
			
			jQuery( this ).html( jQuery( this ).attr( 'offtext' ) );
			jQuery( this ).attr( 'togglestatus', 'off' );
			
		}
				
		jQuery.ajax({
			url: ajaxurl,
			type:"POST",
			dataType : 'html',
			data: {
				action: 'patreon_wordpress_toggle_option',
				toggle_id: toggle_id,
			}
		});		
		
	});
	

	jQuery(document).on( 'click', '.patreon_wordpress_interface_toggle', function(e) {
		
		e.preventDefault();
		
		var toggles = jQuery( this ).attr( 'toggle' );
		var toggle_array = toggles.split(" ");
		toggle_array.forEach( function( toggle_id, index, toggle_array ) {
			var toggle_target = document.getElementById( toggle_id );

			jQuery( toggle_target ).slideToggle();
							
		}, jQuery( this ) );
		
	});	
		
	// Only trigger if the select dropdown is actually present
	if ( jQuery( "#patreon_level_select" ).length ) {
		
		var pw_input_target = jQuery( "#patreon_level_select" );
		var pw_post_id = pw_input_target.attr( 'pw_post_id' );
			
		jQuery.ajax({
			url: ajaxurl,
			async: true, // Just to make sure
			type:"POST",
			dataType : 'html',
			data: {
				action: 'patreon_wordpress_populate_patreon_level_select',
				pw_post_id: pw_post_id,
			},
			success: function( response ) {
				jQuery( pw_input_target ).empty();
				jQuery( pw_input_target ).html( response );
				
			},
			error: function( response ) {
				jQuery( pw_input_target ).empty();
				jQuery( pw_input_target ).html( response );
			},
			statusCode: {
				500: function(error) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Sorry - error (500)' );
				}
			}
		});	
		
	}
	
	// Sync the exact amount value to select value if select is changed
	jQuery( "#patreon_level_select" ).on( 'change', function() {
		jQuery( "#patreon-level-exact" ).val( this.value );
	});
	
	jQuery( ".patreon_toggle_admin_sections" ).on( 'click', function (e) {
		
		if ( jQuery( e.target ).hasClass( 'patreon_setting_section_help_icon' ) ) { 
			return 
		};
		
		jQuery( '#footer-thankyou' ).remove();
		var patreon_target = jQuery( this ).parent('.patreon_admin_health_content_box').find( jQuery( this ).attr( 'target' ) );
        e.preventDefault();
		if ( jQuery( this ).find( 'span:first' ).hasClass( 'dashicons-arrow-down-alt2' ) ) {
			jQuery( this ).find( 'span:first' ).removeClass( 'dashicons-arrow-down-alt2' );
			jQuery( this ).find( 'span:first' ).addClass( 'dashicons-arrow-up-alt2' );
		}
		else if ( jQuery( this ).find( 'span:first' ).hasClass( 'dashicons-arrow-up-alt2' ) ) {
			jQuery( this ).find( 'span:first' ).removeClass( 'dashicons-arrow-up-alt2' );
			jQuery( this ).find( 'span:first' ).addClass( 'dashicons-arrow-down-alt2' );
		}
		jQuery( patreon_target ).toggle( 'slow' );
	});
	
	jQuery( '#patreon_copy_health_check_output' ).on( 'click', function (e) {
		e.preventDefault();
		// Some of below is from stack https://stackoverflow.com/questions/23048550/how-to-copy-a-divs-content-to-clipboard-without-flash
		var textarea = document.createElement( 'textarea' );
		  textarea.id = 'patreon_temp_element'
		  // Optional step to make less noise on the page, if any!
		  textarea.style.height = 0
		  // Now append it to your page somewhere, I chose <body>
		  document.body.appendChild( textarea )
		  // Give our textarea a value of whatever inside the div of id=containerid
		  textarea.value = jQuery( '#patreon_health_check_output_for_support' ).text();
		  // Now copy whatever inside the textarea to clipboard
		  var selector = document.querySelector( '#patreon_temp_element' );
		  selector.select();
		  document.execCommand('copy');
		  // Remove the textarea
		  document.body.removeChild(textarea);
		jQuery( "#patreon_copied" ).text( "Copied!" ).show().fadeOut( 1000 );
    });

	
});