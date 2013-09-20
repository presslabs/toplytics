<?php
/* Toplytics Template: Simple
 */
?>
<?php if ( $title ) echo $before_title . $title . $after_title; ?>
<ol>
<?php foreach ($toplytics_results as $post_id => $post_view) : ?>
	<li>
<?php $lang=""; //if (isset($_GET['lang'])) $lang="&lang=".$_GET['lang']; ?>
        <a href="<?php echo get_permalink($post_id) . $lang; ?>" title="<?php echo esc_attr(get_the_title($post_id)); ?>">
<?php echo get_the_title($post_id); ?>
<?php if ($show_views) printf(__(' - %d Views', TOPLYTICS_TEXTDOMAIN), $post_view); ?>
		</a>
	</li>
<?php endforeach; ?>
</ol>

