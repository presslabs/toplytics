<?php
/*
 * Toplytics Template: Default
 */
?>

<div class="toplytics-box">

<?php if ( $title ) echo $before_title . $title . $after_title; $count = 0; ?>
			  
<ol>				
	<?php foreach ($results[$type] as $post_id => $pv) : ?>
				
	<?php if ($number <= 0) break; ?>
				
	<li class="top<?php echo ($counter-$number+1); ?>">
		<div class="toplytics-box-bg">
			<?php $post = get_post($post_id); ?>
	  
<?php   $post_categories = wp_get_post_categories( $post_id );
        $category = get_category($post_categories[0]);
        $title = get_the_title($post_id);
			?>
        <a class="category-<?php echo $category->slug; ?>" href="<?php echo get_permalink($post_id); ?>" title="<?php echo esc_attr(get_the_title($post_id)); ?>">
		  <!--span class="details"><strong class="caption"><?php echo $title; ?></strong></span-->
				<?php 
				$images =& get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post_id );
				if ($images) {
					$firstImageSrc = wp_get_attachment_image_src(array_shift(array_keys($images)), 'toplytics-box', false);
					$firstImg = $firstImageSrc[0];
			  
if (@file_get_contents($firstImg)):
	echo '<img src="'.$firstImg.'" width="315" height="50" border="0" alt="'.get_the_title($post_id).'">';
endif;
				}
				?>
		  <div class="toplytics-box-title">
			<?php echo get_the_title($post_id); ?><!-- - <span class="toplytics-box-views"><?php printf('%d Views', $pv); ?></span>-->
		  </div>
		</a>
		  </div><!-- end of .toplytics-box-bg -->
	</li>
		<?php $number--; ?>
		<?php endforeach; ?>
</ol>
</div><!-- end of .toplytics-box -->
