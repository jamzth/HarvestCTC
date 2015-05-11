<?php
	/* Event tag archive */
	
	get_header(); 
	$term = get_queried_object();
	
	harvest_title_bar( sprintf( _x( '%s events', 'Event category', 'harvest'), $term->name) );
?>
		<div class="content_wrap">

			<div class="grid-container content">
			
<?php harvest_get_tax_dropdown( 'ctc_event_category' ); ?>
				
<?php
	do_action('__before_loop');
	
	if (have_posts()) :  
		if( $term -> description ): 	?>
				<div class="grid-100 ctc-event-category-desc" >
					<p><?php echo $term->description; ?></p>
				</div>
<?php	
	endif;
	while (have_posts()) : the_post(); 
		
	get_template_part( 'templates/event', 'grid' );

	endwhile; endif; 
	do_action('__after_loop'); 
?>

			</div> <!-- .content.grid-container -->

		</div> <!-- .content_wrap -->
		<!-- END CONTENT -->

		<div class="grid-container">
<?php harvest_pagination_new(); ?>
		</div>
		
<?php

	get_footer();
	

		
