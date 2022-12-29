<?php
/**
 * Displays footer site info.
 *
 * @package SeedletChild
 */

?>

<div class="site-info">
	<?php $blog_info = get_bloginfo( 'name' ); ?>
	<?php if ( ! empty( $blog_info ) ) : ?>
		<a class="site-name" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">&copy 2007-<?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></a><span class="comma">,</span>
	<?php endif; ?>
	<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'seedlet' ) ); ?>" class="imprint">
		<?php
		/* translators: %s: WordPress. */
		printf( esc_html__( 'Proudly powered by %s.', 'seedlet' ), 'WordPress' );
		?>
	</a>
	<?php
	if ( function_exists( 'the_privacy_policy_link' ) ) {
		the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
	}
	?>
</div><!-- .site-info -->
