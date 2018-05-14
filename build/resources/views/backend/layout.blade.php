{{--

This file is the main template for the admin page interface of the
Toplytics Plugin. All admin pages will expand this template.

There is no data passed directly to this template.

--}}

<div class="wrap ct-presslabs-wrap">

        <div class="ct-presslabs-branding">
                <div class="ct-presslabs-img">
                        <img src="https://avatars3.githubusercontent.com/u/1033395?s=60">
                </div>
                <div class="text">
                        <div class="ct-presslabs-plugin-name">@yield('title', __( TOPLYTICS_APP_NAME, TOPLYTICS_DOMAIN ))</div>
                        <div class="settings">@yield('subtitle', __( 'Settings', TOPLYTICS_DOMAIN ))</div>
                </div>
        </div>

        @yield('content')
</div>
