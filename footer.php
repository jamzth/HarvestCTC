	<footer id="footer">
<?php wp_footer(); ?>
<?php if ( harvest_option( 'google_analytics' ) <> "" && ! is_user_logged_in() ) { echo stripslashes( harvest_option('google_analytics' ) ); } ?>
</body>
</html>