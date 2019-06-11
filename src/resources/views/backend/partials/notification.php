<?php $type = ( isset( $type ) ) ? $type : ''; ?>
<?php $is_dismissible = ( isset( $dismiss ) && $dismiss ) ? 'is-dismissible' : ''; ?>
<?php $style = ( isset( $style ) && $style ) ? 'style="' . $style . '"' : ''; ?>
<div class="notice notice-<?php echo $type; ?> <?php echo $is_dismissible; ?>" <?php echo $style; ?>>
    <p><?php esc_html_e( $message, TOPLYTICS_DOMAIN ); ?></p>
</div>