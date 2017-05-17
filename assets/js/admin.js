jQuery(document).ready( function( $ ) {
    
    $( "#expiry_date" ).datepicker({
        dateFormat: "yy-mm-dd",
        showButtonPanel: true,
        minDate: 0,
    });
    
    if ( $( "#pqc_condition_type" ).val() == 'range' ) {

        $( '#pqc_condition_value2' ).css({'display' : 'initial'});
        
        $( '#symbol' ).text( '( ' + $( "#pqc_condition_type" ).val() + ' )' );
        
    }
    else {
            
        $( '#pqc_condition_value2' ).css({'display' : 'none'});
        
        $( '#symbol' ).text( '( ' + $( "#pqc_condition_type" ).val() + ' )' );
        
    }
    
    $( '#pqc_condition_type' ).change( function () {
        
        var val = $(this).val();
        
        if ( val == 'range' ) {
            
            $( '#pqc_condition_value2' ).css({'display' : 'initial'});
            
        } else {
            
            $( '#pqc_condition_value2' ).css({'display' : 'none'});
        }
        
        $( '#symbol' ).text( '( ' + val + ' )' );
        
    } );
    
    $( 'div.pqc-wrapper' ).prepend( $( 'div.pqc-inner-notice' ) );    
    
    /**
    * For Editing
    * 
    */
    $( 'div.row-actions a.editinline.quote' ).click( function ( event ) {
        
        event.preventDefault();
        
        $( 'h2#edit-quote' ).show();
        $( 'h2#add-quote' ).hide();
        
        $( "input[name='pqc_quote_add']" ).hide();
        $( "input[name='pqc_quote_update']" ).show();
        $( "button#cancel_edit" ).show();
        
        // Get needed data from table
        var parent = $(this).parents( 'tr' ),
            id = $(this).attr( 'data-id' ),
            quoteType = parent.find( 'td.quote_type input.quote_type_hidden' ).val(),
            condType = parent.find( 'td.quote_type input.cond_type_hidden' ).val(),
            condVal1 = parent.find( 'td.quote_type input.cond_val1_hidden' ).val(),
            condVal2 = parent.find( 'td.quote_type input.cond_val2_hidden' ).val(),
            quoteCost = parent.find( 'td.quote_type input.quote_cost_hidden' ).val();
            
            
        if ( condType == 'range' ) {
            
            $( 'form#addquote' ).find( "input#pqc_condition_value2" ).show();
            
        } else {
            
            $( 'form#addquote' ).find( "input#pqc_condition_value2" ).hide();
        }
        
        $( '#symbol' ).text( '( ' + condType + ' )' );
            
        
        // Pass needed data to edit form
        $( 'form#addquote' ).find( 'input#pqc_id' ).val( id );
        $( 'form#addquote' ).find( "select#pqc_quote_type option[value='" + quoteType + "']" ).attr( 'selected', 'selected' );
        $( 'form#addquote' ).find( "select#pqc_condition_type option[value='" + condType + "']" ).attr( 'selected', 'selected' );
        $( 'form#addquote' ).find( "input#pqc_condition_value1" ).val( condVal1 );
        $( 'form#addquote' ).find( "input#pqc_condition_value2" ).val( condVal2 );
        $( 'form#addquote' ).find( "input#pqc_quote_cost" ).val( quoteCost ); 
        
        return false;
        
    } );
    
    /**
    * For Editing
    * 
    */
    $( 'div.row-actions a.editinline.material' ).click( function ( event ) {
        
        event.preventDefault();
        
        $( 'h2#edit-material' ).show();
        $( 'h2#add-material' ).hide();
        
        $( "input[name='pqc_material_add']" ).hide();
        $( "input[name='pqc_material_update']" ).show();
        $( "button#cancel_edit" ).show();
        
        // Get needed data from table
        var parent = $(this).parents( 'tr' ),
            id = $(this).attr( 'data-id' ),
            materialName = parent.find( 'td.material_name input.material_name_hidden' ).val(),
            materialDesc = parent.find( 'td.material_name input.material_description_hidden' ).val(),
            materialCost = parent.find( 'td.material_name input.material_cost_hidden' ).val();   
        
        // Pass needed data to edit form
        $( 'form#addmaterial' ).find( 'input#pqc_id' ).val( id );
        $( 'form#addmaterial' ).find( "input#pqc_material_name" ).val( materialName );
        $( 'form#addmaterial' ).find( "textarea#pqc_material_description" ).val( materialDesc );
        $( 'form#addmaterial' ).find( "input#pqc_material_cost" ).val( materialCost ); 
        
        return false;
        
    } );
    
    $( 'button#cancel_edit' ).click( function () {
        
        $( 'h2#edit-quote' ).hide();
        $( 'h2#edit-material' ).hide();
        $( 'h2#add-quote' ).show();
        $( 'h2#add-material' ).show();
        
        $( "input[name='pqc_quote_add']" ).show();
        $( "input[name='pqc_material_add']" ).show();
        $( "input[name='pqc_quote_update']" ).hide();
        $( "input[name='pqc_material_update']" ).hide();
        
        $( 'form#addmaterial' ).find( "input#pqc_material_name" ).val( '' );
        $( 'form#addmaterial' ).find( "textarea#pqc_material_description" ).val( '' );
        $( 'form#addmaterial' ).find( "input#pqc_material_cost" ).val( '' );
        
        $( "button#cancel_edit" ).hide(); 
        
        return false;
    } );

});
