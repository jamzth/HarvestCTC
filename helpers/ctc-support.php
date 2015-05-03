<?php 
// Add Church Theme Content support

function harvest_ctc_notice(){
	echo '<div class="error"><p>'. __( 'Church Theme Content Plugin is required!', 'harvest' ).'</p></div>';
}
function harvest_ctcex_notice(){
	echo '<div class="error"><p>'. __( 'CTC_Extender Plugin is required!', 'harvest' ).'</p></div>';
}
	
function harvest_add_ctc(){
	 
	if( ! class_exists( 'Church_Theme_Content' ) ) {
		add_action( 'admin_notices', 'harvest_ctc_notice' );
		return;
	}
	if( ! class_exists( 'CTC_Extender' ) ) {
		add_action( 'admin_notices', 'harvest_ctcex_notice' );
		function ctcex_get_option( $option ){
			return '';
		}
		return;
	}
	
	add_theme_support( 'church-theme-content' );
	
	// Events
	add_theme_support( 'ctc-events', array(
			'taxonomies' => array(
				'ctc_event_category',
			),
			'fields' => array(
				'_ctc_event_start_date',
				'_ctc_event_end_date',
				'_ctc_event_start_time',
				'_ctc_event_end_time',
				'_ctc_event_recurrence',
				'_ctc_event_recurrence_end_date',
				'_ctc_event_recurrence_period',       // Not default in CTC
				'_ctc_event_recurrence_monthly_type', // Not default in CTC
				'_ctc_event_recurrence_monthly_week', // Not default in CTC 
				'_ctc_event_venue',
				'_ctc_event_address',
			),
			'field_overrides' => array()
	) );
	
	// Sermons
	add_theme_support( 'ctc-sermons', array(
			'taxonomies' => array(
					'ctc_sermon_topic',
					'ctc_sermon_series',
					'ctc_sermon_speaker',
			),
			'fields' => array(
					'_ctc_sermon_video',
					'_ctc_sermon_audio',
			),
			'field_overrides' => array()
	) );
	 
	// People
	add_theme_support( 'ctc-people', array(
			'taxonomies' => array(
					'ctc_person_group',
			),
			'fields' => array(
					'_ctc_person_position',
					'_ctc_person_phone',
					'_ctc_person_email',
					'_ctc_person_urls',
			),
			'field_overrides' => array()
	) );

	// Locations
	add_theme_support( 'ctc-locations', array(
			'taxonomies' => array(),
			'fields' => array(
					'_ctc_location_address',
					'_ctc_location_phone',
					'_ctc_location_times',
					'_ctc_location_slider',
			),
			'field_overrides' => array()
	) );
	
}

// This helper is used to get an expression for recurrence
function harvest_get_recurrence_note( $post_obj ) {
	return ctcex_get_recurrence_note ( $post_obj );
}

function harvest_get_default_data( $post_id ) {
	$data = array(
		'permalink'   => get_permalink( $post_id ),
		'name'        => get_the_title( $post_id ),
	);
	return $data;
}

// Get sermon data for use in templates
function harvest_get_sermon_data( $post_id ){
	$default_img =  harvest_option( 'logo', '' ); 
	if( class_exists( 'CTC_Extender' ) )
		return ctcex_get_sermon_data( $post_id, $default_img );
	else
		return harvest_get_default_data( $post_id ); 
}

// Get event data for use in templates
function harvest_get_event_data( $post_id ){
	if( class_exists( 'CTC_Extender' ) )
		return ctcex_get_event_data( $post_id );
	else
		return harvest_get_default_data( $post_id ); 
}

// Get location data for use in templates
function harvest_get_location_data( $post_id ){
	if( class_exists( 'CTC_Extender' ) )
		return ctcex_get_location_data( $post_id );
	else
		return harvest_get_default_data( $post_id ); 
}

// Get person data for use in templates
function harvest_get_person_data( $post_id ){
	if( class_exists( 'CTC_Extender' ) )
		return ctcex_get_person_data( $post_id );
	else
		return harvest_get_default_data( $post_id ); 
}