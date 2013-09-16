<?php
/* Toplytics Template: Default
 */
?>
<div class="toplytics-box">
	<?php if ( $title ) echo $before_title . $title . $after_title; ?>
<ol>

<?php foreach ($results[$period] as $post_id => $pv) : ?>

	<?php if ($number <= 0) break; // stop when $number posts are shown ?>

	<li class="top<?php echo ($counter-$number+1); ?>">
		<div class="toplytics-box-bg">
<?php   $post_categories = wp_get_post_categories( $post_id );
        $category = get_category($post_categories[0]);
        $post_title = get_the_title($post_id);

		$lang = "";
		if ( isset($_GET['lang']) )
			$lang = "&lang=" . $_GET['lang'];
?>
        <a class="category-<?php echo $category->slug; ?>" href="<?php echo get_permalink($post_id) . $lang; ?>" title="<?php echo esc_attr($post_title); ?>">
<?php
if ($thumbnail == 'featuredimage') {
	if ( has_post_thumbnail( $post_id ) ) // check if the post has a Post Thumbnail assigned to it.
		echo get_the_post_thumbnail($post_id, 'thumbnail');
}
?>
		  <div class="toplytics-box-title"><?php echo $post_title; ?>
<?php if ( $show_views ) { ?><br /><span class="toplytics-box-views">
<?php printf(__('%d Views', TOPLYTICS_TEXTDOMAIN), $pv); ?></span>
<?php } ?>
		  </div>
		</a>
		</div><!-- end of .toplytics-box-bg -->
	</li>
		<?php $number--; ?>
		<?php endforeach; ?>
</ol>
</div><!-- end of .toplytics-box -->
