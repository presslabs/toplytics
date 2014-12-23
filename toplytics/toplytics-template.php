<?php
/**
 * $toplytics_results - all results as array with post_id as key and pageviews as value
 * $showviews         - true/false, show/hide the post pageviews
 */
if ( empty( $toplytics_results ) ) {
	?><p><?php _e( 'No data is available!', 'toplytics' ); ?></p><?php
} else {
	?>
	<ol>
	<?php foreach ( $toplytics_results as $post_id => $post_views ) : ?>
	<li>
	<a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
	<?php echo get_the_title( $post_id ); ?>
	</a>
	<?php
		if ( $showviews ) {
			echo '<span class="post-views">&nbsp;';
			printf( __( '%d Views', 'toplytics' ), $post_views );
			echo '</span>';
		}
	?>
	</li>
	<?php endforeach;
}
?>
</ol>
