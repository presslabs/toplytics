<?php 
$toplytics_args = array(
  'period'      => $period,
  'numberposts' => $numberposts,
  'showviews'   => $showviews
);

$toplytics_results = false;
if ( function_exists( 'toplytics_get_results' ) )
  $toplytics_results = toplytics_get_results( $toplytics_args );

if ( $toplytics_results ) {
?>
<ol>
  <?php foreach ( $toplytics_results as $post_id => $post_views ) : ?>
  <li>
    <a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
    <?php echo get_the_title( $post_id ); ?>
    </a>
    <?php 
      if ( $showviews ) {
        echo '<span class="post-views">';
        printf( __( '%d Views', TOPLYTICS_TEXTDOMAIN ), $post_views );
        echo '</span>';
      }
    ?>
  </li>
<?php endforeach; ?>
<?php
  } else {
    echo "<p>No info found!</p>";
  }
?>
</ol>
