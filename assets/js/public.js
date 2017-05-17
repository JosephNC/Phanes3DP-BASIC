/**
* Change window url
* 
* @param title
* @param url
*/
function change_url( title, url ) {
    
    var obj = { Title: title, Url: url };
    
    history.pushState( obj, obj.Title, obj.Url );
    
}

/**
* Format a number with grouped thousands
* 
* @param number The number being formatted
* @param decimals Sets the number of decimal points
* @param dec_point Sets the separator for the decimal point
* @param thousands_sep Sets the thousands separator
* @returns string A formatted version of number
*/
function number_format( number, decimals, dec_point, thousands_sep ) {
    // Strip all characters but numerical ones.
    number = ( number + '' ).replace( /[^0-9+\-Ee.]/g, '' );
    var n = !isFinite( +number ) ? 0 : +number,
        prec = !isFinite( +decimals ) ? 0 : Math.abs( decimals ),
        sep = ( typeof thousands_sep === 'undefined' ) ? ',' : thousands_sep,
        dec = ( typeof dec_point === 'undefined' ) ? '.' : dec_point,
        s = '',
        toFixedFix = function ( n, prec ) {
            var k = Math.pow( 10, prec );
            return '' + ( Math.round( n * k ) / k ).toFixed(prec);
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = ( prec ? toFixedFix( n, prec ) : '' + Math.round( n ) ).split( '.' );
    if ( s[0].length > 3 ) {
        s[0] = s[0].replace( /\B(?=(?:\d{3})+(?!\d))/g, sep );
    }
    if ( ( s[1] || '' ).length < prec ) {
        s[1] = s[1] || '';
        s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
    }
    return s.join( dec );
}

function parse_float( number ) {
    
    number = ( number + '' ).replace( /[^0-9.]/g, '' );
    
    return parseFloat( number );    
    
}

/**
* Formats a number as a currency string
* 
* @param number The number to be formatted
* @return string Returns the formatted string
*/
function money_format( number ) {
    
    var control         = PQC.money_format_control; 
        num_decimals    = control.num_decimals; 
        decimals_sep    = control.decimals_sep; 
        thousand_sep    = control.thousand_sep; 
        currency        = control.currency; 
        currency_sym    = control.currency_symbol; 
        currency_pos    = control.currency_pos;
        
    number = number_format( number, num_decimals, decimals_sep, thousand_sep );
    
    switch ( currency_pos ) {
        case 'left' :           money = currency_sym + number; break;
        case 'left_space' :     money = currency_sym + " " + number; break;
        case 'right' :          money = number + currency_sym; break;
        case 'right_space' :    money = number + " " + currency_sym; break;
        default :               money = currency_sym + number; break;
    }
    
    return money; 
}

function shipping_sol( val ) {

    var subtotal = parse_float( jQuery( '#pqc-wrapper table.cart-total tr.sub-total td#subtotal' ).text() ),
        total   = subtotal,
        desc    = '',
        cost    = '';

    if ( val >= 1 ) {
        
        desc = PQC_Shipping[val]['desc'];
        cost = 'Cost: ' + PQC_Shipping[val]['cost'];
        total = parse_float( PQC_Shipping[val]['amount'] ) + parse_float( subtotal );
        
    }

    var object = { 'desc' : desc, 'cost' : cost, 'total' : total };
    
    return object;
    
}

function bindRemoveCoupon() {
    
    jQuery( '#pqc-wrapper a#remove-coupon' ).click( function () {
        
        jQuery( '#pqc-wrapper form#cart-form' ).append( '<input type="hidden" name="remove_coupon" value="1">' ).submit();
        
        jQuery( '#pqc-wrapper form#cart-form' ).find( 'input[name="remove_coupon"]' ).remove();
        
        return false;

    });
    
}

( function( $, window, document, undefined ) {
    
    if ( PQC_Page.page == 1 ) {
        
        $( '#pqc-wrapper .inputfile' ).each( function () {
            
            var input       = $( this ),
                label       = input.next( 'label' ),
                labelVal    = label.html();
                
            input.on( 'change', function( e ) {
                
                var fileName = '';
                
                var files = $(this)[0].files;
                
                if ( files.length > 1 ) {
                    
                    if ( files.length > PQC.max_upload ) {
                        
                        alert( 'Max upload is ' + PQC.max_upload );
                        
                        return false;
                    }
                    
                    label.find( 'span' ).html( files.length + " files selected" );
                    
                } else {
                    
                    if ( e.target.value ) {
                        
                        fileName = e.target.value.split( '\\' ).pop();
                    }
                    
                    if ( fileName ) {
                        
                        label.find( 'span' ).html( fileName );
                    
                    } else {
                        
                        label.html( labelVal );
                        
                    }
                        
                }
                
            });

            // Firefox bug fix
            input
            .on( 'focus', function() { input.addClass( 'has-focus' ); })
            .on( 'blur', function() { input.removeClass( 'has-focus' ); });
        });

    }
    else if ( PQC_Page.page == 2 ) {
        
        if ( PQC_Shipping.shipping_set == 0 ) $( '#pqc-wrapper select[name="shipping_option"] option:selected' ).prop( "selected", false );
        
        $( '#pqc-wrapper select[name="shipping_option"]' ).change( function() {
            
            var object = shipping_sol( $( this ).val() );
            
            $( this ).siblings( 'p#shipping-description' ).text( object.desc );
            $( this ).siblings( 'p#shipping-cost' ).text( object.cost );
            $( '#pqc-wrapper tr.total td#total' ).text( money_format( object.total ) );
            $( '#pqc-wrapper form#pqc_proceed_checkout_form input[name="shipping_option_id"]' ).val( $( this ).val() );

        });
        
        $( 'form#pqc_proceed_checkout_form' ).submit( function () {

            var shippingOption = $( '#pqc-wrapper select[name="shipping_option"]' ).val(),
                shippingOptionId = $( '#pqc-wrapper form#pqc_proceed_checkout_form input[name="shipping_option_id"]' ).val( shippingOption );
            
            if ( shippingOption >= 1 && shippingOptionId.val() >= 1 ) {
                
                return true;
                
            } else {
                
                alert( 'Please choose a shipping option.' );
                
                return false;
                
            }
            
        } );
        
        bindRemoveCoupon();
        
        $( '#pqc-wrapper input#apply-coupon' ).click( function () {
            
            $( '#pqc-wrapper form#cart-form' ).append( '<input type="hidden" name="apply_coupon" value="1">' ).submit();
            
            $( '#pqc-wrapper form#cart-form' ).find( 'input[name="apply_coupon"]' ).remove();
            
            return false;

        });
        
        $( '#pqc-wrapper form#cart-form' ).submit( function( e ) {
           
            var object = $(this).serialize(),
                tbody = $(this).find( 'table.cart tbody' ),
                modal = $( "#pqc-wrapper #modal" );

            $.ajax({
                type : "post",
                dataType : "json",
                url : PQC_Ajax.url,
                beforeSend: function() { modal.addClass( "loading" ); },
                complete: function() { modal.removeClass( "loading" ); },
                data : { action: PQC_Ajax.action + '-update', nonce: PQC_Ajax.nonce, data: object },
                success: function( response ) {
                    if ( response.type == "success" ) {
                        
                        $.each( response.the_items, function ( index, value ) {
                            
                            tbody.find( 'tr#' + index + ' td#' + index + '-cost span.cost' ).text( ' x ' + value.cost );
                            tbody.find( 'tr#' + index + ' td#' + index + '-total p' ).text( value.total );

                        } );
                        
                        // Modify Subtotal
                        $( '#pqc-wrapper table.cart-total tr.sub-total td#subtotal' ).text( response.sub_total );
                        
                        $( "#pqc-wrapper div#coupon-msg" ).find( 'div.success, div.error' ).remove(); // Remove Coupon message element just incase.
                        $( "#pqc-wrapper div#msg" ).find( 'div.success, div.error' ).remove(); // Remove message element just incase.

                        if ( response.coupon_msg != '' )
                            $( "#pqc-wrapper div#coupon-msg" ).html( '<div class="' + response.coupon_type + '"><p>' + response.coupon_msg + '</p></div>' );

                        
                        if ( response.msg != '' )
                            $( "#pqc-wrapper div#msg" ).html( '<div class="' + response.type + '"><p>' + response.msg + '</p></div>' );
                        
                        $( 'html, body' ).animate( { scrollTop: $( '#pqc-wrapper' )
                            .parent()
                            .parent()
                            .parent()
                            .offset().top
                        }, 'slow' );
                        
                        var shippingSol = shipping_sol( $( '#pqc-wrapper select[name="shipping_option"]' ).val() );
                        
                        $( '#pqc-wrapper tr.total td#total' ).text( money_format( shippingSol.total ) );
                        

                    }
                    else {
                        if ( response.msg == undefined ) {
                            alert( PQC_Ajax.err_msg );
                        }
                        else {
                            
                            $( "#pqc-wrapper div#coupon-msg" ).find( 'div.success, div.error' ).remove(); // Remove Coupon message element just incase.
                            $( "#pqc-wrapper div#msg" ).find( 'div.success, div.error' ).remove(); // Remove message element just incase.
                            
                            if ( response.msg != '' )
                                $( "#pqc-wrapper div#msg" ).html( '<div class="' + response.type + '"><p>' + response.msg + '</p></div>' );
                            
                            $( 'html, body' ).animate( { scrollTop: $( '#pqc-wrapper' )
                                .parent()
                                .parent()
                                .parent()
                                .offset().top
                            }, 'slow' );
                    
                        }
                    }
                    
                    bindRemoveCoupon();
                }
            });
            
            return false;
            
        } );
        
        /**
        * For Removing Items in Cart
        * 
        */
        $( '#pqc-wrapper form#cart-form a.remove-item, #pqc-wrapper a#empty-cart' ).click( function() {
            
            var active = $(this).attr( 'id' ),
                modal = $( "#pqc-wrapper #modal" );
            
            if ( active == 'empty-cart' ) {
                
                var ids = [];
                
                $( 'a.remove-item' ).each( function() {
                    ids.push( $(this).attr( 'id' ) );    
                } );
        
            }
            else {
                
                var ids = [$(this).attr( 'id' )],
                    parent = $(this).parents( 'tr' );
        
            }

            $.ajax({
                type : "post",
                dataType : "json",
                url : PQC_Ajax.url,
                beforeSend: function() { modal.addClass( "loading" ); },
                complete: function() { modal.removeClass( "loading" ); },
                data : { action: PQC_Ajax.action + '-delete', nonce: PQC_Ajax.nonce, unique_ids: ids },
                success: function( response ) {
                    
                    if ( response.type == "success" ) {
                        
                        if ( active == 'empty-cart' ) {
                            
                            $( '#pqc-wrapper div.content' ).remove();

                            $( "#pqc-wrapper div#coupon-msg" ).find( 'div.success, div.error' ).remove(); // Remove Coupon message element just incase.
                            $( "#pqc-wrapper div#msg" ).find( 'div.success, div.error' ).remove(); // Remove message element just incase.
                            
                        }
                        else {
                        
                            var total = parent.siblings( 'tr' ).length;
                            
                            if ( total > 1 ) {
                                
                                $( 'div.item-total-heading h1' ).text( total + ' items in cart' );
                                
                            }
                            else {
                                
                                $( 'div.item-total-heading h1' ).text( total + ' item in cart' );
                                
                            }
                            
                            if ( total == 0 ) {
                                
                                parent.parents( '#pqc-wrapper div.content' ).remove();
                                
                                $( 'header.codrops-header' ).show( 'fast' );
                                
                            }
                            else {

                                parent.remove();
                                
                            }
                            
                            $( '#pqc-wrapper table.cart-total tr.sub-total td#subtotal' ).text( response.sub_total );
                            
                            var shippingSol = shipping_sol( $( '#pqc-wrapper select[name="shipping_option"]' ).val() );
                            
                            $( '#pqc-wrapper tr.total td#total' ).text( money_format( shippingSol.total ) );
                            
                            $( "#pqc-wrapper div#coupon-msg" ).find( 'div.success, div.error' ).remove(); // Remove Coupon message element just incase.
                            $( "#pqc-wrapper div#msg" ).find( 'div.success, div.error' ).remove(); // Remove message element just incase.

                            if ( response.coupon_msg != '' )
                                $( "#pqc-wrapper div#coupon-msg" ).html( '<div class="' + response.coupon_type + '"><p>' + response.coupon_msg + '</p></div>' );
                        
                        }
                        
                        if ( response.msg != '' )
                            $( "#pqc-wrapper div#msg" ).html( '<div class="' + response.type + '"><p>' + response.msg + '</p></div>' );
                        
                        $( 'html, body' ).animate( { scrollTop: $( '#pqc-wrapper' )
                            .parent()
                            .parent()
                            .parent()
                            .offset().top
                        }, 'slow' );
                        
                    }
                    else {
                    
                        alert( PQC_Ajax.err_msg );
                        
                    }
                    
                    bindRemoveCoupon();
                }
            });
            
            return false;
            
        } );

    }
    else if ( PQC_Page.page == 3 ) {
        
        $( 'a#show-cart-details' ).click( function () {
            
            $( 'div#content-full' ).slideToggle( 'slow' );
            
            return false;
        } );
        

        $( 'ul.pqc-payment-options-list li input:checked' ).attr( 'data-prev', 'selected' )
        .siblings( '.payment-options-description' ).fadeIn( 'slow' )
        .parents( 'ul' ).siblings( 'input#place-order-button' ).fadeIn( 'fast' );

        $( 'ul.pqc-payment-options-list li input' ).change( function() {
            
            if ( $(this).is( ":checked" ) ) {
                
                $( 'ul.pqc-payment-options-list li input[data-prev="selected"]' ).removeAttr( 'data-prev' )
                .siblings( '.payment-options-description' ).hide( 'fast' )
                .parent( 'label' ).siblings( '.pqc-place-order' ).hide( 'fast' );
                
                $(this).attr( 'data-prev', 'selected' );
                    
                $(this).siblings( '.payment-options-description' ).fadeIn( 'slow' );
                
                $(this).parents( 'form#pqc_place_order' ).find( 'input#place-order-button' ).fadeIn( 'fast' );
                
            }

        });
        
        $( 'form#pqc_place_order' ).submit( function () {

            var paymentMethod = $( this ).find( 'ul.pqc-payment-options-list li input:checked' ).val();
                firstname = $( this ).find( 'fieldset#item-attributes_fieldset input[name="firstname"]' );
                lastname = $( this ).find( 'fieldset#item-attributes_fieldset input[name="lastname"]' );
                address = $( this ).find( 'fieldset#item-attributes_fieldset input[name="address"]' );
                city = $( this ).find( 'fieldset#item-attributes_fieldset input[name="city"]' );
                zipcode = $( this ).find( 'fieldset#item-attributes_fieldset input[name="zipcode"]' );
                state = $( this ).find( 'fieldset#item-attributes_fieldset input[name="state"]' );
            
            if ( paymentMethod == '' || paymentMethod == null || paymentMethod == 'undefined' ) {
                
                alert( 'Please choose a shipping option.' );
                
                return false;
                
            }
            else {
                
                var error = 0;
                
                if ( firstname.val() == '' || firstname.val().length < 3 ) {
                    error = 1;
                    firstname.removeClass( 'buyer-details-success' );
                    firstname.addClass( 'buyer-details-error' );
                }
                else {
                    firstname.removeClass( 'buyer-details-error' );
                    firstname.addClass( 'buyer-details-success' );
                }
                
                if ( lastname.val() == '' || lastname.val().length < 3 ) {
                    error = 1;
                    lastname.removeClass( 'buyer-details-success' );
                    lastname.addClass( 'buyer-details-error' );
                }
                else {
                    lastname.removeClass( 'buyer-details-error' );
                    lastname.addClass( 'buyer-details-success' );
                }
                
                if ( address.val() == '' || address.val().length < 3 ) {
                    error = 1;
                    address.removeClass( 'buyer-details-success' );
                    address.addClass( 'buyer-details-error' );
                }
                else {
                    address.removeClass( 'buyer-details-error' );
                    address.addClass( 'buyer-details-success' );
                }
                
                if ( city.val() == '' ) {
                    error = 1;
                    city.removeClass( 'buyer-details-success' );
                    city.addClass( 'buyer-details-error' );
                }
                else {
                    city.removeClass( 'buyer-details-error' );
                    city.addClass( 'buyer-details-success' );
                }
                
                if ( zipcode.val() == '' || zipcode.val().length < 4 ) {
                    error = 1;
                    zipcode.removeClass( 'buyer-details-success' );
                    zipcode.addClass( 'buyer-details-error' );
                }
                else {
                    zipcode.removeClass( 'buyer-details-error' );
                    zipcode.addClass( 'buyer-details-success' );
                }
                
                if ( state.val() == '' ) {
                    error = 1;
                    state.removeClass( 'buyer-details-success' );
                    state.addClass( 'buyer-details-error' );
                }
                else {
                    state.removeClass( 'buyer-details-error' );
                    state.addClass( 'buyer-details-success' );
                }
                
                if ( error == 0 ) {
                    return true;
                }
                else {
                    $( 'html, body' ).animate( { scrollTop: $( '#pqc-wrapper div#item-attributes' )
                        .parent()
                        .parent()
                        .parent()
                        .offset().top
                    }, 'slow', function () { alert( 'Please input shipping details.' ); return false; } );
                    
                    return false;
                }
                
            }
            
        } );
        
        
    }
    else if ( PQC_Page.page == 4 ) {
        
        var url = new URI(),
            obj = {
                order_id: undefined,
                order_status: undefined,
                payment_method: undefined,
                paymentId: undefined,
                PayerID: undefined,
                token: undefined,
                _wpnonce: undefined,
                'pqc-setup': undefined
            };

        url.removeQuery( obj );

        change_url( '', url );
        
    }
    
}) ( jQuery, window, document );