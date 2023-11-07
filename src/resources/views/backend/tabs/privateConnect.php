<?php
/**
 * This is the private tab template which is being used to display
 * the recommended redirect URL and the fields for the user
 * to setup their own client/secret and redirect URL.
 * 
 * (string) $appRedirectURL - the default URL for the app that should
 * be set in your new APP after creation so we can automatically
 * get the authorization token for your google login.
 */
?>

<h2><?php _e( 'Manual Connect', TOPLYTICS_DOMAIN ); ?></h2>
<p><?php _e( 'The manual connect is the recommended way for connecting to your Google account, even if it is a bit more difficult and cumbersome. <br />It offers you complete control over the connection by using your very own API keys and application for granting access.', TOPLYTICS_DOMAIN ); ?></p>

<form action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>" method="POST">

    <?php wp_nonce_field( 'toplytics-private-authorization' ); ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="toplytics-private-client-id"><?php _e( 'Client ID', TOPLYTICS_DOMAIN ); ?></label></th>
                <td>
                    <input type="text" class="regular-text" id="toplytics-private-client-id" name="toplytics-private-client-id" value="" placeholder="<?php esc_attr_e( 'This is where your Client ID is going.', TOPLYTICS_DOMAIN ); ?>">
                </td>
            </tr>
            <tr>
            <th scope="row"><label for="toplytics-private-client-secret"><?php _e( 'Client Secret', TOPLYTICS_DOMAIN ); ?></label></th>
                <td>
                    <input type="text" class="regular-text" id="toplytics-private-client-secret" name="toplytics-private-client-secret" value="" placeholder="<?php esc_attr_e( 'This is where your Client Secret is going.', TOPLYTICS_DOMAIN ); ?>">
                </td>
            </tr>
            <tr>
            <th scope="row"><label for="toplytics-private-redirect"><?php _e( 'Redirect URL', TOPLYTICS_DOMAIN ); ?></label></th>
                <td>
                <input type="text" class="regular-text" id="toplytics-private-redirect" name="toplytics-private-redirect" value="<?php echo esc_attr( $appRedirectURL ); ?>" placeholder="<?php esc_attr_e( 'This is where your Redirect URL is going.', TOPLYTICS_DOMAIN ); ?>">

                    <p class="description"><?php echo __( 'This redirect URL is very important when you are using your own keys. Use the default Redirect URL in most cases. Make sure to only change it if you really know what you\'re doing.<br /><strong>Default:</strong> ', TOPLYTICS_DOMAIN ) . $appRedirectURL; ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <input type="submit" name="ToplyticsSubmitPrivateAuthorization" class="button-primary" value="<?php esc_attr_e( 'Private Authorize', TOPLYTICS_DOMAIN ); ?>" />

</form>