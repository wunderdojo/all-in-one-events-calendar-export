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
	    wp_enqueue_script( 'ajax-export-events', trailingslashit( plugins_url( '', __FILE__) ) .'js/ajax-export-events.' . $suffix .'js', true );
	    
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
	 * Given an array of event objects formats them for output as a Word doc
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
		
		//- set up the header for each date
		$content .= sprintf( '{\b\caps %1$s, %2$s %3$s} {\line\line}', 
					$date['full_weekday'],
					$date['full_month'],
					$date['day'] );
		
		foreach ( $date['events']['allday'] as $instance ) {
		
			//- get the event details
			$content .= $this->get_event_details( $instance );

		}
		
		foreach ( $date['events']['notallday'] as $instance ) {
		    
			//- get the event details
			$content .= $this->get_event_details( $instance );
			
		}
	    }
	    
	    $content.= "}";
		
	    return $content;
	}
	
	/**
	 * Given a post ID get the details and return a formatted string to be output
	 * 
	 * @param object $instance -- an ai1ec event object
	 * @return string $event -- a string with the event details for print
	 * 
	 * $instance has the following properties:
	 *	post_id
	 *	venue
	 *	filtered_title
	 *	filtered_content
	 *	short_start_time: ex: 5:44pm
	 *	timespan_short: ex: Oct. 17 @ 5:44 pm - 6:44 pm
	 *	
	 */
	public function get_event_details( $instance ){
	    
	    //- get the following event properties: https://gist.github.com/lukaspawlik/c4a0e605414542e844dd
	    $event = $this->ai1ec_registry->get( 'model.event', $instance['post_id'] );
	    
	    //- Title
	    $output .= '{\b ' . $event->get( 'post' )->post_title . '}{\line}';
	    
	    //- Description
	    $output .= $event->get( 'post' )->post_content;
	    
	    //- Start time -- only for non all day events
	    if( ! $instance['is_allday'] ){
		//- ai1ec outputs an emdash instead of a hyphen between the times which messed up the RTF formatting
		$output .= " " . str_replace( '–', '-', explode( '@', $instance['timespan_short'] )[1] );
	    }
	    
	    //- check for a venue
	    if( $instance['venue'] ){

		$term = $this->ai1ec_registry->get( 'model.venue' )->get_venue_term( $instance['venue'], false );

		$venue = $this->ai1ec_registry->get( 'model.venue', $term['term_id'] );

		//- Venue name
		$output .= ' {\b ' . $instance['venue'] . '}: ';

		//- Venue address
		if( $event->get( 'address' ) ){
		    $output .= $event->get( 'address' ) . '; ';  
		}
		
		//- Venue URL
		if( $venue->get( 'url' ) ){
		    $output .= $venue->get( 'url' ) ;
		}
	    }
		
	    //- Ticket URL
	    if( $event->get( 'ticket_url' ) ){
		$output .= $event->get( 'ticket_url' ) . '; ';  
	    }
	    
	    //- Organizer URL
	    if( $event->get( 'contact_url' ) ){
		$output .= $event->get( 'contact_url' ) . ';';
	    }
	    
	    $output .= ' \line' . ' \line';
	    
	    return $output;
	    
	}
	
	
    } //- end of class definition
    
    $ai1ec_export = new AI1EC_Export();
}
