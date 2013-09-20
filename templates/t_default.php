<?php
/* Toplytics Template: Default
 */
?>
<?php if ( $title )
		echo $before_title . $title . $after_title; ?>
<ol>
	<?php foreach ( $toplytics_results as $post_id => $post_views ) : ?>
	<li>
        <a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"><?php echo get_the_title( $post_id ); ?></a>
	<?php if ( $show_views )
		printf( __( '%d Views', TOPLYTICS_TEXTDOMAIN), $post_views ); ?>
	</li>
<?php endforeach; ?>
</ol>

