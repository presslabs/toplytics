<?php
/**
 * This is the settings page and the page where the profile
 * selection is taking place. The various submit buttons
 * it has on this page will be conditionally
 * displayed according to the plguin
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

    <h3><?php _e( 'GA4 Property ID', TOPLYTICS_DOMAIN ); ?></h3>
    <p><?php _e( 'Enter your GA4 property ID below. You can find this in your Admin > Property Settings from <a href="https://analytics.google.com/analytics/web/" target="_blank"> analytics.google.com</a>.', TOPLYTICS_DOMAIN ); ?></p>

    <ul>
        <li><label for="property_id"><?php _e( 'Propery ID', TOPLYTICS_DOMAIN ); ?><span> *</span>: </label>
            <input if="property_id" name="property_id" type="number">
        </li>
    </ul>

    <div class="submit">
        <?php // We show different submit buttons based on whether the user has chosen a profile or not. ?>

        <input type="submit" name="ToplyticsProfileSelect" class="button-primary" value="<?php esc_attr_e( 'Confirm Property', TOPLYTICS_DOMAIN ); ?>" />
        
        &nbsp;&nbsp;

        <input type="submit" name="ToplyticsSubmitAccountDisconnect" class="button" value="<?php esc_attr_e( 'Disconnect Google Account', TOPLYTICS_DOMAIN ); ?>" />
    </div>

</form>