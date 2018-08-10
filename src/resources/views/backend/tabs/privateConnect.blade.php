{{--

This is the private tab template which is being used to display
the recommended redirect URL and the fields for the user
to setup their own client/secret and redirect URL.

(string) $appRedirectURL - the default URL for the app that should
be set in your new APP after creation so we can automatically
get the authorization token for your google login.

--}}

<h2>{{ __('Private Authorization', TOPLYTICS_DOMAIN) }}</h2>
<p>The private authorization is the recommended way for connecting to your Google account, even if it's a bit more difficult and cumbersome. <br />It offers you complete control over the connection by using your very own API keys and application for granting access.</p>

<form action="{{$_SERVER['REQUEST_URI']}}" method="POST">

    {!! wp_nonce_field( 'toplytics-private-authorization' ) !!}

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="toplytics-private-client-id">Client ID</label></th>
                <td>
                    <input type="text" class="regular-text" id="toplytics-private-client-id" name="toplytics-private-client-id" value="" placeholder="This is where your Client ID is going.">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="toplytics-private-client-secret">Client Secret</label></th>
                <td>
                    <input type="text" class="regular-text" id="toplytics-private-client-secret" name="toplytics-private-client-secret" value="" placeholder="This is where your Client Secret is going.">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="toplytics-private-redirect">Redirect URL</label></th>
                <td>
                <input type="text" class="regular-text" id="toplytics-private-redirect" name="toplytics-private-redirect" value="{{ $appRedirectURL }}" placeholder="This is where your Redirect URL is going.">

                    <p class="description">{!! __('This redirect URL is very important when you are using your own keys. Use the default Redirect URL in most cases. Make sure to only change it if you really know what you\'re doing.<br /><strong>Default:</strong> ', TOPLYTICS_DOMAIN).$appRedirectURL !!}</p>
                </td>
            </tr>
        </tbody>
    </table>

    <input type="submit" name="ToplyticsSubmitPrivateAuthorization" class="button-primary" value="{{ __( 'Private Authorize', TOPLYTICS_DOMAIN ) }}" />

</form>
