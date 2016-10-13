<?php

/**
 * Plugin Name: All in One Event Calendar Export
 * Version: 1.0.0
 * Description: All in One Event Calendar add on that exports events to a text file
 * Author: James Currie
 * Author URI: http://www.wunderdojo.com

 * Copyright 2016 wunderdojo LLC

 */
    
if( ! class_exists( 'AI1EC_Export' ) ){
 
    class AI1EC_Export{

	var $ai1ec_registry = null;

	var $repeating_events = array();

	var $events_to_display = null;

    var $start_date = null;

    var $end_date = null;


	
	function __construct(){
	    
	    add_action( 'admin_menu', array( $this, 'add_export_menu_item' ) );
	    
            add_action( 'wp_ajax_ai1ec_export_events', array( $this, 'export_events' ) );
	    
	    add_action( 'ai1ec_loaded', array( $this, 'access_ai1ec_registry' ) );
	    
	}
	
	//- add the Export Events submenu item to the Events menu
	function add_export_menu_item(){

	    add_submenu_page( 'edit.php?post_type=ai1ec_event', 'Export Events', 'Export Events', 'read', 'ai1ec_export_events', array( $this, 'display_export_options' ) );
	    
	}
	
	//- creates an admin screen where you can choose a range of dates and categories for events to export
	function display_export_options(){
	    
	    //- Load unminified js file for debugging
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '': '.min'; 
	    
	    //- load jQuery UI datepicker
	    wp_enqueue_script( 'jquery-ui-datepicker' );
	    
	    //- load in the js file to handle form submission
	    wp_enqueue_script( 'ajax-export-events', trailingslashit( plugins_url( '', __FILE__) ) .'js/ajax-export-events' . $suffix .'.js', true );
	    
	    //- load jQuery UI styles
	    wp_enqueue_style( 'jquery-ui-base', trailingslashit( plugins_url( '', __FILE__) ) .'css/jquery-ui-base.min.css', true );
	    
	    //- get the event categories as checkboxes
	    $terms = get_terms( array(
		'taxonomy' => 'events_categories',
		'hide_empty' => false
		) );

	    $checkboxes = "<tr><td scope='row'><label for = 'term-all'>All Categories</label></td>";
	    $checkboxes .= "<td><input type='checkbox' name='term[]' value='all' checked='checked'></td></tr>";
	    
	    foreach ( $terms as $term ){
		$checkboxes .= "<tr><td scope='row'><label for = 'term-{$term->slug}'>{$term->name}</label></td>";
		$checkboxes .= "<td><input type='checkbox' name='term[]' value='{$term->term_id}'></td></tr>";
	    }

	    $form = <<<FORM
		    <div class='wrap'>
		    <h1>Export Events For Print</h1>
		    <form>
			<table class='wp-list-table widefat fixed striped'>
			   <tr><td scope='row' width='10%'><label>Start Date:</label></td><td><input type='text' name='start_date' id='start_date' class='datepicker' value='$start'></td></tr>
			    <tr><td scope='row'><label>End Date:</label></td><td><input type='text' name='end_date' id='end_date' class='datepicker' value='$end'></td></tr>
			    <tr><th colspan='2'>Select Event Categories to Include</th></tr>
			    {$checkboxes}
			 </table>
			<a href='' id='datalink' download='events.txt'></a>
			<div class='spinner' style='float:none; display:block;'></div>
			<input type='submit' name='export_events' id='export_events' data-action='ai1ec_export_events' class='button' value='Export Events'>
		    </form>
		    </div
FORM;
	    
	    echo $form;
	    
	}
	
	//- hooks into the All in One Event Calendar extensions loaded call to get a copy of the object registry
	public function access_ai1ec_registry( Ai1ec_Registry_Object $registry ){
	    
	    $this->ai1ec_registry = &$registry;
  
	}
	
	/**
	 * Receives the ajax export call and returns the matching events
	 * 
	 * @param string $start_date -- mm-dd-yyyy
	 * @param string $end_date -- mm-dd-yyyy
	 * @param array $categories -- either 'all' or one or more tax term IDs
	 * 
	 * @return json array
	 */    
	public function export_events(){
	    
	    $params = extract( $_POST );
   
        $this->start_date = DateTime::createFromFormat( 'm-d-Y', $start_date );

        $this->end_date = DateTime::createFromFormat( 'm-d-Y H:i:s', $end_date . ' 23:59:59' );
	    
	    $events = $this->get_events( $start_date, $end_date, $categories );

	    $response = $this->format_events( $events );
	    
	    wp_send_json( array( $response ) );

	}
	
	/**
	 * Uses all-in-one-event-calendar/app/model/search.php method get_events_between to find events that 
	 * match the search criteria.
	 * 
	 * Based on: https://gist.github.com/MattReimer/8a9248d478f22903283a
	 * 
	 * @param string $start_date -- mm-dd-yyyy
	 * @param string $end_date -- mm-dd-yyyy
	 * @param array $categories -- either 'all' or one or more tax term IDs
	 * 
	 * @return array $events -- array of event objects
	 */
	
	public function get_events( $start_date, $end_date, $categories ){

	    $date_system = $this->ai1ec_registry->get( 'date.system' );
	    
	    $search = $this->ai1ec_registry->get( 'model.search' );
	    
	    //- ai1ec wants the date in yyyy-mm-dd format and as an Ai1ec_Date_Time object
	    $local_date = $this->ai1ec_registry->get( 'date.time', $date_system->current_time(), 'sys.default' );
	    
	    $start_time = clone $local_date;
	    
	    $start = explode( '-', $start_date );
	    
	    $start_time->set_date( $start[2], $start[0], $start[1] )->set_time( 0, 0, 0 );
	    
	    $end_time = clone $start_time;
	    
	    $end = explode( '-', $end_date );
	    
	    $end_time->set_date( $end[2], $end[0], $end[1] )->set_time( 23, 59, 59 );
	    
	    $categories = ( isset ( $categories ) && ! in_array( 'all', $categories ) ) ? array( 'cat_ids' => $categories ) : array();
	    
	    $events = $search->get_events_between(
		    $start_time,
		    $end_time,
		    $categories,
		    false, //- spanning -- whether to show events that span this period even if they start/end outside the selected dates
		    false //- whether or not we're searching for events on a single day
		    );

	    return $events;
	    
	}
	
	/**
	 * Given an array of event objects formats them for output in RTF format
	 * 
	 * Based on: https://gist.github.com/lukaspawlik/045dbd5b517a9eb1cf95
	 * 
	 * @param array $events -- an array of event objects
	 * 
	 */
	public function format_events( $events ){

	    $view = $this->ai1ec_registry->get( 'view.calendar.view.agenda', $this->ai1ec_registry->get( 'http.request.parser' ) );
	    
	    $dates = $view->get_agenda_like_date_array( $events );
	    
	    $content = '{\rtf1\ansi\deff0';
    
	    foreach ( $dates as $date ) {

            //- track whether there are any events to show and only output the date if there are
            $this->events_to_display = false;
            $single_day_content = null;
            $multi_day_content = null;

            $all_events = $date['events']['allday'] + $date['events']['notallday'];
		   
		foreach ( $all_events as $instance ) {

                if( $instance['is_multiday'] == 1 ){
                    $multi_day_content .= $this->process_event( $instance, $date );
                }
                else{
                    $single_day_content .= $this->process_event( $instance, $date );
                }
		    }
		
            if( $this->events_to_display ) { 
   
                //- set up the single day header
                $display_date = sprintf( '{\b\caps %1$s, %2$s %3$s}{\line}', 
	                $date['full_weekday'],
	                $date['full_month'],
	                $date['day'] );

                $content .= $multi_day_content . $display_date . $single_day_content; 

            }
            
	    }
	    
	    $content.= "}";
		
	    return $content;
	}


	/**
	 * Check to see if an event should be displayed (repeating event on first occurrence or one-time event)
	 * 
	 * @param object $instance
     * @param object $date
     * @param array $all_events
	 * 
	 * @return string $details -- the event description formatted for RTF
	 */
	public function process_event( $instance, $date ){
     
	    $details = null;
        $recurring_dates = null;

	    if( ! in_array( $instance['post_id'], $this->repeating_events, true ) ){
		$this->events_to_display = true;
		
		//- get the following event properties: https://gist.github.com/lukaspawlik/c4a0e605414542e844dd
		$event = $this->ai1ec_registry->get( 'model.event', $instance['post_id'] );
		
		//- handle multi-day events
		if( $instance['is_multiday'] == 1 ){
		    $this->repeating_events[] = $instance['post_id'];

		    $end = date( 'l, F d', strtotime( "{$instance['enddate_info']['month']} {$instance['enddate_info']['day']} {$instance['enddate_info']['year']}" ) );

		    $display_date = sprintf( '{\b\caps %1$s, %2$s %3$s - %4$s} {\line}', 
			    $date['full_weekday'],
			    $date['full_month'],
			    $date['day'],
			$end );

		    $details .= $display_date;
		}
		
		//- check if the event is recurring
		if( $event->get( 'recurrence_rules' ) ){
		    
		    $this->repeating_events[] = $instance['post_id'];

            //- for rule based recurrences -- ex: daily every 1 day -- parse the rule to text for display
            if( ! $event->get( 'recurrence_dates' ) ){
                
                $rule_parser = $this->ai1ec_registry->get( 'recurrence.rule' );
           
                $rule_text = $rule_parser->rrule_to_text( $event->get( 'recurrence_rules' ) );

                $recurring_dates = sprintf( '{\line}{\i %1$s\i0}{\line}', 
			        $rule_text );

            } 
            else{
		        //- Handle recurrence dates here
		        $rdates = explode( ',', $event->get( 'recurrence_dates' ) );
		        
		        foreach ( $rdates as $rdate ){

	                $rd = strtotime( $rdate );

                    if( $rd >= $this->start_date->getTimestamp() && $rd <= $this->end_date->getTimestamp() ){
                        
			            $end_dates[] = date( 'l, F d', strtotime( $rdate ) );

                    }
		        
		        }
		    
		        $end = implode( ', ', $end_dates );

		        $recurring_dates = sprintf( '{\line}{\i %1$s: %2$s\i0}{\line}', 
			    'Recurs on',
			    $end );
            }

		}

		//- get the event details
		$details .= $this->get_event_details( $instance, $event ) . $recurring_dates . ' \line' . ' \line';
	    }

	    return ( $details ) ? $details : '';
	}
	
	/**
	 * Given a post ID get the details and return a formatted string to be output
	 * 
	 * @param array $instance -- an array of event properties
	 * @param object $event -- an ai1ec event object
	 * @return string $event -- a string with the event details for print
	 * 
	 * $instance has the following keys:
	 *	post_id
	 *	venue
	 *	filtered_title
	 *	filtered_content
	 *	short_start_time: ex: 5:44pm
	 *	timespan_short: ex: Oct. 17 @ 5:44 pm - 6:44 pm
	 *	
	 */
	public function get_event_details( $instance, $event ){
	    
	    //- Title
	    $title_clean = $this->format_for_rtf( $event->get( 'post' )->post_title );

	    $output .= '{\b ' . $title_clean . '}{\line}';

	    //- Description
	    $output .= $this->format_for_rtf( wp_strip_all_tags( $event->get( 'post' )->post_content ) );
	    
	    //- Start time -- only for non all day events
	    if( ! $instance['is_allday'] ){
		//- ai1ec outputs an emdash instead of a hyphen between the times which messed up the RTF formatting
		$output .= " " . str_replace( '–', '-', explode( '@', $instance['timespan_short'] )[1] ) .'.';
	    }

	    //- Ticket price
	    $output .= ( $instance['is_free'] ) ? 'FREE.' : ( isset( $instance['cost'] ) ? $instance['cost'] . '.' : '' );

	    //- check for a venue
	    if( $instance['venue'] ){

		$term = $this->ai1ec_registry->get( 'model.venue' )->get_venue_term( $instance['venue'], false );

		$venue = $this->ai1ec_registry->get( 'model.venue', $term['term_id'] );

		//- Venue name
		$output .= ' {\b ' . $instance['venue'] . '}: ';

		//- Venue address
		if( $event->get( 'address' ) ){
		    //- strip out the country
		    $output .= str_replace( ', USA', '', $event->get( 'address' ) ) . '; ';  
		}
		
		//- Show the ticket URL if there is one or else show the venue URL or organizer URL if there is one. Don't show both.
		//- Ticket URL
		if( $event->get( 'ticket_url' ) ){
			$output .= $this->clean_url( $event->get( 'ticket_url' ) );  
		}
		    //- Venue URL
		    else if( $venue->get( 'url' ) ){
			$output .= $this->clean_url( $venue->get( 'url' ) );
		    }

		//- Organizer URL
		else if( $event->get( 'contact_url' ) ){
			$output .= $this->clean_url( $event->get( 'contact_url' ) );
		}

	    }
	   
	    
	    return $output;
	    
	}

	//- fix punctuation issues caused by people cutting-and-pasting from Word docs
	public function format_for_rtf( $text ){

	    return iconv('UTF-8', 'ASCII//TRANSLIT', $text);  

	}

	//- clean up URLs for print
	public function clean_url( $link ){

	    $link = html_entity_decode( $link );

	    //- remove http/s
	    $link = preg_replace("(^https?://)", "", $link );

	    return $link;

	}

	
    } //- end of class definition
    
    $ai1ec_export = new AI1EC_Export();
}