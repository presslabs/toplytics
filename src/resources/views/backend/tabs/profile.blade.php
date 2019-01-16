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

<form action="{{$_SERVER['REQUEST_URI']}}" method="POST">
    {!! wp_nonce_field( 'toplytics-settings' ) !!}

    {{-- We show the google analytics profiles so the user can chose which one he wants to use. --}}

    <h3>{{ __('User profile selection', TOPLYTICS_DOMAIN) }}</h3>
    <p>{{ __('Select from the list of profiles the one you wish to use for this site.', TOPLYTICS_DOMAIN) }}</p>

    @if (isset($profiles) && $profiles)
        <ul>
            <li><label for="profile_id">{{ __( 'Profile Select', 'toplytics' ) }}<span> *</span>: </label>
                <select id="profile_id" name="profile_id">
                    @foreach ( $profiles as $id => $name )
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </li>
        </ul>
    @else
        @include('backend.partials.inlineNotification', ['type' => 'info', 'message' => __("Oh NO! There has been an error or there are no profiles to select for this account. You might want to add some or disconnect this Google Account using the button below.", TOPLYTICS_DOMAIN)])
    @endif

    <div class="submit">
        {{-- We show different submit buttons based on whether the user has chosen a profile or not. --}}

        @if (isset($profiles) && $profiles)
        <input type="submit" name="ToplyticsProfileSelect" class="button-primary" value="<?= __( 'Select Profile', TOPLYTICS_DOMAIN ) ?>" />
        @endif

        &nbsp;&nbsp;

        <input type="submit" name="ToplyticsSubmitAccountDisconnect" class="button" value="<?=   __( 'Disconnect Google Account', TOPLYTICS_DOMAIN ) ?>" />
    </div>

</form>
