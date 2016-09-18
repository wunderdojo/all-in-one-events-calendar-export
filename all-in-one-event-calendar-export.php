<?php

/**
  Plugin Name: All in One Event Calendar Export
  Version: 1.0.0
  Description: All in One Event Calendar add on that exports events to a text file
  Author: James Currie
  Author URI: http://www.wunderdojo.com

  ------------------------------------------------------------------------
  Copyright 2016 wunderdojo LLC

 */
    
if( ! class_exists( 'AI1EC_Export' ) ){
 
    class AI1EC_Export{
	
	function __construct(){
	    
	    add_action( 'admin_menu', array( $this, 'add_export_menu_item' ) );
	    
            add_action( 'wp_ajax_ai1ec_export_events', array( $this, 'export_events' ) );
	    
	}
	
	//- add the Export Events submenu item to the Events menu
	function add_export_menu_item(){

	    add_submenu_page( 'edit.php?post_type=ai1ec_event', 'Export Events', 'Export Events', 'read', 'ai1ec_export_events', array( $this, 'display_export_options' ) );
	    
	}
	
	//- creates an admin screen where you can choose a range of dates and categories for events to export
	function display_export_options(){
	    
	    //- load jQuery UI datepicker
	    wp_enqueue_script( 'jquery-ui-datepicker' );
	    //- load in the js file to handle form submission
	    wp_enqueue_script( 'ajax-export-events', trailingslashit( plugins_url( '', __FILE__) ) .'js/ajax-export-events.js', true );
	    
	    //- load jQuery UI styles
	    wp_enqueue_style( 'jquery-ui-base', trailingslashit( plugins_url( '', __FILE__) ) .'css/jquery-ui-base.min.css', true );
	    
	    //- get the event categories as checkboxes
	    $terms = get_terms( array(
		'taxonomy' => 'events_categories',
		'hide_empty' => false
		) );

	    $checkboxes = "<tr><td scope='row'><label for = 'term-all'>All Categories</label></td>";
	    $checkboxes .= "<td><input type='checkbox' name='term[] value='all' checked='checked'></td></tr>";
	    
	    foreach ( $terms as $term ){
		$checkboxes .= "<tr><td scope='row'><label for = 'term-{$term->slug}'>{$term->name}</label></td>";
		$checkboxes .= "<td><input type='checkbox' name='term[] value='{$term->term_id}'></td></tr>";
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
			<input type='submit' name='export_events' id='export_events' class='button' value='Export Events'>
		    </form>
		    </div>
		    
FORM;
	    
	    echo $form;
	    
	}
	
	
	
	
    }
    
    $ai1ec_export = new AI1EC_Export();
}
