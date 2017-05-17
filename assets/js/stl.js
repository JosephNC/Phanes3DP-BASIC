( function( $, window, document, undefined ) {

    //Function Definitions
    //====================================================================
    function loadSTL() {
        
        //3D Object loading
        $.each( PQC.url, function( index, value ) {
            var canvas = document.getElementById( 'cv' + index );
            var viewer = new JSC3D.Viewer( canvas );
            var ctx = canvas.getContext( '2d' );
            
            viewer.enableDefaultInputHandler( true );
            viewer.replaceSceneFromUrl( value );
            //viewer.update();
            viewer.setParameter( 'InitRotationX', 20 );
            viewer.setParameter( 'InitRotationY', 20 );
            viewer.setParameter( 'InitRotationZ', 0 );
            viewer.setParameter( 'ModelColor', '#7fca18' );
            viewer.setParameter( 'BackgroundColor1', '#FFFFFF' );
            viewer.setParameter( 'BackgroundColor2', '#FFFFFF' );
            viewer.setParameter( 'RenderMode', 'flat' );
            viewer.setParameter( 'Definition', 'high' );
            viewer.init();
            viewer.update();
            ctx.font = '12px Courier New';
            ctx.fillStyle = '#FF0000';    
        } );
    }
    //END Function Definitions
    //====================================================================

    loadSTL();

    //Render mode selection events
    $( "div#render-modes a" ).click( function( evt ){
        $mode = $( this ).attr( "href" ).substr(1);
        viewer.setRenderMode( $mode );
        viewer.update();
        return false;
    });
    
}) ( jQuery, window, document );