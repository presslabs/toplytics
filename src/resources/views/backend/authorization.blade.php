{{--

This file is being used to swich between the 2 authorization
methods available at this stage for the plugin.

There is no data passed directly to this template.

--}}

@extends('backend.layout')

@section('subtitle', __('Google Account Authorization', TOPLYTICS_DOMAIN))

@section('content')

    <h2 class="nav-tab-wrapper">
        <a class="nav-tab{{ isset($_GET['tab']) ? ( $_GET['tab'] == 'public' ? ' nav-tab-active' : '' ) : ' nav-tab-active' }}" 
            href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&tab=' . 'public' )}}"
            title="{{ __('This is for the everyday user and small websites.', TOPLYTICS_DOMAIN) }}">
            {{ __('Public Authorization', TOPLYTICS_DOMAIN) }}
        </a>

        <a class="nav-tab{{ isset($_GET['tab']) && $_GET['tab'] == 'private' ? ' nav-tab-active' : '' }}" 
            href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&tab=' . 'private' )}}"
            title="{{ __('This is for the the pros that value their privacy.', TOPLYTICS_DOMAIN) }}">
            {{ __('Private Authorization (Advanced)', TOPLYTICS_DOMAIN)}}
        </a>
    </h2>

    @if (isset($_GET['tab'] ) && $_GET['tab'] == 'private')
        @include('backend.tabs.privateConnect')
    @else
        @include('backend.tabs.publicConnect')
    @endif

    @if (isset($isDirtyAuth) && $isDirtyAuth)
        <form action="{{$_SERVER['REQUEST_URI']}}" method="POST">

            {!! wp_nonce_field( 'toplytics-dirty-cleanup' ) !!}

            @include('backend.partials.inlineNotification', ['type' => 'warning', 'message' => __("We have detected a missconfiguration in your google authorization settings. If you encounter any issues when you authorize via Google please use the button below to clean up the settings.", TOPLYTICS_DOMAIN)])
            
            <input type="submit" title="Clean-up Auth Config" name="ToplyticsCleanDirtyAuth" class="button-primary" style="margin: 20px;" value="<?= __( 'Clean-up Auth Config', TOPLYTICS_DOMAIN ) ?>" />
        </form>
    @endif

@endsection
