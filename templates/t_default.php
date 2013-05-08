<?php
/*
 * Toplytics Template: Default
 */
?>

<div class="toplytics-box">

<?php if ( $title ) echo $before_title . $title . $after_title; $count = 0; // widget title ?>
			  
<ol>				
	<?php foreach ($results[$type] as $post_id => $pv) : ?>
	<?php if ($number <= 0) break; ?>

	<li class="top<?php echo ($counter-$number+1); ?>">
		<div class="toplytics-box-bg">
<?php
			$post_categories = wp_get_post_categories( $post_id );
        	$category = get_category($post_categories[0]);
			$post_title = get_the_title($post_id);
?>
			<a class="category-<?php echo $category->slug; ?>" href="<?php echo get_permalink($post_id); ?>" title="<?php echo esc_attr($post_title); ?>">

			<?php toplytics_post_image($post_id); ?>

			<div class="toplytics-box-title">
				<?php echo $post_title; ?>
				<!-- - <span class="toplytics-box-views"><?php printf('%d Views', $pv); ?></span>-->
			</div>
			</a>
		</div><!-- end of .toplytics-box-bg -->
	</li>
		<?php $number--; ?>
	<?php endforeach; ?>
</ol>

</div><!-- end of .toplytics-box -->
