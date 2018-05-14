{{--

(string) $url - The url / permalink of for where this 
    'https://permalink.com/' => default: none / required

(string) $title - The title of the link which will also be the text for it
    'This is the title' => default: none / required

(string) $icon - The icon (if any) to be displyed before the link
    true / false => default: none / not required
    Icons: https://developer.wordpress.org/resource/dashicons/#admin-network

(string) $target - the target for the url window to open on
    blank / self / parent / top => default: none / not required

--}}

<a href="{{ $url }}"{{ isset( $target ) && $target ? ' target="_' . $target . '"' : '' }} title="{{ $title }}">

    {!! isset($icon) && $icon ? '<span class="dashicons dashicons-' . $icon . '"></span>' : '' !!}
    {{ __( $title, TOPLYTICS_DOMAIN) }}
</a>
