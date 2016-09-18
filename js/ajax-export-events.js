jQuery( document ).ready( function( $ ){

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
        
		//- grab the start and end dates
		var start = $( '#start_date' ).val();
		var end = $( '#end_date' ).val();
		
		var filename = 'events-' + start + '-to-' + end + '-export.docx';
        var datalink = $('#datalink');
        //- change the download name
        datalink.prop( { 'download':filename } );
		
		console.log( start, end );
		
		//- ai1ec saves dates as Unix timestamps in GMT. 
		
        var action = 'admin-ajax-'+ $(this).data('action');
        var form_data = $(this).parents('form').serializeArray();
        
        if(batch_completed == true){
            $('.update-now').removeClass( 'updated-message' );
        }

        $('.update-now').addClass('updating-message');
        generate_pdf(action);
        
        //- sends a call to ajax-pdf-export.controller.php
        function generate_pdf(){
            var method = (num_selected > 0) ? 'selected' : 'batch';
            $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    dataType: "json",
                    data: { 
                            action: 'ai1ec_export_events',
                            selected: selected_ids,
                            form_data: form_data
                        }
                    })
            
            //- Successful response
            .done( function(response, status, jqXHR) {
                batch_completed = response.completed;
                if (batch_completed == true) {
                    $('.update-now').removeClass('updating-message').addClass('updated-message');
                    $('#ajax-target').before(response.msg);
                }
            })

            //- Error catching
            .fail(function(jqhr, textStatus, error){
                var err = textStatus + ", " + error;
                console.log("Request Failed: " + err);
            });
        }       

    });
	
} );
		 

