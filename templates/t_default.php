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
$thumbnail_src = toplytics_get_thumbnail_src($post_id, $thumbnail);
if (@file_get_contents($thumbnail_src)):
	echo '<img src="'.$thumbnail_src.'" width="315" height="50" border="0" alt="'.get_the_title($post_id).'" />';
endif;
?>
		  <div class="toplytics-box-title"><?php echo $post_title; ?>
<?php
	if ( $show_views ) { 
		echo '<br /><span class="toplytics-box-views">';
		printf(__('%d Views', TOPLYTICS_TEXTDOMAIN), $pv);
		echo '</span>';
} ?>
		  </div>
		</a>
		</div><!-- end of .toplytics-box-bg -->
	</li>
		<?php $number--; ?>
		<?php endforeach; ?>
</ol>
</div><!-- end of .toplytics-box -->
