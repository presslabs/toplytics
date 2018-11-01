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
        @if ( isset($profile) && $profile )
            <a class="nav-tab{{ isset($_GET['tab']) ? ( $_GET['tab'] == 'overview' ? ' nav-tab-active' : '' ) : ' nav-tab-active' }}" 
                href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&tab=' . 'overview' )}}"
                title="{{ __('General config for the plugin.', TOPLYTICS_DOMAIN) }}">
                {{ __('Overview', TOPLYTICS_DOMAIN) }}
            </a>

            <a class="nav-tab{{ isset($_GET['tab']) && $_GET['tab'] == 'settings' ? ' nav-tab-active' : '' }}" 
                href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&tab=' . 'settings' )}}"
                title="{{ __('Global plugin settings for all widgets.', TOPLYTICS_DOMAIN) }}">
                {{ __('Settings', TOPLYTICS_DOMAIN)}}
            </a>
        @else
            <a class="nav-tab{{ isset($_GET['tab']) ? ( $_GET['tab'] == 'profile' ? ' nav-tab-active' : '' ) : ' nav-tab-active' }}" 
                href="{{admin_url( TOPLYTICS_SUBMENU_PAGE . '?page=' . TOPLYTICS_DOMAIN . '&tab=' . 'profile' )}}"
                title="{{ __('General config for the plugin.', TOPLYTICS_DOMAIN) }}">
                {{ __('Profile', TOPLYTICS_DOMAIN) }}
            </a>
        @endif
    </h2>

    @if ( isset($profile) && $profile )
        @if (isset($_GET['tab'] ))
            @switch($_GET['tab'])
                @case('overview')
                    @include('backend.tabs.overview')
                    @break
                @case('settings')
                    @include('backend.tabs.settings')
                    @break
                @default
            @endswitch
        @else
            @include('backend.tabs.overview')
        @endif
    @else
        @include('backend.tabs.profile')
    @endif

@endsection 
