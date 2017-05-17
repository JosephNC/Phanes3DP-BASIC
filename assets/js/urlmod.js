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

jQuery(document).ready( function( $ ) {
    
    if ( typeof PQC_Admin !== 'undefined' ) {
        
        if ( PQC_Admin.do_url_mod == true ) {
            
            var url = new URI(),
                obj = {
                    action: undefined,
                    quote: undefined,
                    order: undefined,
                    material: undefined,
                    _wpnonce: undefined,
                    'pqc-setup': undefined
                };

            url.removeQuery( obj );

            change_url( '', url );
            
        }    
    }
    
});