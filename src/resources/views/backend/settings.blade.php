{{--

This is the main settings page template, the one which is being
displayed after the authorization step. From here we'll
display step 2 in activation (the profile selection) and
the status and stats for the updates of the data.

There is no data passed directly to this template.

--}}

@extends('backend.layout')

@section('subtitle', __('Settings & Overview', TOPLYTICS_DOMAIN))

@section('content')
    
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" 
            href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN )}}"
            title="{{ __('General config for the plugin.', TOPLYTICS_DOMAIN) }}">
            {{ __('Overview', TOPLYTICS_DOMAIN) }}
        </a>
    </h2>

    @include('backend.tabs.settingsOverview')

@endsection
