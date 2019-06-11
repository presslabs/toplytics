<?php
/**
 * (string) $url - The url / permalink of for where this 
 *     'https://permalink.com/' => default: none / required
 * 
 * (string) $title - The title of the link which will also be the text for it
 *     'This is the title' => default: none / required
 * 
 * (string) $icon - The icon (if any) to be displyed before the link
 *     true / false => default: none / not required
 *     Icons: https://developer.wordpress.org/resource/dashicons/#admin-network
 * 
 * (string) $target - the target for the url window to open on
 *     blank / self / parent / top => default: none / not required
 */

$target = ( isset( $target ) && $target ) ? 'target="_' . $target . '"' : '';

?>

<a href="<?php echo esc_url( $url ); ?>" <?php echo $target; ?> title="<?php echo esc_attr( $title ); ?>">
    <?php if ( isset( $icon ) && $icon ) : ?>
        <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
    <?php endif; ?>
    <?php echo $title; ?>
</a>