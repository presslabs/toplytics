<?php
/**
 * 
 * This file is being used to swich between the 2 authorization
 * methods available at this stage for the plugin.
 * 
 * There is no data passed directly to this template.
 * 
 */

global $toplytics_engine;

?>

<div class="wrap ct-presslabs-wrap">
    <div class="ct-presslabs-branding">
        <div class="ct-presslabs-img">
            <img src="https://avatars3.githubusercontent.com/u/1033395?s=60">
        </div>
        <div class="text">
            <div class="ct-presslabs-plugin-name"><?php _e( TOPLYTICS_APP_NAME, TOPLYTICS_DOMAIN ); ?></div>
            <div class="settings"><?php _e( 'Google Account Authorization', TOPLYTICS_DOMAIN ); ?></div>
        </div>
    </div>

    <h2 class="nav-tab-wrapper">
        <?php $nav_tab_active = ( isset( $_GET['tab'] ) ) ? ( ( $_GET['tab'] == 'public' ) ? 'nav-tab-active' : '' ) : 'nav-tab-active'; ?>
        <a class="nav-tab <?php echo $nav_tab_active; ?>"
            href="<?php echo admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&amp;tab=' . 'public' ); ?>"
            title="<?php esc_attr_e( 'This is for the everyday user and small websites.', TOPLYTICS_DOMAIN ); ?>">
            <?php _e( 'Quick Connect', TOPLYTICS_DOMAIN ); ?>
        </a>

        <?php $nav_tab_active = ( isset( $_GET['tab'] )  && ( $_GET['tab'] == 'private' ) ) ? 'nav-tab-active' : ''; ?>
        <a class="nav-tab <?php echo $nav_tab_active; ?>"
            href="<?php echo admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&amp;tab=' . 'private' ); ?>"
            title="<?php esc_attr_e( 'This is for the the pros that value their privacy.', TOPLYTICS_DOMAIN ); ?>">
            <?php _e( 'Manual Connect (Advanced)', TOPLYTICS_DOMAIN ); ?>
        </a>
    </h2>

    <?php if ( isset( $_GET['tab'] ) && ( $_GET['tab'] == 'private' ) ) : ?>
        <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.privateConnect' ); ?>
    <?php else : ?>
        <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.publicConnect' ); ?>
    <?php endif; ?>

    <?php if ( isset( $isDirtyAuth ) && $isDirtyAuth ) : ?>
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">

            <?php wp_nonce_field( 'toplytics-dirty-cleanup' ); ?>

            <?php $type = 'warning'; ?>
            <?php $message = __( "We have detected a misconfiguration in your Google authorization settings. If you encounter any issues when you authorize via Google, please use the button below to clean up the settings.", TOPLYTICS_DOMAIN ); ?>
            <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.partials.inlineNotification' ); ?>

            <input type="submit" title="Clean-up Auth Config" name="ToplyticsCleanDirtyAuth" class="button-primary" style="margin: 20px;" value="<?php esc_attr_e( 'Clean-up Auth Config', TOPLYTICS_DOMAIN ); ?>" />
        </form>
    <?php endif; ?>

</div>