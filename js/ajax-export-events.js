jQuery( document ).ready( function( $ ){

	var batch_completed = false;
	
	//- instantiate the datepicker
	$( ".datepicker" ).datepicker( {
		changeMonth: true,
		changeYear: true,
		yearRange: 'c-100:c',
		dateFormat: 'mm-dd-yy'
	} );
	    
	//- ajaxify the form submission and generate the file
    $( '#export_events' ).click( function( event ){
        event.preventDefault();
        
		//- grab the start and end dates. format is mm-dd-yyyy
		var start = $( '#start_date' ).val();
		var end = $( '#end_date' ).val();
		
		//- get the selected category(ies)
		var categories =  $( 'input[name="term[]"]:checked').map( function(){
			return this.value;
		} ).get();

        //- change the download name
		var filename = 'events-' + start + '-to-' + end + '-export.rtf';
        var datalink = $( '#datalink' );
        datalink.prop( { 'download':filename } );

		//- ai1ec saves dates as Unix timestamps in GMT. 
        $( this ).prev( '.spinner' ).addClass( 'is-active' );
        
        //- sends the ajax form submit
        $.ajax( {
			method: "POST",
			url: ajaxurl,
			data: { 
					action: 'ai1ec_export_events',
					start_date: start,
					end_date: end,
					categories: categories
				},
            success: function( data ) {
                        $('.spinner').removeClass('is-active');
                        datalink.attr( "href", 'data:Application/rtf,' + encodeURIComponent(data))[0].click();

                    }   
			} );

    });
	
} );
		 

