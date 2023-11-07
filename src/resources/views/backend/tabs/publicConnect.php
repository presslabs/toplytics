<?php
/**
 * This is the public connection tab, which is being used to
 * basically show some information for the user to
 * understand what is the intent of this and
 * how we're going to use it's data.
 * 
 * There is no data passed directly to this template.
 */

global $toplytics_engine;

?>

<h3><?php _e( 'Quick Connect', TOPLYTICS_DOMAIN ); ?></h3>
<p><?php _e( 'This authorization method is using the Presslabs public API key to authenticate you to the Google Analytics API.<br />If you are concerned about privacy or are having any API related errors using this method, please use the private method.', TOPLYTICS_DOMAIN ); ?></p>

<form action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>" method="POST">

    <?php wp_nonce_field( 'toplytics-public-authorization' ); ?>

    <?php $type = 'info'; ?>
    <?php $message = __( "Clicking the button below will redirect you to the Google Authorization screen and you will be asked for read access to your Analytics profiles. If you don't agree with this or you have concerns about privacy, please use the private method.", TOPLYTICS_DOMAIN ); ?>
    <?php include $toplytics_engine->backend->getWindow()->getView( 'backend.partials.inlineNotification' ); ?>

    <input type="submit" title="Log in with your Google Account" name="ToplyticsSubmitPublicAuthorization" class="button-primary" style="margin: 20px;" value="<?php esc_attr_e( 'Log in with your Google Account via Presslabs.org', TOPLYTICS_DOMAIN ); ?>" />

    <?php
        // TODO: Use Official Google login buton and JS method. 
        // https://developers.google.com/identity/gsi/web/guides/display-button#html_2
        // Maybe use button generator: https://developers.google.com/identity/gsi/web/tools/configurator
    ?>

</form>