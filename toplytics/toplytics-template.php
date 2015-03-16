<?php
/**
 * $toplytics_results - all results as array with post_id as key and pageviews as value
 * $showviews         - true/false, show/hide the post pageviews
 */
if ( ! empty( $toplytics_results ) ) {
	$posts_list = get_posts( array(
		'posts_per_page' => count( $toplytics_results ), // the number of posts being displayed
		'post__in'       => array_keys( $toplytics_results ),
		'orderby'        => 'post__in',
	));
	?><ol><?php
	global $post;
	foreach ( $posts_list as $post ) : setup_postdata( $post );
		?>
		<li>
		<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></a>
		<?php
		if ( $showviews ) {
			echo '<span class="post-views">&nbsp;';
			printf( __( '%d Views', 'toplytics' ), $toplytics_results[ get_the_ID() ] ); // pageviews
			echo '</span>';
		}
		?></li><?php
	endforeach; wp_reset_postdata();
	?></ol><?php
}
