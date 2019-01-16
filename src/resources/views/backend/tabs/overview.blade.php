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

    @if ( isset($profile) && $profile )

        <h3>{{ __('User profile info', TOPLYTICS_DOMAIN) }}</h3>
        <p>{{ __('Below is the information regarding your connection and selected profile as well as quick button controls to change them.', TOPLYTICS_DOMAIN) }}</p>

        {{-- We show the connection details since we are already connected and we chose a profile --}}
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="auth_config">{{ __( 'Connection Information', 'toplytics' ) }}</label>
                </th>
                <td>
                    @if ( isset($auth) && $auth == 'private' )
                    {!! __('<span>You are using the <strong>Private</strong> Authorization method. Good choise.</span>', TOPLYTICS_DOMAIN) !!}
                    @else
                        {!! __('<span>You are using the Presslabs public authorization method.</span>', TOPLYTICS_DOMAIN) !!}
                    @endif
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label>{{ __( 'Active Profile', 'toplytics' ) }}</label>
                </th>
                <td>{{ $profile['profile_info'] }}</td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label>{{ __( 'Last Data Update', 'toplytics' ) }}</label>
                </th>
                <td>{{ $lastUpdateTime }}</td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label>{{ __( 'Fetched from Google', 'toplytics' ) }}</label>
                </th>
                <td>{{ $lastUpdateCount }}</td>
            </tr>
        </table>
    @else
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
    @endif

    <div class="submit">
        {{-- We show different submit buttons based on whether the user has chosen a profile or not. --}}
        @if ( isset($profile) && $profile )
            <a href="{!! admin_url('widgets.php') !!}" class="button-primary"><?= __('Widgets Management', TOPLYTICS_DOMAIN) ?></a>&nbsp;&nbsp;
            <input type="submit" name="ToplyticsSubmitForceUpdate" class="button" value=" <?= __( 'Update Now', TOPLYTICS_DOMAIN ) ?> " />&nbsp;&nbsp;
            <input type="submit" name="ToplyticsSubmitProfileSwitch" class="button" value=" <?= __( 'Switch Profile', 'toplytics' ) ?> " />
        @elseif(isset($profiles) && $profiles)
            <input type="submit" name="ToplyticsProfileSelect" class="button-primary" value=" <?= __( 'Select Profile', TOPLYTICS_DOMAIN ) ?> " />
        @endif

        &nbsp;&nbsp;

        <input id="ToplyticsSubmitAccountDisconnect" type="submit" name="ToplyticsSubmitAccountDisconnect" class="button" value="<?=   __( 'Disconnect Google Account', TOPLYTICS_DOMAIN ) ?>" />
    </div>

</form>

{{-- We need to prevent accidental disconnect.--}}
<script type="text/javascript">
    var disconnect_btn = document.getElementById("ToplyticsSubmitAccountDisconnect");
    if (disconnect_btn !== null) disconnect_btn.onclick = function(){
        return confirm("{{ __( 'Are you sure you want to disconnect your Google Account?', TOPLYTICS_DOMAIN ) }}");
    };
</script>
