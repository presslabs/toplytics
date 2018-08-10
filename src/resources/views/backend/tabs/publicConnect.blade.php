{{--

This is the public connection tab, which is being used to
basically show some information for the user to
understand what is the intent of this and
how we're going to use it's data.

There is no data passed directly to this template.

--}}

<h3>{{ __('Public Authorization', TOPLYTICS_DOMAIN) }}</h3>
<p>{!! __('This authorization method is using the Presslabs public API key to authenticate you to the Google Analytics API.<br />If you are concerned about privacy or are having any API related errors using this method please use the private method.', TOPLYTICS_DOMAIN) !!}</p>

<form action="{{$_SERVER['REQUEST_URI']}}" method="POST">

    {!! wp_nonce_field( 'toplytics-public-authorization' ) !!}

    @include('backend.partials.inlineNotification', ['type' => 'info', 'message' => __("Clicking the button below will redirect you to the Google Authorization screen and you will be asked for read access to your analytics profiles. If you don't agree with this or you have concerns about privacy, please use the private method.", TOPLYTICS_DOMAIN)])
    
    <input type="submit" title="Log in with your Google Account" name="ToplyticsSubmitPublicAuthorization" class="button-primary" style="margin: 20px;" value="{{ __( 'Log in with your Google Account via Presslabs.org', TOPLYTICS_DOMAIN ) }}" />

</form>
