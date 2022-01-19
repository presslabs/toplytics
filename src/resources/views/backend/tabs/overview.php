<?php
/**
 * This is the settings page and the page where the profile
 * selection is taking place. The various submit buttons
 * it has on this page will be conditionally
 * displayed according to the plugin
 * settings.
 * 
 * (array) $profile - This is the connected profile currently
 * being used by the Toplytics Widget. We use this in our
 * template to understand if we are connected and chose
 * an actual profile or not and display the relevant
 * information accordingly.
 * 
 * (array) $profiles - These are all the profiles available
 * on the profiles selection screen.
 * 
 * (string) $auth - This variable contains the way we have
 * authenticated for the plugin. (public / private)
 * 
 * (string) $lastUpdateTime - the date in the proper ISO format
 * which also supports translation.
 * 
 * (string) $lastUpdateCount - The number of items updated from
 * analytics on the last update.
 */

global $toplytics_engine;

?>

<form action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>" method="POST">
    <?php wp_nonce_field( 'toplytics-settings' ); ?>

    <?php if ( isset( $profile ) && $profile ) : ?>

        <h3><?php _e( 'User profile info', TOPLYTICS_DOMAIN ); ?></h3>
        <p><?php _e( 'Below is the information regarding your connection and selected profile, as well as quick button controls to change them.', TOPLYTICS_DOMAIN ); ?></p>

        <?php // We show the connection details since we are already connected and we chose a profile ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="auth_config"><?php _e( 'Connection Information', TOPLYTICS_DOMAIN ); ?></label>
                </th>
                <td>
                    <?php if ( isset( $auth ) && $auth == 'private' ) : ?>
                    <?php _e( '<span>You are using the <strong>Private</strong> Authorization method. Good choice.</span>', TOPLYTICS_DOMAIN ); ?>
                    <?php else : ?>
                        <?php _e('<span>You are using the Presslabs public authorization method.</span>', TOPLYTICS_DOMAIN ); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label><?php _e( 'Active Profile', TOPLYTICS_DOMAIN ); ?></label>
                </th>
                <td><?php echo $profile['profile_info']; ?></td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label><?php _e( 'Last Data Update', TOPLYTICS_DOMAIN ); ?></label>
                </th>
                <td><?php echo $lastUpdateTime; ?></td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label><?php _e( 'Fetched from Google', TOPLYTICS_DOMAIN ); ?></label>
                </th>
                <td><?php echo $lastUpdateCount; ?></td>
            </tr>
        </table>
    <?php else : ?>
        <?php // We show the google analytics profiles so the user can chose which one he wants to use. ?>

        <h3><?php _e( 'User profile selection', TOPLYTICS_DOMAIN ); ?></h3>
        <p><?php _e( 'Select from the list of profiles the one you wish to use for this site.', TOPLYTICS_DOMAIN ); ?></p>

        <?php if ( isset( $profiles ) && $profiles ) : ?>
            <ul>
                <li><label for="profile_id"><?php _e( 'Profile Select', TOPLYTICS_DOMAIN ); ?><span> *</span>: </label>
                    <select id="profile_id" name="profile_id">
                        <?php foreach ( $profiles as $id => $name ) : ?>
                            <option value="<?php echo esc_attr( $id ); ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </li>
            </ul>
        <?php else : ?>
            <?php $type = 'info'; ?>
            <?php $message = __( "Oh NO! There has been an error or there are no profiles to select for this account. You might want to add some or disconnect this Google Account using the button below.", TOPLYTICS_DOMAIN ); ?>
            <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.partials.inlineNotification' ); ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="submit">
        <?php // We show different submit buttons based on whether the user has chosen a profile or not. ?>
        <?php if ( isset( $profile ) && $profile ) : ?>
            <a href="<?php echo admin_url('widgets.php'); ?>" class="button-primary"><?php _e( 'Widgets Management', TOPLYTICS_DOMAIN ); ?></a>&nbsp;&nbsp;
            <input type="submit" name="ToplyticsSubmitForceUpdate" class="button" value="<?php esc_attr_e( 'Update Top', TOPLYTICS_DOMAIN ); ?>" />&nbsp;&nbsp;
            <input type="submit" name="ToplyticsSubmitProfileSwitch" class="button" value="<?php esc_attr_e( 'Switch Analytics Profile', TOPLYTICS_DOMAIN ); ?>" />
        <?php elseif ( isset( $profiles ) && $profiles ) : ?>
            <input type="submit" name="ToplyticsProfileSelect" class="button-primary" value="<?php esc_attr_e( 'Select Profile', TOPLYTICS_DOMAIN ); ?>" />
        <?php endif; ?>

        &nbsp;&nbsp;

        <input id="ToplyticsSubmitAccountDisconnect" type="submit" name="ToplyticsSubmitAccountDisconnect" class="button" value="<?php esc_attr_e( 'Disconnect Google Account', TOPLYTICS_DOMAIN ); ?>" />
    </div>

</form>

<?php // We need to prevent accidental disconnect. ?>
<script type="text/javascript">
    var disconnect_btn = document.getElementById("ToplyticsSubmitAccountDisconnect");
    if (disconnect_btn !== null) disconnect_btn.onclick = function(){
        return confirm("<?php _e( 'Are you sure you want to disconnect your Google Account?', TOPLYTICS_DOMAIN ); ?>");
    };
</script>
