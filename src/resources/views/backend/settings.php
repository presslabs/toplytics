<?php
/**
 * This is the main settings page template, the one which is being
 * displayed after the authorization step. From here we will
 * display step 2 in activation (the profile selection) and
 * the status and stats for the updates of the data.
 *
 * There is no data passed directly to this template.
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
            <div class="settings"><?php _e( 'Settings & Overview', TOPLYTICS_DOMAIN ); ?></div>
        </div>
    </div>

    <h2 class="nav-tab-wrapper">
        <?php if ( isset( $profile ) && $profile ) : ?>
            <?php $nav_tab_active = ( isset( $_GET['tab'] ) ) ? ( ( $_GET['tab'] == 'overview' ) ? 'nav-tab-active' : '' ) : 'nav-tab-active'; ?>
            <a class="nav-tab <?php echo $nav_tab_active; ?>"
                href="<?php echo admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&amp;tab=' . 'overview' ); ?>"
                title="<?php esc_attr_e( 'General config for the plugin.', TOPLYTICS_DOMAIN ); ?>">
                <?php _e( 'Overview', TOPLYTICS_DOMAIN ); ?>
            </a>

            <?php $nav_tab_active = ( isset( $_GET['tab'] ) && ( $_GET['tab'] == 'settings' ) ) ? 'nav-tab-active' : ''; ?>
            <a class="nav-tab <?php echo $nav_tab_active; ?>"
                href="<?php echo admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&amp;tab=' . 'settings' ); ?>"
                title="<?php esc_attr_e( 'Global plugin settings for all widgets.', TOPLYTICS_DOMAIN ); ?>">
                <?php _e( 'Settings', TOPLYTICS_DOMAIN ); ?>
            </a>
        <?php else : ?>
            <?php $nav_tab_active = ( isset( $_GET['tab'] ) ) ? ( ( $_GET['tab'] == 'profile' ) ? 'nav-tab-active' : '' ) : 'nav-tab-active'; ?>
            <a class="nav-tab <?php echo $nav_tab_active; ?>"
                href="<?php echo admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&amp;tab=' . 'profile' ); ?>"
                title="<?php esc_attr_e( 'General config for the plugin.', TOPLYTICS_DOMAIN ); ?>">
                <?php _e( 'Profile', TOPLYTICS_DOMAIN ); ?>
            </a>
        <?php endif; ?>
    </h2>

    <?php if ( isset( $profile ) && $profile ) : ?>
        <?php if ( isset( $_GET['tab'] ) ) : ?>
            <?php if ($_GET['tab'] == 'overview') : ?>
                <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.overview' ); ?>
            <?php elseif ($_GET['tab'] == 'settings') : ?>
                <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.settings' ); ?>
            <?php endif; ?>
        <?php else : ?>
            <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.overview' ); ?>
        <?php endif; ?>
    <?php else : ?>
        <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.tabs.profile' ); ?>
    <?php endif; ?>
</div>