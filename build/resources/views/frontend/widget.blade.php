{{--

    !!! IMPORTANT !!!
    IF YOU WISH TO UPDATE THIS DEFAULT TEMPLATE, MAKE A COPY OF IT
    AND NAME IT "custom.blade.php". THE COPY SHOULD BE IN THE SAME FOLDER
    AS THIS FILE (views/frontend/custom.blade.php) AND IT WILL BE LOADED
    BY THE PLUGIN PROPERLLY AT RUNTIME. THIS WAY YOU WILL NOT LOSE ANY
    OTHER MODIFICATION YOU DID TO THIS FILE WHEN WE RELEASE ANOTHER UPDATE.

This file contains the default template for the frontend Widget that will be
displayed by toplytics. You can update this template by copy-pasting it in
this same folder and name it `custom.blade.php`.

Here is the data that is being passed for this template:
(The same data will be passed to the custom.blade.php template as well)

(object) $posts - all the posts to be displayed in the top with the following format
    {
        'permalink' = 'https://permalink.com/',
        'title'     = 'This is the post title',
        'views'     = 123
    } => default: none / required

(boolean) $showviews - if we should display the view count or not
    true / false => default: false

(string) $target - the target for the url window to open on
    blank / self / parent / top => default: self

--}}

<ol>
    @foreach($posts as $post)
        <li class="toplytics-list">
            <a class="toplytics-anchor" href="{{ $post->permalink }}" title="{{ $post->title }}" target="_{{ (isset($target) && $target) ? $target : 'self' }}">
                {{ $post->title }}
            </a>

            @if ( isset($showviews) && $showviews )
                <span class="toplytics-views-count">&nbsp;{{ $post->views.__(' Views', TOPLYTICS_DOMAIN) }}</span>
            @endif

        </li>
    @endforeach
</ol>
