{{--

This is the settings page and the page where the profile
selection is taking place. The various submit buttons
it has on this page will be conditionally
displayed according to the plguin
settings.

(array) $profile - This is the connected profile currently
being used by the Toplytics Widget. We use this in our
template to understand if we are connected and chose
an actual profile or not and display the relevant
information accordingly.

(array) $profiles - These are all the profiles available
on the profiles selection screen.

(string) $auth - This variable contains the way we have
authenticated for the plugin. (public / private)

(string) $lastUpdateTime - the date in the proper ISO format
which also supports translation.

(string) $lastUpdateCount - The number of items updated from
analytics on the last update.

--}}

<form method="post" action="options.php">

    {!! settings_fields('toplytics') !!}
    {!! do_settings_sections('toplytics') !!}
    {!! submit_button() !!}

</form>
